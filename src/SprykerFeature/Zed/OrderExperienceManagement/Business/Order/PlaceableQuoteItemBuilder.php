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
use Generated\Shared\Transfer\ShipmentTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\Mapper\PlaceableItemMapperInterface;

class PlaceableQuoteItemBuilder implements PlaceableQuoteItemBuilderInterface
{
    public function __construct(
        protected ItemShipmentMethodResolverInterface $itemShipmentMethodResolver,
        protected PlaceableItemMapperInterface $placeableItemMapper,
    ) {
    }

    public function appendScheduleItems(
        QuoteTransfer $quoteTransfer,
        RecurringScheduleTransfer $recurringScheduleTransfer,
        bool $isPlacement = false,
    ): QuoteTransfer {
        $itemTransfers = $this->buildItemTransfers($quoteTransfer, $recurringScheduleTransfer, $isPlacement);
        $itemTransfers = $this->alignBundleShipments($itemTransfers);

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
        $shipmentMethodIdMap = $this->itemShipmentMethodResolver->buildExpenseShipmentMethodMap($quoteTransfer);

        $itemTransfers = [];

        foreach ($this->groupScheduleItemsByGroupKey($recurringScheduleTransfer) as $recurringScheduleItemTransfers) {
            $itemTransfer = $this->placeableItemMapper->mapRecurringScheduleItemToItemTransfer(
                $recurringScheduleItemTransfers[0],
                $this->sumNextDeliveryQuantity($recurringScheduleItemTransfers),
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
            if ($this->isBundleItem($itemTransfer)) {
                $quoteTransfer->addBundleItem($itemTransfer);

                continue;
            }

            $quoteTransfer->addItem($itemTransfer);
        }

        return $quoteTransfer;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<\Generated\Shared\Transfer\ItemTransfer>
     */
    protected function alignBundleShipments(array $itemTransfers): array
    {
        $childShipmentByBundleIdentifier = $this->indexChildShipments($itemTransfers);

        foreach ($itemTransfers as $itemTransfer) {
            if (!$this->isBundleItem($itemTransfer)) {
                continue;
            }

            $childShipmentTransfer = $childShipmentByBundleIdentifier[$itemTransfer->getBundleItemIdentifier()] ?? null;

            if ($childShipmentTransfer === null) {
                continue;
            }

            $itemTransfer->setShipment(
                (new ShipmentTransfer())->fromArray($childShipmentTransfer->toArray(true, true), true),
            );
        }

        return $itemTransfers;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<string, \Generated\Shared\Transfer\ShipmentTransfer>
     */
    protected function indexChildShipments(array $itemTransfers): array
    {
        $childShipmentByBundleIdentifier = [];

        foreach ($itemTransfers as $itemTransfer) {
            $relatedBundleItemIdentifier = $itemTransfer->getRelatedBundleItemIdentifier();
            $shipmentTransfer = $itemTransfer->getShipment();

            if ($relatedBundleItemIdentifier === null || $shipmentTransfer === null) {
                continue;
            }

            $childShipmentByBundleIdentifier[$relatedBundleItemIdentifier] ??= $shipmentTransfer;
        }

        return $childShipmentByBundleIdentifier;
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

    protected function isBundleItem(ItemTransfer $itemTransfer): bool
    {
        return $itemTransfer->getBundleItemIdentifier() !== null
            && $itemTransfer->getRelatedBundleItemIdentifier() === null;
    }
}
