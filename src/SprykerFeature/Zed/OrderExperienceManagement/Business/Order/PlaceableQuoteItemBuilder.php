<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\Mapper\PlaceableItemMapperInterface;

class PlaceableQuoteItemBuilder implements PlaceableQuoteItemBuilderInterface
{
    public function __construct(
        protected ItemShipmentMethodResolverInterface $itemShipmentMethodResolver,
        protected PlaceableItemMapperInterface $placeableItemMapper,
        protected BundleItemClassifierInterface $bundleItemClassifier,
    ) {
    }

    public function appendScheduleItems(
        QuoteTransfer $quoteTransfer,
        RecurringScheduleTransfer $recurringScheduleTransfer,
        bool $isPlacement = false,
    ): QuoteTransfer {
        $itemTransfers = $this->buildItemTransfers($quoteTransfer, $recurringScheduleTransfer, $isPlacement);
        $itemTransfers = $this->itemShipmentMethodResolver->applyFallbackShipments($itemTransfers, $quoteTransfer);
        $itemTransfers = $this->itemShipmentMethodResolver->alignBundleShipments($itemTransfers);

        return $this->assignItemsToQuote($quoteTransfer, $itemTransfers);
    }

    /**
     * @return array<\Generated\Shared\Transfer\ItemTransfer>
     */
    protected function buildItemTransfers(
        QuoteTransfer $quoteTransfer,
        RecurringScheduleTransfer $recurringScheduleTransfer,
        bool $isPlacement,
    ): array {
        $shipmentMethodIdMap = $this->itemShipmentMethodResolver->buildShipmentMethodIdMapByMerchantReference($quoteTransfer);

        $itemTransfers = [];
        foreach ($this->groupScheduleItemsByGroupKey($recurringScheduleTransfer) as $recurringScheduleItemTransfers) {
            $nextDeliveryQuantity = $this->sumNextDeliveryQuantity($recurringScheduleItemTransfers);

            if ($nextDeliveryQuantity <= 0) {
                continue;
            }

            $itemTransfer = $this->placeableItemMapper->mapRecurringScheduleItemToItemTransfer(
                $recurringScheduleItemTransfers[0],
                $nextDeliveryQuantity,
                new ItemTransfer(),
            );
            $this->itemShipmentMethodResolver->applyShipmentMethodId($itemTransfer, $shipmentMethodIdMap);

            if (!$isPlacement) {
                $itemTransfers[] = $itemTransfer;

                continue;
            }

            foreach ($this->splitPackagingUnitItem($itemTransfer) as $placeableItemTransfer) {
                $itemTransfers[] = $placeableItemTransfer;
            }
        }

        return $itemTransfers;
    }

    /**
     * @return array<\Generated\Shared\Transfer\ItemTransfer>
     */
    protected function splitPackagingUnitItem(ItemTransfer $itemTransfer): array
    {
        if ($itemTransfer->getAmount() === null) {
            return [$itemTransfer];
        }

        $packagingUnitItemTransfers = [];
        $quantity = $itemTransfer->getQuantityOrFail();

        for ($i = 0; $i < $quantity; $i++) {
            $packagingUnitItemTransfers[] = (new ItemTransfer())
                ->fromArray($itemTransfer->toArray(true, true), true)
                ->setQuantity(1);
        }

        return $packagingUnitItemTransfers;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     */
    protected function assignItemsToQuote(QuoteTransfer $quoteTransfer, array $itemTransfers): QuoteTransfer
    {
        foreach ($itemTransfers as $itemTransfer) {
            if ($this->bundleItemClassifier->isBundleItem($itemTransfer)) {
                $quoteTransfer->addBundleItem($itemTransfer);

                continue;
            }

            $quoteTransfer->addItem($itemTransfer);
        }

        return $quoteTransfer;
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $recurringScheduleItemTransfers
     */
    protected function sumNextDeliveryQuantity(array $recurringScheduleItemTransfers): int
    {
        $totalQuantity = 0;

        foreach ($recurringScheduleItemTransfers as $recurringScheduleItemTransfer) {
            $totalQuantity += $recurringScheduleItemTransfer->getNextDeliveryQuantity() ?? $recurringScheduleItemTransfer->getQuantityOrFail();
        }

        return $totalQuantity;
    }

    /**
     * @return array<string, array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer>>
     */
    protected function groupScheduleItemsByGroupKey(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        $groupedScheduleItemTransfers = [];

        foreach ($this->flattenScheduleItems($recurringScheduleTransfer) as $recurringScheduleItemTransfer) {
            $key = $recurringScheduleItemTransfer->getGroupKey() ?? $recurringScheduleItemTransfer->getSkuOrFail();
            $groupedScheduleItemTransfers[$key][] = $recurringScheduleItemTransfer;
        }

        return $groupedScheduleItemTransfers;
    }

    /**
     * When the schedule is loaded for the review page its bundle children are folded into the parent's
     * bundledItems. Flatten them back into a single list so the placeable quote is rebuilt in full,
     * regardless of whether the schedule was loaded grouped (review) or flat (placement).
     *
     * @return array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    protected function flattenScheduleItems(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        $flattenedScheduleItemTransfers = [];

        foreach ($recurringScheduleTransfer->getItems() as $recurringScheduleItemTransfer) {
            $flattenedScheduleItemTransfers[] = $recurringScheduleItemTransfer;

            foreach ($recurringScheduleItemTransfer->getBundledItems() as $bundledScheduleItemTransfer) {
                $flattenedScheduleItemTransfers[] = $bundledScheduleItemTransfer;
            }
        }

        return $flattenedScheduleItemTransfers;
    }
}
