<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Persistence;

use ArrayObject;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleDueDataTransfer;
use Generated\Shared\Transfer\RecurringScheduleHistoryTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleStatusCountCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleStatusCountTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\Map\SpyRecurringScheduleTableMap;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringSchedule;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleQuery;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineItemStateHistory;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementPersistenceFactory getFactory()
 */
class OrderExperienceManagementRepository extends AbstractRepository implements OrderExperienceManagementRepositoryInterface
{
    protected const string VIRTUAL_COL_ORDER_REFERENCE = 'order_reference';

    protected const array SORT_FIELD_MAP = [
        'spy_recurring_schedule.name' => SpyRecurringScheduleTableMap::COL_NAME,
        'spy_recurring_schedule.next_trigger_date' => SpyRecurringScheduleTableMap::COL_NEXT_TRIGGER_DATE,
        'spy_recurring_schedule.status' => SpyRecurringScheduleTableMap::COL_STATUS,
        'spy_recurring_schedule.cadence_type' => SpyRecurringScheduleTableMap::COL_CADENCE_TYPE,
    ];

    /**
     * @param array<int> $stateIds
     *
     * @return array<\Generated\Shared\Transfer\StateMachineItemTransfer>
     */
    public function getStateMachineItemsByStateIds(array $stateIds): array
    {
        if ($stateIds === []) {
            return [];
        }

        /** @var array<array<string, mixed>> $rows */
        $rows = $this->getFactory()
            ->createRecurringScheduleQuery()
            ->filterByFkStateMachineItemState_In($stateIds)
            ->select([
                SpyRecurringScheduleTableMap::COL_ID_RECURRING_SCHEDULE,
                SpyRecurringScheduleTableMap::COL_FK_STATE_MACHINE_ITEM_STATE,
            ])
            ->find()
            ->getData();

        $mapper = $this->getFactory()->createRecurringScheduleMapper();
        $stateMachineItemTransfers = [];

        foreach ($rows as $row) {
            $stateMachineItemTransfers[] = $mapper->mapRowToStateMachineItemTransfer($row, new StateMachineItemTransfer());
        }

        return $stateMachineItemTransfers;
    }

    public function findRecurringScheduleById(int $idRecurringSchedule): ?RecurringScheduleTransfer
    {
        $scheduleEntity = $this->getFactory()
            ->createRecurringScheduleQuery()
            ->filterByIdRecurringSchedule($idRecurringSchedule)
            ->findOne();

        if ($scheduleEntity === null) {
            return null;
        }

        return $this->mapRecurringScheduleEntityToTransferWithItems($scheduleEntity);
    }

    public function findRecurringScheduleDueData(int $idRecurringSchedule): ?RecurringScheduleDueDataTransfer
    {
        /** @var array<string, mixed>|null $row */
        $row = $this->getFactory()
            ->createRecurringScheduleQuery()
            ->filterByIdRecurringSchedule($idRecurringSchedule)
            ->select([
                SpyRecurringScheduleTableMap::COL_ID_RECURRING_SCHEDULE,
                SpyRecurringScheduleTableMap::COL_NEXT_TRIGGER_DATE,
                SpyRecurringScheduleTableMap::COL_NOTIFICATION_WINDOW_HOURS,
            ])
            ->findOne();

        if ($row === null) {
            return null;
        }

        return $this->getFactory()
            ->createRecurringScheduleMapper()
            ->mapRowToRecurringScheduleDueDataTransfer($row, new RecurringScheduleDueDataTransfer());
    }

    protected function mapRecurringScheduleEntityToTransferWithItems(SpyRecurringSchedule $scheduleEntity): RecurringScheduleTransfer
    {
        $recurringScheduleTransfer = $this->getFactory()
            ->createRecurringScheduleMapper()
            ->mapRecurringScheduleEntityToRecurringScheduleTransfer($scheduleEntity, new RecurringScheduleTransfer());

        $itemEntities = $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByFkRecurringSchedule($scheduleEntity->getIdRecurringSchedule())
            ->find();

        foreach ($itemEntities as $itemEntity) {
            $recurringScheduleTransfer->addItem(
                (new RecurringScheduleItemTransfer())->fromArray($itemEntity->toArray(), true),
            );
        }

        return $recurringScheduleTransfer;
    }

