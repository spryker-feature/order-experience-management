<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Persistence;

use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleDueDataTransfer;
use Generated\Shared\Transfer\RecurringScheduleHistoryTransfer;
use Generated\Shared\Transfer\RecurringScheduleStatusCountCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface OrderExperienceManagementRepositoryInterface
{
    /**
     * @param array<int> $stateIds
     *
     * @return array<\Generated\Shared\Transfer\StateMachineItemTransfer>
     */
    public function getStateMachineItemsByStateIds(array $stateIds): array;

    public function findRecurringScheduleById(int $idRecurringSchedule): ?RecurringScheduleTransfer;

    public function findRecurringScheduleDueData(int $idRecurringSchedule): ?RecurringScheduleDueDataTransfer;

    public function findLatestHistoryByScheduleId(int $idRecurringSchedule): ?RecurringScheduleHistoryTransfer;

    public function findRecurringScheduleByUuid(string $uuid): ?RecurringScheduleTransfer;

    public function findCurrentSmStateIdForSchedule(int $idRecurringSchedule, string $stateMachineName): ?int;

    public function findSmStateIdByStateMachineAndStateName(string $stateMachineName, string $stateName): ?int;

    public function getRecurringScheduleCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
    ): RecurringScheduleCollectionTransfer;

    public function getRecurringScheduleStatusCountCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
    ): RecurringScheduleStatusCountCollectionTransfer;

    /**
     * @param array<int> $scheduleIds
     *
     * @return array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    public function findScheduleItemsByScheduleIds(array $scheduleIds): array;

    /**
     * @param array<int> $scheduleIds
     *
     * @return array<\Generated\Shared\Transfer\RecurringScheduleHistoryTransfer>
     */
    public function findScheduleHistoriesByScheduleIds(array $scheduleIds, ?PaginationTransfer $paginationTransfer = null): array;
}
