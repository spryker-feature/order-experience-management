<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Scope;

use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;

interface ScheduleReviewScopeStrategyInterface
{
    public function applyRemoval(
        RecurringScheduleItemReviewTransfer $recurringScheduleItemReviewTransfer,
        int $idRecurringSchedule,
    ): void;

    /**
     * @param array<string, int> $acceptedPricesByGroupKey
     * @param array<string, int> $acceptedQuantitiesByGroupKey
     * @param array<int, string> $groupKeysByIdRecurringScheduleItem Group keys keyed by recurring schedule item ID, ordered by ID ascending.
     */
    public function applyAcceptedItems(
        array $acceptedPricesByGroupKey,
        array $acceptedQuantitiesByGroupKey,
        array $groupKeysByIdRecurringScheduleItem,
        bool $isNetMode,
    ): void;

    public function applyAddedItemScope(RecurringScheduleItemTransfer $recurringScheduleItemTransfer): void;
}
