<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\RecurringScheduleHistoryTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleHistory;

class RecurringScheduleHistoryMapper
{
    public function mapRecurringScheduleHistoryTransferToRecurringScheduleHistoryEntity(
        RecurringScheduleHistoryTransfer $recurringScheduleHistoryTransfer,
        SpyRecurringScheduleHistory $recurringScheduleHistoryEntity,
    ): SpyRecurringScheduleHistory {
        $recurringScheduleHistoryEntity->fromArray($recurringScheduleHistoryTransfer->modifiedToArray());

        $recurringScheduleHistoryEntity->setFkRecurringSchedule($recurringScheduleHistoryTransfer->getIdRecurringScheduleOrFail());
        $recurringScheduleHistoryEntity->setFkSalesOrder($recurringScheduleHistoryTransfer->getIdSalesOrder());

        // When a created_at is supplied explicitly (e.g. a skip recorded at the skipped occurrence's date),
        // set it so the timestampable behavior does not overwrite it with the insert time.
        if ($recurringScheduleHistoryTransfer->getCreatedAt() !== null) {
            $recurringScheduleHistoryEntity->setCreatedAt($recurringScheduleHistoryTransfer->getCreatedAt());
        }

        return $recurringScheduleHistoryEntity;
    }

    public function mapRecurringScheduleHistoryEntityToRecurringScheduleHistoryTransfer(
        SpyRecurringScheduleHistory $recurringScheduleHistoryEntity,
        RecurringScheduleHistoryTransfer $recurringScheduleHistoryTransfer,
    ): RecurringScheduleHistoryTransfer {
        $recurringScheduleHistoryTransfer->fromArray($recurringScheduleHistoryEntity->toArray(), true);

        $recurringScheduleHistoryTransfer->setIdRecurringSchedule($recurringScheduleHistoryEntity->getFkRecurringSchedule());
        $recurringScheduleHistoryTransfer->setIdSalesOrder($recurringScheduleHistoryEntity->getFkSalesOrder());

        return $recurringScheduleHistoryTransfer;
    }
}
