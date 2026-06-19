<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Persistence;

use Generated\Shared\Transfer\RecurringScheduleHistoryTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface OrderExperienceManagementEntityManagerInterface
{
    public function createRecurringSchedule(RecurringScheduleTransfer $recurringScheduleTransfer): RecurringScheduleTransfer;

    public function createRecurringScheduleItem(RecurringScheduleItemTransfer $recurringScheduleItemTransfer): RecurringScheduleItemTransfer;

    public function updateRecurringScheduleStateMachineState(int $idRecurringSchedule, int $idStateMachineItemState, ?string $status): void;

    public function createRecurringScheduleHistory(RecurringScheduleHistoryTransfer $recurringScheduleHistoryTransfer): RecurringScheduleHistoryTransfer;

    public function updateRecurringScheduleItem(RecurringScheduleItemTransfer $recurringScheduleItemTransfer): RecurringScheduleItemTransfer;

    public function deleteRecurringScheduleItem(int $idRecurringScheduleItem): void;

    public function deleteRecurringScheduleItemsByGroupKey(int $idRecurringSchedule, string $groupKey): void;

    public function deleteRecurringScheduleItemsByBundleItemIdentifier(int $idRecurringSchedule, string $bundleItemIdentifier): void;

    public function deleteRecurringScheduleItemsByConfiguredBundleGroupKey(int $idRecurringSchedule, string $configuredBundleGroupKey): void;

    public function updateReferencePricesByGroupKey(
        int $idRecurringSchedule,
        string $groupKey,
        ?int $referenceNetPrice,
        ?int $referenceGrossPrice,
    ): void;

    public function updateScheduleNextTriggerDate(int $idRecurringSchedule, string $nextTriggerDate): void;
}