    public function findLatestHistoryByScheduleId(int $idRecurringSchedule): ?RecurringScheduleHistoryTransfer
    {
        $entity = $this->getFactory()
            ->createRecurringScheduleHistoryQuery()
            ->filterByFkRecurringSchedule($idRecurringSchedule)
            ->orderByIdRecurringScheduleHistory(Criteria::DESC)
            ->findOne();

        if ($entity === null) {
            return null;
        }

        return (new RecurringScheduleHistoryTransfer())->fromArray($entity->toArray(), true);
    }

    public function findRecurringScheduleByUuid(string $uuid): ?RecurringScheduleTransfer
    {
        $scheduleEntity = $this->getFactory()
            ->createRecurringScheduleQuery()
            ->filterByUuid($uuid)
            ->findOne();

        if ($scheduleEntity === null) {
            return null;
        }

        return $this->mapRecurringScheduleEntityToTransferWithItems($scheduleEntity);
    }

    /**
     * @module StateMachine
     */
    public function findSmStateIdByStateMachineAndStateName(string $stateMachineName, string $stateName): ?int
    {
        /** @var \Orm\Zed\StateMachine\Persistence\SpyStateMachineItemState|null $stateEntity */
        $stateEntity = $this->getFactory()
            ->createStateMachineItemStateQuery()
            ->filterByName($stateName)
            ->useProcessQuery()
                ->filterByStateMachineName($stateMachineName)
            ->endUse()
            ->findOne();

        return $stateEntity?->getIdStateMachineItemState();
    }

    /**
     * @module StateMachine
     */
    public function findCurrentSmStateIdForSchedule(int $idRecurringSchedule, string $stateMachineName): ?int
    {
        return $this->findLatestSmStateHistoryEntity($idRecurringSchedule, $stateMachineName)?->getFkStateMachineItemState();
    }

    /**
     * @module StateMachine
     */
    protected function findLatestSmStateHistoryEntity(int $idRecurringSchedule, string $stateMachineName): ?SpyStateMachineItemStateHistory
    {
        /** @var \Orm\Zed\StateMachine\Persistence\SpyStateMachineItemStateHistoryQuery $historyQuery */
        $historyQuery = $this->getFactory()
            ->createStateMachineItemStateHistoryQuery()
            ->filterByIdentifier($idRecurringSchedule)
            ->useStateQuery()
                ->useProcessQuery()
                    ->filterByStateMachineName($stateMachineName)
                ->endUse()
            ->endUse();

        return $historyQuery
            ->orderByIdStateMachineItemStateHistory(Criteria::DESC)
            ->findOne();
    }

    public function getRecurringScheduleCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleCollectionTransfer {
        $collectionTransfer = new RecurringScheduleCollectionTransfer();
        $paginationTransfer = $recurringScheduleCriteriaTransfer->getPagination();

        $query = $this->getFactory()->createRecurringScheduleQuery();

        $conditions = $recurringScheduleCriteriaTransfer->getRecurringScheduleConditions();
        if ($conditions !== null) {
            $query = $this->applyConditionsToQuery($query, $conditions);
        }

        $query = $this->applySortingToQuery($query, $recurringScheduleCriteriaTransfer->getSortCollection());

        if ($paginationTransfer !== null) {
            $query = $this->applyPagination($query, $paginationTransfer);
            $collectionTransfer->setPagination($paginationTransfer);
        }

        return $this->getFactory()
            ->createRecurringScheduleMapper()
            ->mapEntityCollectionToTransferCollection($query->find(), $collectionTransfer);
    }

