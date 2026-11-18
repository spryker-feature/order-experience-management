<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift;

use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleOrderReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use Spryker\DecimalObject\Decimal;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

class OrderTotalPriceDriftChecker extends AbstractPriceDriftChecker
{
    protected const int PERCENT_SCALE_THRESHOLD = 1;

    protected const int DECIMALS_ABOVE_THRESHOLD = 1;

    protected const int DECIMALS_BELOW_THRESHOLD = 2;

    protected const int INCREASE_PERCENT_WORKING_SCALE = 6;

    public function check(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        QuoteTransfer $originalQuoteTransfer,
        CartChangeTransfer $repricedCartChangeTransfer,
        string $priceMode,
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleValidationResultTransfer {
        $tolerancePercent = $recurringScheduleTransfer->getPriceTolerancePercent() ?? Decimal::create(0);

        // 0 switches the check off: any increase is then reviewed product by product
        if (!$tolerancePercent->isPositive()) {
            return $recurringScheduleValidationResultTransfer;
        }

        $scheduleItemsByGroupKey = $this->recurringScheduleItemIndexer->indexByGroupKey($recurringScheduleTransfer);
        $scheduleItemsByBundleIdentifier = $this->recurringScheduleItemIndexer->indexByBundleItemIdentifier($recurringScheduleTransfer);

        $previousTotal = 0;
        $currentTotal = 0;
        $driftedItemReviewTransfers = [];

        foreach ($repricedCartChangeTransfer->getItems() as $repricedItemTransfer) {
            $recurringScheduleItemTransfer = $this->findScheduleItem($repricedItemTransfer, $scheduleItemsByGroupKey, $scheduleItemsByBundleIdentifier);

            if ($recurringScheduleItemTransfer === null) {
                continue;
            }

            $previousPrice = $this->resolveReferencePriceByMode($recurringScheduleItemTransfer, $priceMode);
            $currentPrice = $this->resolveItemPriceByMode($repricedItemTransfer, $priceMode);

            // An item without a current price is reviewed on its own (price_unavailable); counting it here would
            // read as a falling total and could mask a rise on the other items.
            if ($previousPrice === null || $currentPrice === null || $currentPrice === 0) {
                continue;
            }

            $quantity = (int)$repricedItemTransfer->getQuantity();
            $previousTotal += $previousPrice * $quantity;
            $currentTotal += $currentPrice * $quantity;

            if ($currentPrice > $previousPrice) {
                $driftedItemReviewTransfers[] = (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem($recurringScheduleItemTransfer)
                    ->setPreviousPrice($previousPrice)
                    ->setCurrentPrice($currentPrice);
            }
        }

        if (!$this->isAboveTolerance($previousTotal, $currentTotal, $tolerancePercent)) {
            return $recurringScheduleValidationResultTransfer;
        }

        return $recurringScheduleValidationResultTransfer
            ->setOrderReview($this->createOrderReview($previousTotal, $currentTotal, $tolerancePercent, $driftedItemReviewTransfers))
            ->setIsValid(false);
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $driftedItemReviewTransfers
     */
    protected function createOrderReview(
        int $previousTotal,
        int $currentTotal,
        Decimal $tolerancePercent,
        array $driftedItemReviewTransfers
    ): RecurringScheduleOrderReviewTransfer {
        $recurringScheduleOrderReviewTransfer = (new RecurringScheduleOrderReviewTransfer())
            ->setReviewReason(SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_ORDER_TOTAL_INCREASED)
            ->setPreviousTotal($previousTotal)
            ->setCurrentTotal($currentTotal)
            ->setTolerancePercent($tolerancePercent)
            ->setIncreasePercent($this->formatIncreasePercent($previousTotal, $currentTotal));

        foreach ($driftedItemReviewTransfers as $driftedItemReviewTransfer) {
            $recurringScheduleOrderReviewTransfer->addDriftedItem($driftedItemReviewTransfer);
        }

        return $recurringScheduleOrderReviewTransfer;
    }

    protected function formatIncreasePercent(int $previousTotal, int $currentTotal): string
    {
        if ($previousTotal <= 0) {
            return '';
        }

        $increasePercent = Decimal::create($currentTotal - $previousTotal)
            ->multiply(static::PERCENT_DENOMINATOR)
            ->divide($previousTotal, static::INCREASE_PERCENT_WORKING_SCALE);
        $decimals = $increasePercent->greaterThanOrEquals(static::PERCENT_SCALE_THRESHOLD) ? static::DECIMALS_ABOVE_THRESHOLD : static::DECIMALS_BELOW_THRESHOLD;

        return $increasePercent->round($decimals)->trim()->toString();
    }
}
