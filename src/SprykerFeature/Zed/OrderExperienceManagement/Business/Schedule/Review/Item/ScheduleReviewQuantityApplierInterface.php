<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item;

interface ScheduleReviewQuantityApplierInterface
{
    /**
     * @param array<string, int> $acceptedQuantitiesByGroupKey
     * @param array<int, string> $groupKeysByIdRecurringScheduleItem
     *
     * @return array{0: array<int, \Generated\Shared\Transfer\RecurringScheduleItemTransfer>, 1: array<int>} Standing quantity changes keyed by recurring schedule item ID, and the IDs of the collapsed rows to delete.
     */
    public function applyStandingQuantities(
        array $acceptedQuantitiesByGroupKey,
        array $groupKeysByIdRecurringScheduleItem,
    ): array;

    /**
     * @param array<string, int> $acceptedQuantitiesByGroupKey
     * @param array<int, string> $groupKeysByIdRecurringScheduleItem
     *
     * @return array<int, \Generated\Shared\Transfer\RecurringScheduleItemTransfer> Next delivery quantity changes keyed by recurring schedule item ID.
     */
    public function applyOccurrenceQuantities(
        array $acceptedQuantitiesByGroupKey,
        array $groupKeysByIdRecurringScheduleItem,
    ): array;
}
