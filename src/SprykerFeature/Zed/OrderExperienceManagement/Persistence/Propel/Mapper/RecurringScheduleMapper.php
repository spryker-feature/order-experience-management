<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleDueDataTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\Map\SpyRecurringScheduleTableMap;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringSchedule;

class RecurringScheduleMapper
{
    public function mapRecurringScheduleTransferToRecurringScheduleEntity(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        SpyRecurringSchedule $recurringScheduleEntity,
    ): SpyRecurringSchedule {
        $recurringScheduleEntity->fromArray($recurringScheduleTransfer->modifiedToArray());

        $recurringScheduleEntity->setFkCustomer($recurringScheduleTransfer->getIdCustomerOrFail());
        $recurringScheduleEntity->setFkCompanyUser($recurringScheduleTransfer->getIdCompanyUser());
        $recurringScheduleEntity->setFkSourceSalesOrder($recurringScheduleTransfer->getIdSourceSalesOrder());
        $recurringScheduleEntity->setFkStateMachineItemState($recurringScheduleTransfer->getIdStateMachineItemState());

        return $recurringScheduleEntity;
    }

    public function mapRecurringScheduleEntityToRecurringScheduleTransfer(
        SpyRecurringSchedule $recurringScheduleEntity,
        RecurringScheduleTransfer $recurringScheduleTransfer,
    ): RecurringScheduleTransfer {
        $recurringScheduleTransfer->fromArray($recurringScheduleEntity->toArray(), true);

        $recurringScheduleTransfer->setIdCustomer($recurringScheduleEntity->getFkCustomer());
        $recurringScheduleTransfer->setIdCompanyUser($recurringScheduleEntity->getFkCompanyUser());
        $recurringScheduleTransfer->setIdSourceSalesOrder($recurringScheduleEntity->getFkSourceSalesOrder());
        $recurringScheduleTransfer->setIdStateMachineItemState($recurringScheduleEntity->getFkStateMachineItemState());

        return $recurringScheduleTransfer;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function mapRowToStateMachineItemTransfer(
        array $row,
        StateMachineItemTransfer $stateMachineItemTransfer,
    ): StateMachineItemTransfer {
        $stateMachineItemTransfer
            ->setIdentifier((int)$row[SpyRecurringScheduleTableMap::COL_ID_RECURRING_SCHEDULE])
            ->setIdItemState((int)$row[SpyRecurringScheduleTableMap::COL_FK_STATE_MACHINE_ITEM_STATE]);

        return $stateMachineItemTransfer;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function mapRowToRecurringScheduleDueDataTransfer(
        array $row,
        RecurringScheduleDueDataTransfer $recurringScheduleDueDataTransfer,
    ): RecurringScheduleDueDataTransfer {
        $recurringScheduleDueDataTransfer
            ->setIdRecurringSchedule((int)$row[SpyRecurringScheduleTableMap::COL_ID_RECURRING_SCHEDULE])
            ->setNextTriggerDate((string)$row[SpyRecurringScheduleTableMap::COL_NEXT_TRIGGER_DATE])
            ->setNotificationWindowHours((int)$row[SpyRecurringScheduleTableMap::COL_NOTIFICATION_WINDOW_HOURS]);

        return $recurringScheduleDueDataTransfer;
    }

    /**
     * @param iterable<\Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringSchedule> $entities
     */
    public function mapEntityCollectionToTransferCollection(
        iterable $entities,
        RecurringScheduleCollectionTransfer $collectionTransfer,
    ): RecurringScheduleCollectionTransfer {
        foreach ($entities as $entity) {
            $collectionTransfer->addRecurringSchedule(
                $this->mapRecurringScheduleEntityToRecurringScheduleTransfer($entity, new RecurringScheduleTransfer()),
            );
        }

        return $collectionTransfer;
    }
}
