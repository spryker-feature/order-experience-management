<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Formatter;

use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\DecimalObject\Decimal;

class PriceToleranceLabelFormatter implements PriceToleranceLabelFormatterInterface
{
    /**
     * @see \Spryker\Shared\Price\PriceConfig::PRICE_MODE_NET
     */
    protected const string PRICE_MODE_NET = 'NET_MODE';

    public const string LABEL_ON = 'On';

    public const string LABEL_OFF = 'Off';

    protected const string FORMAT_PERCENT = '%s%%';

    protected const string FORMAT_PERCENT_WITH_THRESHOLD = '%s%% (%s %s)';

    protected const int MONEY_DIVISOR = 100;

    protected const int MONEY_DECIMALS = 2;

    protected const int PERCENT_DENOMINATOR = 100;

    protected const int WORKING_SCALE = 6;

    public function formatItemTolerance(
        RecurringScheduleItemTransfer $recurringScheduleItemTransfer,
        RecurringScheduleTransfer $recurringScheduleTransfer,
    ): string {
        $tolerancePercent = $recurringScheduleItemTransfer->getPriceTolerancePercent();

        if ($tolerancePercent === null || !$tolerancePercent->isPositive() || $this->isProductPriceChangeIgnored($recurringScheduleTransfer)) {
            return '';
        }

        $unitPrice = $this->resolveUnitPrice($recurringScheduleItemTransfer, $recurringScheduleTransfer);

        if ($unitPrice === null) {
            return sprintf(static::FORMAT_PERCENT, $tolerancePercent->trim()->toString());
        }

        return sprintf(
            static::FORMAT_PERCENT_WITH_THRESHOLD,
            $tolerancePercent->trim()->toString(),
            $this->formatMoney($this->calculateReviewThreshold($unitPrice, $tolerancePercent)),
            $recurringScheduleTransfer->getCurrencyIsoCode() ?? '',
        );
    }

    /**
     * @return array<int|string, string>
     */
    public function formatItemTolerances(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        $itemToleranceLabels = [];

        foreach ($recurringScheduleTransfer->getItems() as $itemKey => $recurringScheduleItemTransfer) {
            $itemToleranceLabels[$itemKey] = $this->formatItemTolerance($recurringScheduleItemTransfer, $recurringScheduleTransfer);
        }

        return $itemToleranceLabels;
    }

    /**
     * @see \SprykerFeature\Yves\OrderExperienceManagement\Calculator\OrderTotalReviewThresholdCalculator::calculate()
     */
    protected function calculateReviewThreshold(int $unitPrice, Decimal $tolerancePercent): int
    {
        return Decimal::create($unitPrice)
            ->multiply(Decimal::create(static::PERCENT_DENOMINATOR)->add($tolerancePercent))
            ->divide(static::PERCENT_DENOMINATOR, static::WORKING_SCALE)
            ->floor()
            ->toInt();
    }

    public function formatOrderTolerance(RecurringScheduleTransfer $recurringScheduleTransfer): string
    {
        $tolerancePercent = $recurringScheduleTransfer->getPriceTolerancePercent();

        if ($tolerancePercent === null || !$tolerancePercent->isPositive()) {
            return static::LABEL_OFF;
        }

        return sprintf(static::FORMAT_PERCENT, $tolerancePercent->trim()->toString());
    }

    public function formatToleranceState(RecurringScheduleTransfer $recurringScheduleTransfer): string
    {
        return $this->hasAnyTolerance($recurringScheduleTransfer) ? static::LABEL_ON : static::LABEL_OFF;
    }

    protected function hasAnyTolerance(RecurringScheduleTransfer $recurringScheduleTransfer): bool
    {
        if ($this->isPositive($recurringScheduleTransfer->getPriceTolerancePercent())) {
            return true;
        }

        foreach ($recurringScheduleTransfer->getItems() as $recurringScheduleItemTransfer) {
            if ($this->isPositive($recurringScheduleItemTransfer->getPriceTolerancePercent())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @see \SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift\AbstractPriceDriftChecker::isProductPriceChangeIgnored()
     */
    public function isProductPriceChangeIgnored(RecurringScheduleTransfer $recurringScheduleTransfer): bool
    {
        return $recurringScheduleTransfer->getIsProductPriceChangeIgnored() === true
            && $this->isPositive($recurringScheduleTransfer->getPriceTolerancePercent());
    }

    protected function isPositive(?Decimal $tolerancePercent): bool
    {
        return $tolerancePercent !== null && $tolerancePercent->isPositive();
    }

    /**
     * @see \SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift\AbstractPriceDriftChecker::resolveReferencePriceByMode()
     */
    protected function resolveUnitPrice(
        RecurringScheduleItemTransfer $recurringScheduleItemTransfer,
        RecurringScheduleTransfer $recurringScheduleTransfer,
    ): ?int {
        $unitPrice = $recurringScheduleTransfer->getPriceMode() === static::PRICE_MODE_NET
            ? $recurringScheduleItemTransfer->getNextDeliveryNetPrice() ?? $recurringScheduleItemTransfer->getReferenceNetPrice()
            : $recurringScheduleItemTransfer->getNextDeliveryGrossPrice() ?? $recurringScheduleItemTransfer->getReferenceGrossPrice();

        return $unitPrice === null ? null : (int)$unitPrice;
    }

    protected function formatMoney(int $amount): string
    {
        return number_format($amount / static::MONEY_DIVISOR, static::MONEY_DECIMALS);
    }
}
