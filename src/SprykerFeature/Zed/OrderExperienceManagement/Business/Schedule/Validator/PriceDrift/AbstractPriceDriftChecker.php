<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

abstract class AbstractPriceDriftChecker implements PriceDriftCheckerInterface
{
    /**
     * @see \Spryker\Shared\Price\PriceConfig::PRICE_MODE_NET
     */
    protected const string PRICE_MODE_NET = 'NET_MODE';

    protected function resolveItemPriceByMode(ItemTransfer $itemTransfer, string $priceMode): ?int
    {
        $price = $priceMode === static::PRICE_MODE_NET
            ? $itemTransfer->getUnitNetPrice()
            : $itemTransfer->getUnitGrossPrice();

        return $this->castPriceToInt($price);
    }

    protected function resolveReferencePriceByMode(RecurringScheduleItemTransfer $recurringScheduleItemTransfer, string $priceMode): ?int
    {
        $price = $priceMode === static::PRICE_MODE_NET
            ? $recurringScheduleItemTransfer->getReferenceNetPrice()
            : $recurringScheduleItemTransfer->getReferenceGrossPrice();

        return $this->castPriceToInt($price);
    }

    protected function castPriceToInt(mixed $price): ?int
    {
        if ($price === null) {
            return null;
        }

        return (int)$price;
    }

    protected function resolveReviewReason(?int $previousPrice, ?int $currentPrice): ?string
    {
        if ($currentPrice === null || $currentPrice === 0) {
            return SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_UNAVAILABLE;
        }

        if ($currentPrice > $previousPrice) {
            return SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED;
        }

        return null;
    }

    protected function addPriceReviewWhenDrifted(
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
        ?RecurringScheduleItemTransfer $recurringScheduleItemTransfer,
        ?int $previousPrice,
        ?int $currentPrice,
    ): RecurringScheduleValidationResultTransfer {
        $reviewReason = $this->resolveReviewReason($previousPrice, $currentPrice);

        if ($reviewReason === null) {
            return $recurringScheduleValidationResultTransfer;
        }

        $recurringScheduleItemReviewTransfer = (new RecurringScheduleItemReviewTransfer())
            ->setRecurringScheduleItem($recurringScheduleItemTransfer)
            ->setPreviousPrice($previousPrice)
            ->setCurrentPrice($currentPrice)
            ->addReviewReason($reviewReason);

        return $recurringScheduleValidationResultTransfer
            ->addItemReview($recurringScheduleItemReviewTransfer)
            ->setIsValid(false);
    }
}
