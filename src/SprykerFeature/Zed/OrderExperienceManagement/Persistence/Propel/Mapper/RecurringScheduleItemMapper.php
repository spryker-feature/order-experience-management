<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleItem;

class RecurringScheduleItemMapper
{
    public function mapRecurringScheduleItemTransferToRecurringScheduleItemEntity(
        RecurringScheduleItemTransfer $recurringScheduleItemTransfer,
        SpyRecurringScheduleItem $recurringScheduleItemEntity,
    ): SpyRecurringScheduleItem {
        $recurringScheduleItemEntity->fromArray($recurringScheduleItemTransfer->modifiedToArray());

        if ($recurringScheduleItemTransfer->getIdRecurringSchedule() !== null) {
            $recurringScheduleItemEntity->setFkRecurringSchedule($recurringScheduleItemTransfer->getIdRecurringScheduleOrFail());
        }
        $recurringScheduleItemEntity->setFkShipmentMethod($recurringScheduleItemTransfer->getIdShipmentMethod());

        return $recurringScheduleItemEntity;
    }

    public function mapRecurringScheduleItemEntityToRecurringScheduleItemTransfer(
        SpyRecurringScheduleItem $recurringScheduleItemEntity,
        RecurringScheduleItemTransfer $recurringScheduleItemTransfer,
    ): RecurringScheduleItemTransfer {
        $recurringScheduleItemTransfer->fromArray($recurringScheduleItemEntity->toArray(), true);

        $recurringScheduleItemTransfer->setIdRecurringSchedule($recurringScheduleItemEntity->getFkRecurringSchedule());
        $recurringScheduleItemTransfer->setIdShipmentMethod($recurringScheduleItemEntity->getFkShipmentMethod());

        return $recurringScheduleItemTransfer;
    }
}
