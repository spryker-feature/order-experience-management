<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item;

use Generated\Shared\Transfer\RecurringScheduleItemTransfer;

class ScheduleReviewQuantityApplier implements ScheduleReviewQuantityApplierInterface
{
    protected const int NEXT_DELIVERY_QUANTITY_SKIP = 0;

    /**
     * @param array<string, int> $acceptedQuantitiesByGroupKey
     * @param array<int, string> $groupKeysByIdRecurringScheduleItem
     *
     * @return array{0: array<int, \Generated\Shared\Transfer\RecurringScheduleItemTransfer>, 1: array<int>}
     */
    public function applyStandingQuantities(
        array $acceptedQuantitiesByGroupKey,
        array $groupKeysByIdRecurringScheduleItem,
    ): array {
        $recurringScheduleItemIdsByGroupKey = $this->mapRecurringScheduleItemIdsByGroupKey($groupKeysByIdRecurringScheduleItem);

        $recurringScheduleItemTransfers = [];
        $collapsedRecurringScheduleItemIds = [];

        foreach ($acceptedQuantitiesByGroupKey as $groupKey => $acceptedQuantity) {
            $recurringScheduleItemIds = $recurringScheduleItemIdsByGroupKey[$groupKey] ?? [];

            if ($recurringScheduleItemIds === []) {
                continue;
            }

            $idFirstRecurringScheduleItem = $recurringScheduleItemIds[0];
            $recurringScheduleItemTransfers[$idFirstRecurringScheduleItem] = (new RecurringScheduleItemTransfer())
                ->setIdRecurringScheduleItem($idFirstRecurringScheduleItem)
                ->setQuantity($acceptedQuantity);

            $collapsedRecurringScheduleItemIds = array_merge(
                $collapsedRecurringScheduleItemIds,
                array_slice($recurringScheduleItemIds, 1),
            );
        }

        return [$recurringScheduleItemTransfers, $collapsedRecurringScheduleItemIds];
    }

    /**
     * @param array<string, int> $acceptedQuantitiesByGroupKey
     * @param array<int, string> $groupKeysByIdRecurringScheduleItem
     *
     * @return array<int, \Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    public function applyOccurrenceQuantities(
        array $acceptedQuantitiesByGroupKey,
        array $groupKeysByIdRecurringScheduleItem,
    ): array {
        $recurringScheduleItemIdsByGroupKey = $this->mapRecurringScheduleItemIdsByGroupKey($groupKeysByIdRecurringScheduleItem);

        $recurringScheduleItemTransfers = [];

        foreach ($acceptedQuantitiesByGroupKey as $groupKey => $acceptedQuantity) {
            $recurringScheduleItemIds = $recurringScheduleItemIdsByGroupKey[$groupKey] ?? [];

            if ($recurringScheduleItemIds === []) {
                continue;
            }

            $recurringScheduleItemTransfers += $this->createNextDeliveryQuantityChanges($recurringScheduleItemIds, $acceptedQuantity);
        }

        return $recurringScheduleItemTransfers;
    }

    /**
     * @param array<int> $recurringScheduleItemIds
     *
     * @return array<int, \Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    protected function createNextDeliveryQuantityChanges(array $recurringScheduleItemIds, int $acceptedQuantity): array
    {
        $recurringScheduleItemTransfers = [];

        foreach ($recurringScheduleItemIds as $idRecurringScheduleItem) {
            $recurringScheduleItemTransfers[$idRecurringScheduleItem] = (new RecurringScheduleItemTransfer())
                ->setIdRecurringScheduleItem($idRecurringScheduleItem)
                ->setNextDeliveryQuantity(static::NEXT_DELIVERY_QUANTITY_SKIP);
        }

        $recurringScheduleItemTransfers[$recurringScheduleItemIds[0]]->setNextDeliveryQuantity($acceptedQuantity);

        return $recurringScheduleItemTransfers;
    }

    /**
     * @param array<int, string> $groupKeysByIdRecurringScheduleItem
     *
     * @return array<string, array<int>>
     */
    protected function mapRecurringScheduleItemIdsByGroupKey(array $groupKeysByIdRecurringScheduleItem): array
    {
        $recurringScheduleItemIdsByGroupKey = [];

        foreach ($groupKeysByIdRecurringScheduleItem as $idRecurringScheduleItem => $groupKey) {
            $recurringScheduleItemIdsByGroupKey[$groupKey][] = $idRecurringScheduleItem;
        }

        return $recurringScheduleItemIdsByGroupKey;
    }
}
