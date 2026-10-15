<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Builder;

use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\DecimalObject\Decimal;
use SprykerFeature\Yves\OrderExperienceManagement\Calculator\OrderTotalReviewThresholdCalculatorInterface;

class PriceToleranceRowBuilder implements PriceToleranceRowBuilderInterface
{
    /**
     * @see \Spryker\Shared\Price\PriceConfig::PRICE_MODE_NET
     */
    protected const string PRICE_MODE_NET = 'NET_MODE';

    public function __construct(
        protected OrderTotalReviewThresholdCalculatorInterface $orderTotalReviewThresholdCalculator,
    ) {
    }

    public function getEditableItems(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        return array_values(array_filter(
            $recurringScheduleTransfer->getItems()->getArrayCopy(),
            static fn (RecurringScheduleItemTransfer $recurringScheduleItemTransfer): bool => $recurringScheduleItemTransfer->getRelatedBundleItemIdentifier() === null
                && ($recurringScheduleItemTransfer->getGroupKey() !== null || $recurringScheduleItemTransfer->getBundleItemIdentifier() !== null),
        ));
    }

    public function isProductCheckIgnored(RecurringScheduleTransfer $recurringScheduleTransfer): bool
    {
        return $recurringScheduleTransfer->getIsProductPriceChangeIgnored() === true
            && ($recurringScheduleTransfer->getPriceTolerancePercent()?->isPositive() === true);
    }

    public function buildProductRows(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        $isNetMode = $recurringScheduleTransfer->getPriceMode() === static::PRICE_MODE_NET;
        $isProductCheckIgnored = $this->isProductCheckIgnored($recurringScheduleTransfer);
        $productRows = [];

        foreach ($this->getEditableItems($recurringScheduleTransfer) as $recurringScheduleItemTransfer) {
            $unitPrice = (int)($isNetMode
                ? $recurringScheduleItemTransfer->getNextDeliveryNetPrice() ?? $recurringScheduleItemTransfer->getReferenceNetPrice()
                : $recurringScheduleItemTransfer->getNextDeliveryGrossPrice() ?? $recurringScheduleItemTransfer->getReferenceGrossPrice());
            $tolerancePercent = $recurringScheduleItemTransfer->getPriceTolerancePercent() ?? Decimal::create(0);

            $productRows[] = [
                'item' => $recurringScheduleItemTransfer,
                'unitPrice' => $unitPrice,
                'quantity' => $recurringScheduleItemTransfer->getNextDeliveryQuantity() ?? $recurringScheduleItemTransfer->getQuantity(),
                'triggerPrice' => !$isProductCheckIgnored && $tolerancePercent->isPositive()
                    ? $this->orderTotalReviewThresholdCalculator->calculate($unitPrice, $tolerancePercent)
                    : null,
                'isReviewingAnyIncrease' => !$isProductCheckIgnored && $tolerancePercent->isZero(),
                'hasTolerance' => !$isProductCheckIgnored && $tolerancePercent->isPositive(),
            ];
        }

        return $productRows;
    }
}