    /**
     * @param array<int> $scheduleIds
     *
     * @return array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    public function findScheduleItemsByScheduleIds(array $scheduleIds): array
    {
        if ($scheduleIds === []) {
            return [];
        }

        $itemEntities = $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByFkRecurringSchedule_In($scheduleIds)
            ->find();

        $mapper = $this->getFactory()->createRecurringScheduleItemMapper();
        $itemTransfers = [];

        foreach ($itemEntities as $itemEntity) {
            $itemTransfers[] = $mapper->mapRecurringScheduleItemEntityToRecurringScheduleItemTransfer(
                $itemEntity,
                new RecurringScheduleItemTransfer(),
            );
        }

        return $itemTransfers;
    }

    /**
     * @module Sales
     *
     * @param array<int> $scheduleIds
     *
     * @return array<\Generated\Shared\Transfer\RecurringScheduleHistoryTransfer>
     */
    public function findScheduleHistoriesByScheduleIds(array $scheduleIds, ?PaginationTransfer $paginationTransfer = null): array
    {
        if ($scheduleIds === []) {
            return [];
        }

        $query = $this->getFactory()
            ->createRecurringScheduleHistoryQuery()
            ->filterByFkRecurringSchedule_In($scheduleIds)
            ->leftJoinSpySalesOrder()
            ->withColumn('spy_sales_order.order_reference', static::VIRTUAL_COL_ORDER_REFERENCE)
            ->orderByCreatedAt(Criteria::DESC);

        if ($paginationTransfer !== null) {
            $query = $this->applyPagination($query, $paginationTransfer);
        }

        $historyEntities = $query->find();

        $mapper = $this->getFactory()->createRecurringScheduleHistoryMapper();
        $historyTransfers = [];

        foreach ($historyEntities as $historyEntity) {
            $historyTransfer = $mapper->mapRecurringScheduleHistoryEntityToRecurringScheduleHistoryTransfer(
                $historyEntity,
                new RecurringScheduleHistoryTransfer(),
            );
            $historyTransfer->setOrderReference(
                $historyEntity->getVirtualColumn(static::VIRTUAL_COL_ORDER_REFERENCE),
            );
            $historyTransfers[] = $historyTransfer;
        }

        return $historyTransfers;
    }

    public function getRecurringScheduleStatusCountCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
    ): RecurringScheduleStatusCountCollectionTransfer {
        $query = $this->getFactory()->createRecurringScheduleQuery();

        $recurringScheduleConditionsTransfer = $recurringScheduleCriteriaTransfer->getRecurringScheduleConditions();
        if ($recurringScheduleConditionsTransfer !== null) {
            $query = $this->applyConditionsToQuery($query, $recurringScheduleConditionsTransfer);
        }

        /** @var array<array<string, string>> $rows */
        $rows = $query
            ->addGroupByColumn(SpyRecurringScheduleTableMap::COL_STATUS)
            ->withColumn('COUNT(*)', 'count')
            ->select([SpyRecurringScheduleTableMap::COL_STATUS, 'count'])
            ->find()
            ->getData();

        $statusCountCollectionTransfer = new RecurringScheduleStatusCountCollectionTransfer();

        foreach ($rows as $row) {
            $statusCountCollectionTransfer->addStatusCount(
                (new RecurringScheduleStatusCountTransfer())
                    ->setStatus($row[SpyRecurringScheduleTableMap::COL_STATUS])
                    ->setCount((int)$row['count']),
            );
        }

        return $statusCountCollectionTransfer;
    }

    /**
     * @module CompanyUser
     */
    protected function applyConditionsToQuery(
        SpyRecurringScheduleQuery $query,
        RecurringScheduleConditionsTransfer $recurringScheduleConditionsTransfer
    ): SpyRecurringScheduleQuery {
        if ($recurringScheduleConditionsTransfer->getUuids()) {
            $query->filterByUuid_In($recurringScheduleConditionsTransfer->getUuids());
        }

        if ($recurringScheduleConditionsTransfer->getCustomerIds()) {
            $query->filterByFkCustomer_In($recurringScheduleConditionsTransfer->getCustomerIds());
        }

        if ($recurringScheduleConditionsTransfer->getStatuses()) {
            $query->filterByStatus_In($recurringScheduleConditionsTransfer->getStatuses());
        }

        $this->applySearchCondition($query, $recurringScheduleConditionsTransfer);

        if ($recurringScheduleConditionsTransfer->getCompanyIds() || $recurringScheduleConditionsTransfer->getCompanyBusinessUnitIds()) {
            $companyUserQuery = $query->useSpyCompanyUserQuery();

            if ($recurringScheduleConditionsTransfer->getCompanyIds()) {
                $companyUserQuery->filterByFkCompany_In($recurringScheduleConditionsTransfer->getCompanyIds());
            }

            if ($recurringScheduleConditionsTransfer->getCompanyBusinessUnitIds()) {
                $companyUserQuery->filterByFkCompanyBusinessUnit_In($recurringScheduleConditionsTransfer->getCompanyBusinessUnitIds());
            }

            $companyUserQuery->endUse();
        }

        return $query;
    }

    protected function applySearchCondition(
        SpyRecurringScheduleQuery $query,
        RecurringScheduleConditionsTransfer $recurringScheduleConditionsTransfer
    ): void {
        $hasName = (bool)$recurringScheduleConditionsTransfer->getNames();
        $hasIds = (bool)$recurringScheduleConditionsTransfer->getIdRecurringSchedules();

        if (!$hasName && !$hasIds) {
            return;
        }

        if ($hasName && $hasIds) {
            $query->filterByName(sprintf('%%%s%%', $recurringScheduleConditionsTransfer->getNames()[0]), Criteria::LIKE)
                ->_or()
                ->filterByIdRecurringSchedule_In($recurringScheduleConditionsTransfer->getIdRecurringSchedules());

            return;
        }

        if ($hasIds) {
            $query->filterByIdRecurringSchedule_In($recurringScheduleConditionsTransfer->getIdRecurringSchedules());

            return;
        }

        $query->filterByName(sprintf('%%%s%%', $recurringScheduleConditionsTransfer->getNames()[0]), Criteria::LIKE);
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\SortTransfer> $sortCollection
     */
    protected function applySortingToQuery(
        SpyRecurringScheduleQuery $query,
        ArrayObject $sortCollection
    ): SpyRecurringScheduleQuery {
        if ($sortCollection->count() === 0) {
            $query->orderByNextTriggerDate(Criteria::ASC);

            return $query;
        }

        foreach ($sortCollection as $sort) {
            $column = static::SORT_FIELD_MAP[$sort->getField()] ?? null;
            if ($column === null) {
                continue;
            }

            $query->orderBy($column, $sort->getIsAscending() ? Criteria::ASC : Criteria::DESC);
        }

        return $query;
    }

    protected function applyPagination(ModelCriteria $query, PaginationTransfer $paginationTransfer): ModelCriteria
    {
        if ($paginationTransfer->getOffset() !== null && $paginationTransfer->getLimit() !== null) {
            $paginationTransfer->setNbResults($query->count());

            return $query
                ->offset($paginationTransfer->getOffsetOrFail())
                ->setLimit($paginationTransfer->getLimitOrFail());
        }

        if ($paginationTransfer->getPage() !== null && $paginationTransfer->getMaxPerPage() !== null) {
            $propelModelPager = $query->paginate(
                $paginationTransfer->getPageOrFail(),
                $paginationTransfer->getMaxPerPageOrFail(),
            );

            $paginationTransfer->setNbResults($propelModelPager->getNbResults())
                ->setFirstIndex($propelModelPager->getFirstIndex())
                ->setLastIndex($propelModelPager->getLastIndex())
                ->setFirstPage($propelModelPager->getFirstPage())
                ->setLastPage($propelModelPager->getLastPage())
                ->setNextPage($propelModelPager->getNextPage())
                ->setPreviousPage($propelModelPager->getPreviousPage());

            return $propelModelPager->getQuery();
        }

        return $query;
    }
}
