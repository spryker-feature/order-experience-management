<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Grouping;

use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

class RecurringScheduleItemGrouper implements RecurringScheduleItemGrouperInterface
{
    public function groupItems(RecurringScheduleTransfer $recurringScheduleTransfer): RecurringScheduleTransfer
    {
        $groupedItems = $this->buildGroupedItems($recurringScheduleTransfer->getItems());

        $recurringScheduleTransfer->getItems()->exchangeArray($groupedItems);
        $recurringScheduleTransfer->setEstimatedTotal($this->sumItemTotals($groupedItems));

        return $recurringScheduleTransfer;
    }

    /**
     * @param iterable<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $items
     *
     * @return array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    protected function buildGroupedItems(iterable $items): array
    {
        [$childItemsByBundleIdentifier, $nonChildItemTransfers] = $this->splitBundleChildren($items);

        $itemsByGroupKey = [];
        $standaloneItems = [];
        $attachedBundleIdentifiers = [];

        foreach ($nonChildItemTransfers as $recurringScheduleItemTransfer) {
            $bundleItemIdentifier = $recurringScheduleItemTransfer->getBundleItemIdentifier();

            if ($bundleItemIdentifier !== null) {
                $this->attachBundledItems($recurringScheduleItemTransfer, $childItemsByBundleIdentifier[$bundleItemIdentifier] ?? []);
                $attachedBundleIdentifiers[$bundleItemIdentifier] = true;
                $standaloneItems[] = $recurringScheduleItemTransfer;

                continue;
            }

            $groupKey = $recurringScheduleItemTransfer->getGroupKey();

            if ($groupKey === null) {
                $standaloneItems[] = $recurringScheduleItemTransfer;

                continue;
            }

            if (!isset($itemsByGroupKey[$groupKey])) {
                $itemsByGroupKey[$groupKey] = $recurringScheduleItemTransfer;

                continue;
            }

            $this->mergeItemIntoGroup($itemsByGroupKey[$groupKey], $recurringScheduleItemTransfer);
        }

        $orphanChildItems = $this->collectOrphanBundleChildren($childItemsByBundleIdentifier, $attachedBundleIdentifiers);

        return array_merge($standaloneItems, array_values($itemsByGroupKey), $orphanChildItems);
    }

    /**
     * @param iterable<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $items
     *
     * @return array{0: array<string, list<\Generated\Shared\Transfer\RecurringScheduleItemTransfer>>, 1: list<\Generated\Shared\Transfer\RecurringScheduleItemTransfer>}
     */
    protected function splitBundleChildren(iterable $items): array
    {
        $childItemsByBundleIdentifier = [];
        $nonChildItemTransfers = [];

        foreach ($items as $recurringScheduleItemTransfer) {
            $relatedBundleItemIdentifier = $recurringScheduleItemTransfer->getRelatedBundleItemIdentifier();

            if ($relatedBundleItemIdentifier !== null) {
                $childItemsByBundleIdentifier[$relatedBundleItemIdentifier][] = $recurringScheduleItemTransfer;

                continue;
            }

            $nonChildItemTransfers[] = $recurringScheduleItemTransfer;
        }

        return [$childItemsByBundleIdentifier, $nonChildItemTransfers];
    }

    /**
     * @param list<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $childItemTransfers
     */
    protected function attachBundledItems(RecurringScheduleItemTransfer $bundleItemTransfer, array $childItemTransfers): void
    {
        foreach ($childItemTransfers as $childItemTransfer) {
            $bundleItemTransfer->addBundledItem($childItemTransfer);
        }
    }

    /**
     * @param array<string, list<\Generated\Shared\Transfer\RecurringScheduleItemTransfer>> $childItemsByBundleIdentifier
     * @param array<string, bool> $attachedBundleIdentifiers
     *
     * @return list<\Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    protected function collectOrphanBundleChildren(array $childItemsByBundleIdentifier, array $attachedBundleIdentifiers): array
    {
        $orphanChildItems = [];

        foreach ($childItemsByBundleIdentifier as $bundleItemIdentifier => $childItemTransfers) {
            if (isset($attachedBundleIdentifiers[$bundleItemIdentifier])) {
                continue;
            }

            foreach ($childItemTransfers as $childItemTransfer) {
                $orphanChildItems[] = $childItemTransfer;
            }
        }

        return $orphanChildItems;
    }

    protected function mergeItemIntoGroup(
        RecurringScheduleItemTransfer $targetItemTransfer,
        RecurringScheduleItemTransfer $sourceItemTransfer,
    ): void {
        $targetQuantity = (int)$targetItemTransfer->getQuantity();
        $sourceQuantity = (int)$sourceItemTransfer->getQuantity();

        $targetItemTransfer->setQuantity($targetQuantity + $sourceQuantity);
        $targetItemTransfer->setItemTotal(
            (int)$targetItemTransfer->getItemTotal() + (int)$sourceItemTransfer->getItemTotal(),
        );

        $this->mergeNextDeliveryQuantity($targetItemTransfer, $sourceItemTransfer, $targetQuantity, $sourceQuantity);
    }

    protected function mergeNextDeliveryQuantity(
        RecurringScheduleItemTransfer $targetItemTransfer,
        RecurringScheduleItemTransfer $sourceItemTransfer,
        int $targetQuantity,
        int $sourceQuantity,
    ): void {
        $targetNextDeliveryQuantity = $targetItemTransfer->getNextDeliveryQuantity();
        $sourceNextDeliveryQuantity = $sourceItemTransfer->getNextDeliveryQuantity();

        if ($targetNextDeliveryQuantity === null && $sourceNextDeliveryQuantity === null) {
            return;
        }

        $targetItemTransfer->setNextDeliveryQuantity(
            ($targetNextDeliveryQuantity ?? $targetQuantity) + ($sourceNextDeliveryQuantity ?? $sourceQuantity),
        );
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $recurringScheduleItemTransfers
     */
    protected function sumItemTotals(array $recurringScheduleItemTransfers): int
    {
        $total = 0;

        foreach ($recurringScheduleItemTransfers as $recurringScheduleItemTransfer) {
            $total += (int)$recurringScheduleItemTransfer->getItemTotal();
        }

        return $total;
    }
}
