<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item;

class AcceptedItemReviewMapper implements AcceptedItemReviewMapperInterface
{
    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     *
     * @return array<string, int>
     */
    public function mapAcceptedPricesByGroupKey(array $acceptedItemReviewTransfers): array
    {
        $acceptedPricesByGroupKey = [];

        foreach ($acceptedItemReviewTransfers as $recurringScheduleItemReviewTransfer) {
            $groupKey = $recurringScheduleItemReviewTransfer->getRecurringScheduleItemOrFail()->getGroupKey();
            $acceptedPrice = $recurringScheduleItemReviewTransfer->getCurrentPrice();

            if ($groupKey === null || $acceptedPrice === null) {
                continue;
            }

            $acceptedPricesByGroupKey[$groupKey] = $acceptedPrice;
        }

        return $acceptedPricesByGroupKey;
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     *
     * @return array<string, int>
     */
    public function mapAcceptedQuantitiesByGroupKey(array $acceptedItemReviewTransfers): array
    {
        $acceptedQuantitiesByGroupKey = [];

        foreach ($acceptedItemReviewTransfers as $recurringScheduleItemReviewTransfer) {
            $groupKey = $recurringScheduleItemReviewTransfer->getRecurringScheduleItemOrFail()->getGroupKey();
            $acceptedQuantity = $recurringScheduleItemReviewTransfer->getAcceptedQuantity();

            if ($groupKey === null || $acceptedQuantity === null || $acceptedQuantity <= 0) {
                continue;
            }

            $acceptedQuantitiesByGroupKey[$groupKey] = $acceptedQuantity;
        }

        return $acceptedQuantitiesByGroupKey;
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     *
     * @return array<string>
     */
    public function mapRemovedGroupKeys(array $acceptedItemReviewTransfers): array
    {
        $removedGroupKeys = [];

        foreach ($acceptedItemReviewTransfers as $recurringScheduleItemReviewTransfer) {
            if ($recurringScheduleItemReviewTransfer->getIsRemoved() !== true) {
                continue;
            }

            $groupKey = $recurringScheduleItemReviewTransfer->getRecurringScheduleItemOrFail()->getGroupKey();

            if ($groupKey === null) {
                continue;
            }

            $removedGroupKeys[] = $groupKey;
        }

        return $removedGroupKeys;
    }
}
