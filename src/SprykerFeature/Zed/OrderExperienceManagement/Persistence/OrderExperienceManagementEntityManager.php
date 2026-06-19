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
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringSchedule;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleHistory;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleItem;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementPersistenceFactory getFactory()
 */
class OrderExperienceManagementEntityManager extends AbstractEntityManager implements OrderExperienceManagementEntityManagerInterface
{
    protected const string COLUMN_FK_STATE_MACHINE_ITEM_STATE = 'FkStateMachineItemState';

    protected const string COLUMN_STATUS = 'Status';

    protected const string COLUMN_REFERENCE_NET_PRICE = 'ReferenceNetPrice';

    protected const string COLUMN_REFERENCE_GROSS_PRICE = 'ReferenceGrossPrice';

    public function createRecurringSchedule(RecurringScheduleTransfer $recurringScheduleTransfer): RecurringScheduleTransfer
    {
        $recurringScheduleMapper = $this->getFactory()->createRecurringScheduleMapper();

        $recurringScheduleEntity = $recurringScheduleMapper->mapRecurringScheduleTransferToRecurringScheduleEntity(
            $recurringScheduleTransfer,
            new SpyRecurringSchedule(),
        );
        $recurringScheduleEntity->save();

        return $recurringScheduleMapper->mapRecurringScheduleEntityToRecurringScheduleTransfer(
            $recurringScheduleEntity,
            $recurringScheduleTransfer,
        );
    }

    public function createRecurringScheduleItem(RecurringScheduleItemTransfer $recurringScheduleItemTransfer): RecurringScheduleItemTransfer
    {
        $recurringScheduleItemMapper = $this->getFactory()->createRecurringScheduleItemMapper();

        $recurringScheduleItemEntity = $recurringScheduleItemMapper->mapRecurringScheduleItemTransferToRecurringScheduleItemEntity(
            $recurringScheduleItemTransfer,
            new SpyRecurringScheduleItem(),
        );
        $recurringScheduleItemEntity->save();

        return $recurringScheduleItemMapper->mapRecurringScheduleItemEntityToRecurringScheduleItemTransfer(
            $recurringScheduleItemEntity,
            $recurringScheduleItemTransfer,
        );
    }

    public function updateRecurringScheduleStateMachineState(int $idRecurringSchedule, int $idStateMachineItemState, ?string $status): void
    {
        $updateData = [static::COLUMN_FK_STATE_MACHINE_ITEM_STATE => $idStateMachineItemState];

        if ($status !== null) {
            $updateData[static::COLUMN_STATUS] = $status;
        }

        $this->getFactory()
            ->createRecurringScheduleQuery()
            ->filterByIdRecurringSchedule($idRecurringSchedule)
            ->update($updateData);
    }

    public function createRecurringScheduleHistory(RecurringScheduleHistoryTransfer $recurringScheduleHistoryTransfer): RecurringScheduleHistoryTransfer
    {
        $recurringScheduleHistoryMapper = $this->getFactory()->createRecurringScheduleHistoryMapper();

        $recurringScheduleHistoryEntity = $recurringScheduleHistoryMapper->mapRecurringScheduleHistoryTransferToRecurringScheduleHistoryEntity(
            $recurringScheduleHistoryTransfer,
            new SpyRecurringScheduleHistory(),
        );
        $recurringScheduleHistoryEntity->save();

        return $recurringScheduleHistoryMapper->mapRecurringScheduleHistoryEntityToRecurringScheduleHistoryTransfer(
            $recurringScheduleHistoryEntity,
            $recurringScheduleHistoryTransfer,
        );
    }

    public function updateRecurringScheduleItem(RecurringScheduleItemTransfer $recurringScheduleItemTransfer): RecurringScheduleItemTransfer
    {
        $recurringScheduleItemEntity = $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByIdRecurringScheduleItem($recurringScheduleItemTransfer->getIdRecurringScheduleItemOrFail())
            ->findOne();

        if ($recurringScheduleItemEntity === null) {
            return $recurringScheduleItemTransfer;
        }

        $recurringScheduleItemMapper = $this->getFactory()->createRecurringScheduleItemMapper();

        $recurringScheduleItemEntity = $recurringScheduleItemMapper->mapRecurringScheduleItemTransferToRecurringScheduleItemEntity(
            $recurringScheduleItemTransfer,
            $recurringScheduleItemEntity,
        );
        $recurringScheduleItemEntity->save();

        return $recurringScheduleItemMapper->mapRecurringScheduleItemEntityToRecurringScheduleItemTransfer(
            $recurringScheduleItemEntity,
            $recurringScheduleItemTransfer,
        );
    }

    public function deleteRecurringScheduleItem(int $idRecurringScheduleItem): void
    {
        $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByIdRecurringScheduleItem($idRecurringScheduleItem)
            ->delete();
    }

    public function deleteRecurringScheduleItemsByGroupKey(int $idRecurringSchedule, string $groupKey): void
    {
        $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByFkRecurringSchedule($idRecurringSchedule)
            ->filterByGroupKey($groupKey)
            ->delete();
    }

    public function deleteRecurringScheduleItemsByBundleItemIdentifier(int $idRecurringSchedule, string $bundleItemIdentifier): void
    {
        $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByFkRecurringSchedule($idRecurringSchedule)
            ->filterByBundleItemIdentifier($bundleItemIdentifier)
            ->delete();

        $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByFkRecurringSchedule($idRecurringSchedule)
            ->filterByRelatedBundleItemIdentifier($bundleItemIdentifier)
            ->delete();
    }

    public function deleteRecurringScheduleItemsByConfiguredBundleGroupKey(int $idRecurringSchedule, string $configuredBundleGroupKey): void
    {
        $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByFkRecurringSchedule($idRecurringSchedule)
            ->filterByConfiguredBundleGroupKey($configuredBundleGroupKey)
            ->delete();
    }

    public function updateReferencePricesByGroupKey(
        int $idRecurringSchedule,
        string $groupKey,
        ?int $referenceNetPrice,
        ?int $referenceGrossPrice,
    ): void {
        $updateData = [];

        if ($referenceNetPrice !== null) {
            $updateData[static::COLUMN_REFERENCE_NET_PRICE] = $referenceNetPrice;
        }

        if ($referenceGrossPrice !== null) {
            $updateData[static::COLUMN_REFERENCE_GROSS_PRICE] = $referenceGrossPrice;
        }

        if ($updateData === []) {
            return;
        }

        $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByFkRecurringSchedule($idRecurringSchedule)
            ->filterByGroupKey($groupKey)
            ->update($updateData);
    }

    public function updateScheduleNextTriggerDate(int $idRecurringSchedule, string $nextTriggerDate): void
    {
        $this->getFactory()
            ->createRecurringScheduleQuery()
            ->filterByIdRecurringSchedule($idRecurringSchedule)
            ->findOne()
            ?->setNextTriggerDate($nextTriggerDate)
            ->save();
    }
}
