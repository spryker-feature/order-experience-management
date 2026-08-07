<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander;

use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class RecurringScheduleLastExecutionExpander implements RecurringScheduleExpanderInterface
{
    public function __construct(protected readonly OrderExperienceManagementRepositoryInterface $repository)
    {
    }

    public function isApplicable(RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer): bool
    {
        return (bool)$recurringScheduleCriteriaTransfer->getRecurringScheduleConditions()?->getIsWithLastExecution();
    }

    public function expand(
        RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer,
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleCollectionTransfer {
        $scheduleIds = $this->extractScheduleIds($recurringScheduleCollectionTransfer);

        if ($scheduleIds === []) {
            return $recurringScheduleCollectionTransfer;
        }

        $lastExecutionDatesByScheduleId = $this->repository->getLastExecutionDatesByScheduleIds($scheduleIds);

        foreach ($recurringScheduleCollectionTransfer->getRecurringSchedules() as $recurringScheduleTransfer) {
            $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringSchedule();

            if ($idRecurringSchedule === null || !isset($lastExecutionDatesByScheduleId[$idRecurringSchedule])) {
                continue;
            }

            $recurringScheduleTransfer->setLastExecutionDate($lastExecutionDatesByScheduleId[$idRecurringSchedule]);
        }

        return $recurringScheduleCollectionTransfer;
    }

    /**
     * @return array<int>
     */
    protected function extractScheduleIds(RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer): array
    {
        $scheduleIds = [];

        foreach ($recurringScheduleCollectionTransfer->getRecurringSchedules() as $recurringScheduleTransfer) {
            $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringSchedule();

            if ($idRecurringSchedule !== null) {
                $scheduleIds[] = $idRecurringSchedule;
            }
        }

        return $scheduleIds;
    }
}
