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
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use Spryker\DecimalObject\Decimal;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Indexer\RecurringScheduleItemIndexerInterface;

abstract class AbstractPriceDriftChecker implements PriceDriftCheckerInterface
{
    /**
     * @see \Spryker\Shared\Price\PriceConfig::PRICE_MODE_NET
     */
    protected const string PRICE_MODE_NET = 'NET_MODE';

    protected const int PERCENT_DENOMINATOR = SharedOrderExperienceManagementConfig::PRICE_TOLERANCE_PERCENT_DENOMINATOR;

    public function __construct(
        protected readonly RecurringScheduleItemIndexerInterface $recurringScheduleItemIndexer,
    ) {
    }

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
            ? $recurringScheduleItemTransfer->getNextDeliveryNetPrice() ?? $recurringScheduleItemTransfer->getReferenceNetPrice()
            : $recurringScheduleItemTransfer->getNextDeliveryGrossPrice() ?? $recurringScheduleItemTransfer->getReferenceGrossPrice();

        return $this->castPriceToInt($price);
    }

    /**
     * Compares exactly, with no float rounding to move a boundary: `$current > $reference * (1 + $tolerancePercent / 100)`
     * is rearranged to `$current * 100 > $reference * (100 + $tolerancePercent)` and evaluated on decimals.
     */
    protected function isAboveTolerance(int $reference, int $current, Decimal $tolerancePercent): bool
    {
        return Decimal::create($current)->multiply(static::PERCENT_DENOMINATOR)->greaterThan(
            Decimal::create($reference)->multiply(Decimal::create(static::PERCENT_DENOMINATOR)->add($tolerancePercent)),
        );
    }

    protected function castPriceToInt(mixed $price): ?int
    {
        if ($price === null) {
            return null;
        }

        return (int)$price;
    }

    protected function resolveReviewReason(
        ?int $previousPrice,
        ?int $currentPrice,
        ?Decimal $itemTolerancePercent = null,
        bool $isProductPriceChangeIgnored = false,
    ): ?string {
        if ($currentPrice === null || $currentPrice === 0) {
            return SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_UNAVAILABLE;
        }

        if ($isProductPriceChangeIgnored) {
            return null;
        }

        if ($this->isAboveTolerance((int)$previousPrice, $currentPrice, $itemTolerancePercent ?? Decimal::create(0))) {
            return SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED;
        }

        return null;
    }

    protected function isProductPriceChangeIgnored(RecurringScheduleTransfer $recurringScheduleTransfer): bool
    {
        return $recurringScheduleTransfer->getIsProductPriceChangeIgnored() === true
            && $recurringScheduleTransfer->getPriceTolerancePercent()?->isPositive() === true;
    }

    protected function addPriceReviewWhenDrifted(
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
        ?RecurringScheduleItemTransfer $recurringScheduleItemTransfer,
        ?int $previousPrice,
        ?int $currentPrice,
        bool $isProductPriceChangeIgnored = false,
    ): RecurringScheduleValidationResultTransfer {
        $reviewReason = $this->resolveReviewReason(
            $previousPrice,
            $currentPrice,
            $recurringScheduleItemTransfer?->getPriceTolerancePercent(),
            $isProductPriceChangeIgnored,
        );

        if ($reviewReason === null) {
            return $this->addUnflaggedPriceChange($recurringScheduleValidationResultTransfer, $recurringScheduleItemTransfer, $previousPrice, $currentPrice);
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

    protected function addUnflaggedPriceChange(
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
        ?RecurringScheduleItemTransfer $recurringScheduleItemTransfer,
        ?int $previousPrice,
        ?int $currentPrice,
    ): RecurringScheduleValidationResultTransfer {
        if ($recurringScheduleItemTransfer === null || $previousPrice === null || $currentPrice === null || $previousPrice === $currentPrice) {
            return $recurringScheduleValidationResultTransfer;
        }

        return $recurringScheduleValidationResultTransfer->addUnflaggedPriceChange(
            (new RecurringScheduleItemReviewTransfer())
                ->setRecurringScheduleItem($recurringScheduleItemTransfer)
                ->setPreviousPrice($previousPrice)
                ->setCurrentPrice($currentPrice),
        );
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\RecurringScheduleItemTransfer> $scheduleItemsByGroupKey
     * @param array<string, \Generated\Shared\Transfer\RecurringScheduleItemTransfer> $scheduleItemsByBundleIdentifier
     */
    protected function findScheduleItem(
        ItemTransfer $repricedItemTransfer,
        array $scheduleItemsByGroupKey,
        array $scheduleItemsByBundleIdentifier,
    ): ?RecurringScheduleItemTransfer {
        // Children of a product bundle carry catalogue prices of their own; the bundle parent is what is charged.
        if ($repricedItemTransfer->getRelatedBundleItemIdentifier() !== null) {
            return null;
        }

        $bundleItemIdentifier = $repricedItemTransfer->getBundleItemIdentifier();

        if ($bundleItemIdentifier !== null) {
            return $scheduleItemsByBundleIdentifier[$bundleItemIdentifier] ?? null;
        }

        $groupKey = $repricedItemTransfer->getGroupKey();

        return $groupKey === null ? null : ($scheduleItemsByGroupKey[$groupKey] ?? null);
    }

    protected function isBundleRelated(ItemTransfer $itemTransfer): bool
    {
        return $itemTransfer->getBundleItemIdentifier() !== null || $itemTransfer->getRelatedBundleItemIdentifier() !== null;
    }
}
