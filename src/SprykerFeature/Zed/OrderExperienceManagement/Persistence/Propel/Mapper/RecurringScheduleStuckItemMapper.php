<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\RecurringScheduleStuckItemTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\Map\SpyRecurringScheduleTableMap;

class RecurringScheduleStuckItemMapper
{
    /**
     * @param array<array<string, mixed>> $scheduleRows
     * @param array<int, string> $stateNamesById
     * @param array<int, array<int, string>> $stateEnteredAtMap
     *
     * @return array<\Generated\Shared\Transfer\RecurringScheduleStuckItemTransfer>
     */
    public function mapScheduleRowsToRecurringScheduleStuckItemTransfers(
        array $scheduleRows,
        array $stateNamesById,
        array $stateEnteredAtMap,
    ): array {
        $recurringScheduleStuckItemTransfers = [];

        foreach ($scheduleRows as $scheduleRow) {
            $idRecurringSchedule = (int)$scheduleRow[SpyRecurringScheduleTableMap::COL_ID_RECURRING_SCHEDULE];
            $idStateMachineItemState = (int)$scheduleRow[SpyRecurringScheduleTableMap::COL_FK_STATE_MACHINE_ITEM_STATE];

            $recurringScheduleStuckItemTransfers[] = $this->mapScheduleRowToRecurringScheduleStuckItemTransfer(
                $scheduleRow,
                $stateNamesById[$idStateMachineItemState],
                $stateEnteredAtMap[$idRecurringSchedule][$idStateMachineItemState] ?? null,
                new RecurringScheduleStuckItemTransfer(),
            );
        }

        return $recurringScheduleStuckItemTransfers;
    }

    /**
     * @param array<string, mixed> $scheduleRow
     */
    public function mapScheduleRowToRecurringScheduleStuckItemTransfer(
        array $scheduleRow,
        string $stateName,
        ?string $stateEnteredAt,
        RecurringScheduleStuckItemTransfer $recurringScheduleStuckItemTransfer,
    ): RecurringScheduleStuckItemTransfer {
        return $recurringScheduleStuckItemTransfer
            ->setIdRecurringSchedule((int)$scheduleRow[SpyRecurringScheduleTableMap::COL_ID_RECURRING_SCHEDULE])
            ->setIdStateMachineItemState((int)$scheduleRow[SpyRecurringScheduleTableMap::COL_FK_STATE_MACHINE_ITEM_STATE])
            ->setStateName($stateName)
            ->setStateEnteredAt($stateEnteredAt ?? (string)$scheduleRow[SpyRecurringScheduleTableMap::COL_CREATED_AT])
            ->setIsStateHistoryMissing($stateEnteredAt === null)
            ->setLastTouchedAt((string)$scheduleRow[SpyRecurringScheduleTableMap::COL_UPDATED_AT]);
    }
}
