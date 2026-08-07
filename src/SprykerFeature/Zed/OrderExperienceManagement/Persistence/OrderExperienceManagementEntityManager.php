<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Persistence;

use Generated\Shared\Transfer\RecurringScheduleForecastSnapshotTransfer;
use Generated\Shared\Transfer\RecurringScheduleHistoryTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringSchedule;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleHistory;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleItem;
use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\Kernel\Persistence\AbstractEntityManager;
use Spryker\Zed\Propel\Persistence\BatchProcessor\ActiveRecordBatchProcessorTrait;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementPersistenceFactory getFactory()
 */
class OrderExperienceManagementEntityManager extends AbstractEntityManager implements OrderExperienceManagementEntityManagerInterface
{
    use ActiveRecordBatchProcessorTrait;

    protected const string COLUMN_FK_STATE_MACHINE_ITEM_STATE = 'FkStateMachineItemState';

    protected const string COLUMN_STATUS = 'Status';

    protected const string COLUMN_REFERENCE_NET_PRICE = 'ReferenceNetPrice';

    protected const string COLUMN_REFERENCE_GROSS_PRICE = 'ReferenceGrossPrice';

    protected const string COLUMN_NEXT_DELIVERY_QUANTITY = 'NextDeliveryQuantity';

    protected const string COLUMN_QUANTITY = 'Quantity';

    protected const int NEXT_DELIVERY_QUANTITY_SKIP = 0;

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

    public function saveRecurringScheduleForecast(
        RecurringScheduleForecastSnapshotTransfer $recurringScheduleForecastSnapshotTransfer
    ): RecurringScheduleForecastSnapshotTransfer {
        $recurringScheduleForecastMapper = $this->getFactory()->createRecurringScheduleForecastMapper();

        $recurringScheduleForecastEntity = $this->getFactory()
            ->createRecurringScheduleForecastQuery()
            ->filterByForecastKey($recurringScheduleForecastSnapshotTransfer->getForecastKeyOrFail())
            ->findOneOrCreate();

        $recurringScheduleForecastEntity = $recurringScheduleForecastMapper
            ->mapRecurringScheduleForecastSnapshotTransferToRecurringScheduleForecastEntity(
                $recurringScheduleForecastSnapshotTransfer,
                $recurringScheduleForecastEntity,
            );
        $recurringScheduleForecastEntity->save();

        return $recurringScheduleForecastMapper
            ->mapRecurringScheduleForecastEntityToRecurringScheduleForecastSnapshotTransfer(
                $recurringScheduleForecastEntity,
                $recurringScheduleForecastSnapshotTransfer,
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

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $recurringScheduleItemTransfers
     */
    public function createRecurringScheduleItemCollection(array $recurringScheduleItemTransfers): void
    {
        $recurringScheduleItemMapper = $this->getFactory()->createRecurringScheduleItemMapper();

        foreach ($recurringScheduleItemTransfers as $recurringScheduleItemTransfer) {
            $this->persist(
                $recurringScheduleItemMapper->mapRecurringScheduleItemTransferToRecurringScheduleItemEntity(
                    $recurringScheduleItemTransfer,
                    new SpyRecurringScheduleItem(),
                ),
            );
        }

        $this->commit();
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $recurringScheduleItemTransfers Each transfer carries idRecurringScheduleItem plus only the properties to be written.
     */
    public function updateRecurringScheduleItemCollection(array $recurringScheduleItemTransfers): void
    {
        if ($recurringScheduleItemTransfers === []) {
            return;
        }

        $recurringScheduleItemEntities = $this->findRecurringScheduleItemEntitiesIndexedById($recurringScheduleItemTransfers);
        $recurringScheduleItemMapper = $this->getFactory()->createRecurringScheduleItemMapper();

        foreach ($recurringScheduleItemTransfers as $recurringScheduleItemTransfer) {
            $recurringScheduleItemEntity = $recurringScheduleItemEntities[$recurringScheduleItemTransfer->getIdRecurringScheduleItemOrFail()] ?? null;

            if ($recurringScheduleItemEntity === null) {
                continue;
            }

            $this->persist(
                $recurringScheduleItemMapper->mapRecurringScheduleItemTransferToRecurringScheduleItemEntity(
                    $recurringScheduleItemTransfer,
                    $recurringScheduleItemEntity,
                ),
            );
        }

        $this->commit();
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $recurringScheduleItemTransfers
     *
     * @return array<int, \Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleItem>
     */
    protected function findRecurringScheduleItemEntitiesIndexedById(array $recurringScheduleItemTransfers): array
    {
        $recurringScheduleItemIds = [];

        foreach ($recurringScheduleItemTransfers as $recurringScheduleItemTransfer) {
            $recurringScheduleItemIds[] = $recurringScheduleItemTransfer->getIdRecurringScheduleItemOrFail();
        }

        $recurringScheduleItemEntities = $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByIdRecurringScheduleItem_In($recurringScheduleItemIds)
            ->find();

        $recurringScheduleItemEntitiesById = [];

        foreach ($recurringScheduleItemEntities as $recurringScheduleItemEntity) {
            $recurringScheduleItemEntitiesById[$recurringScheduleItemEntity->getIdRecurringScheduleItem()] = $recurringScheduleItemEntity;
        }

        return $recurringScheduleItemEntitiesById;
    }

    /**
     * @param array<int> $recurringScheduleItemIds
     */
    public function deleteRecurringScheduleItemsByIds(array $recurringScheduleItemIds): void
    {
        if ($recurringScheduleItemIds === []) {
            return;
        }

        $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByIdRecurringScheduleItem_In($recurringScheduleItemIds)
            ->delete();
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

    public function deleteRecurringScheduleItemsWithZeroQuantity(int $idRecurringSchedule): void
    {
        $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByFkRecurringSchedule($idRecurringSchedule)
            ->filterByQuantity(0, Criteria::LESS_EQUAL)
            ->delete();
    }

    public function updateNextDeliveryQuantityToZeroByGroupKey(int $idRecurringSchedule, string $groupKey): void
    {
        $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByFkRecurringSchedule($idRecurringSchedule)
            ->filterByGroupKey($groupKey)
            ->update([static::COLUMN_NEXT_DELIVERY_QUANTITY => static::NEXT_DELIVERY_QUANTITY_SKIP]);
    }

    public function updateNextDeliveryQuantityToZeroByBundleItemIdentifier(int $idRecurringSchedule, string $bundleItemIdentifier): void
    {
        $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByFkRecurringSchedule($idRecurringSchedule)
            ->filterByBundleItemIdentifier($bundleItemIdentifier)
            ->update([static::COLUMN_NEXT_DELIVERY_QUANTITY => static::NEXT_DELIVERY_QUANTITY_SKIP]);

        $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByFkRecurringSchedule($idRecurringSchedule)
            ->filterByRelatedBundleItemIdentifier($bundleItemIdentifier)
            ->update([static::COLUMN_NEXT_DELIVERY_QUANTITY => static::NEXT_DELIVERY_QUANTITY_SKIP]);
    }

    public function updateNextDeliveryQuantityToZeroByConfiguredBundleGroupKey(int $idRecurringSchedule, string $configuredBundleGroupKey): void
    {
        $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByFkRecurringSchedule($idRecurringSchedule)
            ->filterByConfiguredBundleGroupKey($configuredBundleGroupKey)
            ->update([static::COLUMN_NEXT_DELIVERY_QUANTITY => static::NEXT_DELIVERY_QUANTITY_SKIP]);
    }

    public function updateRecurringScheduleItemNextDeliveryQuantity(int $idRecurringScheduleItem, ?int $nextDeliveryQuantity): void
    {
        $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByIdRecurringScheduleItem($idRecurringScheduleItem)
            ->findOne()
            ?->setNextDeliveryQuantity($nextDeliveryQuantity)
            ->save();
    }

    public function updateNextDeliveryQuantitiesToNull(int $idRecurringSchedule): void
    {
        $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByFkRecurringSchedule($idRecurringSchedule)
            ->update([static::COLUMN_NEXT_DELIVERY_QUANTITY => null]);
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

    public function updateRecurringScheduleQuoteData(int $idRecurringSchedule, string $quoteData): void
    {
        $this->getFactory()
            ->createRecurringScheduleQuery()
            ->filterByIdRecurringSchedule($idRecurringSchedule)
            ->findOne()
            ?->setQuoteData($quoteData)
            ->save();
    }

    public function updateRecurringSchedule(RecurringScheduleTransfer $recurringScheduleTransfer): RecurringScheduleTransfer
    {
        $recurringScheduleEntity = $this->getFactory()
            ->createRecurringScheduleQuery()
            ->filterByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail())
            ->findOne();

        if ($recurringScheduleEntity === null) {
            return $recurringScheduleTransfer;
        }

        $recurringScheduleEntity->fromArray($recurringScheduleTransfer->modifiedToArray());
        $recurringScheduleEntity->save();

        return $this->getFactory()
            ->createRecurringScheduleMapper()
            ->mapRecurringScheduleEntityToRecurringScheduleTransfer($recurringScheduleEntity, $recurringScheduleTransfer);
    }
}
