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
use Generated\Shared\Transfer\RecurringScheduleForecastSnapshotTransfer;
use Generated\Shared\Transfer\RecurringScheduleHistoryTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleStatusCountCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleStatusCountTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use Orm\Zed\Company\Persistence\Map\SpyCompanyTableMap;
use Orm\Zed\CompanyBusinessUnit\Persistence\Map\SpyCompanyBusinessUnitTableMap;
use Orm\Zed\OrderExperienceManagement\Persistence\Map\SpyRecurringScheduleHistoryTableMap;
use Orm\Zed\OrderExperienceManagement\Persistence\Map\SpyRecurringScheduleItemTableMap;
use Orm\Zed\OrderExperienceManagement\Persistence\Map\SpyRecurringScheduleTableMap;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringSchedule;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleQuery;
use Orm\Zed\Sales\Persistence\Map\SpySalesOrderTableMap;
use Orm\Zed\Sales\Persistence\Map\SpySalesOrderTotalsTableMap;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineItemStateHistory;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Join;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\Propel\Mapper\RecurringScheduleForecastMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\Propel\Mapper\RecurringScheduleMapper;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementPersistenceFactory getFactory()
 */
class OrderExperienceManagementRepository extends AbstractRepository implements OrderExperienceManagementRepositoryInterface
{
    protected const string VIRTUAL_COL_ORDER_REFERENCE = 'order_reference';

    protected const string VIRTUAL_COL_LAST_EXECUTION_DATE = 'last_execution_date';

    protected const string ALIAS_NEWER_ORDER_TOTALS = 'newer_order_totals';

    /**
     * @see \Spryker\Shared\Price\PriceConfig::PRICE_MODE_NET
     */
    protected const string PRICE_MODE_NET = 'NET_MODE';

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

    public function findRecurringScheduleForecastSnapshot(string $forecastKey): ?RecurringScheduleForecastSnapshotTransfer
    {
        $recurringScheduleForecastEntity = $this->getFactory()
            ->createRecurringScheduleForecastQuery()
            ->filterByForecastKey($forecastKey)
            ->findOne();

        if ($recurringScheduleForecastEntity === null) {
            return null;
        }

        return $this->getFactory()
            ->createRecurringScheduleForecastMapper()
            ->mapRecurringScheduleForecastEntityToRecurringScheduleForecastSnapshotTransfer(
                $recurringScheduleForecastEntity,
                new RecurringScheduleForecastSnapshotTransfer(),
            );
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
            $this->applyCycleTotalConditions($query, $conditions);
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
     * @return array<\Generated\Shared\Transfer\RecurringScheduleForecastTransfer>
     */
    public function getRecurringScheduleForecastData(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
    ): array {
        $query = $this->getFactory()->createRecurringScheduleQuery();

        $conditions = $recurringScheduleCriteriaTransfer->getRecurringScheduleConditions();
        if ($conditions !== null) {
            $this->applyConditionsToQuery($query, $conditions);
        }

        $query
            ->useSpyRecurringScheduleItemQuery(null, Criteria::INNER_JOIN)
            ->endUse()
            ->addGroupByColumn(SpyRecurringScheduleTableMap::COL_ID_RECURRING_SCHEDULE)
            ->withColumn($this->getCycleTotalExpression(), RecurringScheduleMapper::VIRTUAL_COL_ESTIMATED_TOTAL)
            ->orderBy(SpyRecurringScheduleTableMap::COL_ID_RECURRING_SCHEDULE, Criteria::ASC);

        $paginationTransfer = $recurringScheduleCriteriaTransfer->getPagination();
        if ($paginationTransfer !== null && $paginationTransfer->getOffset() !== null && $paginationTransfer->getLimit() !== null) {
            $query
                ->offset($paginationTransfer->getOffsetOrFail())
                ->setLimit($paginationTransfer->getLimitOrFail());
        }

        /** @var array<array<string, mixed>> $rows */
        $rows = $query
            ->select([
                SpyRecurringScheduleTableMap::COL_CURRENCY_ISO_CODE,
                SpyRecurringScheduleTableMap::COL_CADENCE_TYPE,
                SpyRecurringScheduleTableMap::COL_CADENCE_VALUE,
                SpyRecurringScheduleTableMap::COL_NEXT_TRIGGER_DATE,
                RecurringScheduleMapper::VIRTUAL_COL_ESTIMATED_TOTAL,
            ])
            ->find()
            ->getData();

        return $this->getFactory()
            ->createRecurringScheduleMapper()
            ->mapRowsToRecurringScheduleForecastTransfers($rows);
    }

    /**
     * @module Sales
     *
     * @return array<\Generated\Shared\Transfer\RecurringScheduleForecastTransfer>
     */
    public function getExecutedRecurringOrderTotals(string $placedFrom, string $placedTo): array
    {
        $query = $this->getFactory()
            ->createRecurringScheduleHistoryQuery()
            ->filterByEventType(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_PLACED)
            ->filterByCreatedAt($placedFrom, Criteria::GREATER_EQUAL)
            ->filterByCreatedAt($placedTo, Criteria::LESS_EQUAL)
            ->useSpySalesOrderQuery(null, Criteria::INNER_JOIN)
                ->useOrderTotalQuery(null, Criteria::INNER_JOIN)
                ->endUse()
            ->endUse()
            ->addAlias(static::ALIAS_NEWER_ORDER_TOTALS, SpySalesOrderTotalsTableMap::TABLE_NAME);

        $query->addJoinObject($this->createNewerOrderTotalsJoin($query->isIdentifierQuotingEnabled()));

        /** @var array<array<string, mixed>> $rows */
        $rows = $query
            ->add($this->getNewerOrderTotalsColumn(SpySalesOrderTotalsTableMap::COL_ID_SALES_ORDER_TOTALS), null, Criteria::ISNULL)
            ->addGroupByColumn(SpySalesOrderTableMap::COL_CURRENCY_ISO_CODE)
            ->withColumn(
                sprintf('SUM(%s)', SpySalesOrderTotalsTableMap::COL_SUBTOTAL),
                RecurringScheduleForecastMapper::VIRTUAL_COL_EXECUTED_TOTAL,
            )
            ->withColumn(
                sprintf('COUNT(DISTINCT %s)', SpySalesOrderTableMap::COL_ID_SALES_ORDER),
                RecurringScheduleForecastMapper::VIRTUAL_COL_EXECUTED_ORDER_COUNT,
            )
            ->select([
                SpySalesOrderTableMap::COL_CURRENCY_ISO_CODE,
                RecurringScheduleForecastMapper::VIRTUAL_COL_EXECUTED_TOTAL,
                RecurringScheduleForecastMapper::VIRTUAL_COL_EXECUTED_ORDER_COUNT,
            ])
            ->find()
            ->getData();

        return $this->getFactory()
            ->createRecurringScheduleForecastMapper()
            ->mapRowsToExecutedRecurringScheduleForecastTransfers($rows);
    }

    /**
     * @module Sales
     */
    protected function createNewerOrderTotalsJoin(bool $isIdentifierQuotingEnabled): Join
    {
        $join = new Join();
        $join->setJoinType(Criteria::LEFT_JOIN);
        $join->setIdentifierQuoting($isIdentifierQuotingEnabled);

        $join->addExplicitCondition(
            SpySalesOrderTotalsTableMap::TABLE_NAME,
            $this->getUnqualifiedColumnName(SpySalesOrderTotalsTableMap::COL_FK_SALES_ORDER),
            null,
            SpySalesOrderTotalsTableMap::TABLE_NAME,
            $this->getUnqualifiedColumnName(SpySalesOrderTotalsTableMap::COL_FK_SALES_ORDER),
            static::ALIAS_NEWER_ORDER_TOTALS,
        );
        $join->addExplicitCondition(
            SpySalesOrderTotalsTableMap::TABLE_NAME,
            $this->getUnqualifiedColumnName(SpySalesOrderTotalsTableMap::COL_ID_SALES_ORDER_TOTALS),
            null,
            SpySalesOrderTotalsTableMap::TABLE_NAME,
            $this->getUnqualifiedColumnName(SpySalesOrderTotalsTableMap::COL_ID_SALES_ORDER_TOTALS),
            static::ALIAS_NEWER_ORDER_TOTALS,
            Criteria::LESS_THAN,
        );

        return $join;
    }

    protected function getNewerOrderTotalsColumn(string $column): string
    {
        return sprintf('%s.%s', static::ALIAS_NEWER_ORDER_TOTALS, $this->getUnqualifiedColumnName($column));
    }

    protected function getUnqualifiedColumnName(string $column): string
    {
        return substr($column, (int)strrpos($column, '.') + 1);
    }

    /**
     * @see \Spryker\Shared\Price\PriceConfig::PRICE_MODE_NET
     */
    protected function getCycleTotalExpression(): string
    {
        return sprintf(
            'SUM(CASE WHEN %s = \'%s\' THEN %s ELSE %s END * COALESCE(%s, %s))',
            SpyRecurringScheduleTableMap::COL_PRICE_MODE,
            static::PRICE_MODE_NET,
            SpyRecurringScheduleItemTableMap::COL_REFERENCE_NET_PRICE,
            SpyRecurringScheduleItemTableMap::COL_REFERENCE_GROSS_PRICE,
            SpyRecurringScheduleItemTableMap::COL_NEXT_DELIVERY_QUANTITY,
            SpyRecurringScheduleItemTableMap::COL_QUANTITY,
        );
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

    /**
     * @return array<int, string>
     */
    public function getRecurringScheduleItemGroupKeysByScheduleId(int $idRecurringSchedule): array
    {
        /** @var array<array<string, string>> $rows */
        $rows = $this->getFactory()
            ->createRecurringScheduleItemQuery()
            ->filterByFkRecurringSchedule($idRecurringSchedule)
            ->filterByGroupKey(null, Criteria::ISNOTNULL)
            ->orderByIdRecurringScheduleItem(Criteria::ASC)
            ->select([
                SpyRecurringScheduleItemTableMap::COL_ID_RECURRING_SCHEDULE_ITEM,
                SpyRecurringScheduleItemTableMap::COL_GROUP_KEY,
            ])
            ->find()
            ->getData();

        $groupKeysByIdRecurringScheduleItem = [];

        foreach ($rows as $row) {
            $idRecurringScheduleItem = (int)$row[SpyRecurringScheduleItemTableMap::COL_ID_RECURRING_SCHEDULE_ITEM];
            $groupKeysByIdRecurringScheduleItem[$idRecurringScheduleItem] = $row[SpyRecurringScheduleItemTableMap::COL_GROUP_KEY];
        }

        return $groupKeysByIdRecurringScheduleItem;
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

        if ($recurringScheduleConditionsTransfer->getCadenceTypes()) {
            $query->filterByCadenceType_In($recurringScheduleConditionsTransfer->getCadenceTypes());
        }

        $this->applyNextTriggerDateConditions($query, $recurringScheduleConditionsTransfer);
        $this->applySearchCondition($query, $recurringScheduleConditionsTransfer);

        if ($recurringScheduleConditionsTransfer->getCompanyIds() || $recurringScheduleConditionsTransfer->getCompanyBusinessUnitIds()) {
            $companyUserQuery = $query->useSpyCompanyUserQuery(null, Criteria::INNER_JOIN);

            if ($recurringScheduleConditionsTransfer->getCompanyIds()) {
                $companyUserQuery->filterByFkCompany_In($recurringScheduleConditionsTransfer->getCompanyIds());
            }

            if ($recurringScheduleConditionsTransfer->getCompanyBusinessUnitIds()) {
                $companyUserQuery->filterByFkCompanyBusinessUnit_In($recurringScheduleConditionsTransfer->getCompanyBusinessUnitIds());
            }

            $companyUserQuery->endUse();
        }

        return $this->applyCompanyDataColumnsToQuery($query, $recurringScheduleConditionsTransfer);
    }

    protected function applyNextTriggerDateConditions(
        SpyRecurringScheduleQuery $query,
        RecurringScheduleConditionsTransfer $recurringScheduleConditionsTransfer
    ): void {
        if ($recurringScheduleConditionsTransfer->getNextTriggerDateFrom()) {
            $query->filterByNextTriggerDate($recurringScheduleConditionsTransfer->getNextTriggerDateFrom(), Criteria::GREATER_EQUAL);
        }

        if ($recurringScheduleConditionsTransfer->getNextTriggerDateTo()) {
            $query->filterByNextTriggerDate($recurringScheduleConditionsTransfer->getNextTriggerDateTo(), Criteria::LESS_EQUAL);
        }
    }

    protected function applyCycleTotalConditions(
        SpyRecurringScheduleQuery $query,
        RecurringScheduleConditionsTransfer $recurringScheduleConditionsTransfer
    ): void {
        $estimatedTotalMin = $recurringScheduleConditionsTransfer->getEstimatedTotalMin();
        $estimatedTotalMax = $recurringScheduleConditionsTransfer->getEstimatedTotalMax();

        if ($estimatedTotalMin === null && $estimatedTotalMax === null) {
            return;
        }

        $query->useSpyRecurringScheduleItemQuery(null, Criteria::INNER_JOIN)
            ->endUse()
            ->addGroupByColumn(SpyRecurringScheduleTableMap::COL_ID_RECURRING_SCHEDULE);

        if ($recurringScheduleConditionsTransfer->getIsWithCompany()) {
            $query->addGroupByColumn(SpyCompanyTableMap::COL_NAME)
                ->addGroupByColumn(SpyCompanyBusinessUnitTableMap::COL_NAME);
        }

        $cycleTotalExpression = $this->getCycleTotalExpression();
        $havingClauses = [];
        $havingValues = [];

        if ($estimatedTotalMin !== null) {
            $havingClauses[] = sprintf('%s >= ?', $cycleTotalExpression);
            $havingValues[] = $estimatedTotalMin;
        }

        if ($estimatedTotalMax !== null) {
            $havingClauses[] = sprintf('%s <= ?', $cycleTotalExpression);
            $havingValues[] = $estimatedTotalMax;
        }

        $query->having(
            implode(' AND ', $havingClauses),
            count($havingValues) === 1 ? $havingValues[0] : $havingValues,
        );
    }

    /**
     * @module Company
     * @module CompanyBusinessUnit
     * @module CompanyUser
     */
    protected function applyCompanyDataColumnsToQuery(
        SpyRecurringScheduleQuery $query,
        RecurringScheduleConditionsTransfer $recurringScheduleConditionsTransfer
    ): SpyRecurringScheduleQuery {
        if (!$recurringScheduleConditionsTransfer->getIsWithCompany()) {
            return $query;
        }

        $query
            ->useSpyCompanyUserQuery(null, Criteria::INNER_JOIN)
                ->joinCompany(null, Criteria::INNER_JOIN)
                ->joinCompanyBusinessUnit(null, Criteria::INNER_JOIN)
            ->endUse()
            ->withColumn(SpyCompanyTableMap::COL_NAME, RecurringScheduleMapper::VIRTUAL_COL_COMPANY_NAME)
            ->withColumn(SpyCompanyBusinessUnitTableMap::COL_NAME, RecurringScheduleMapper::VIRTUAL_COL_BUSINESS_UNIT_NAME);

        return $query;
    }

    /**
     * @param array<int> $scheduleIds
     *
     * @return array<int, string>
     */
    public function getLastExecutionDatesByScheduleIds(array $scheduleIds): array
    {
        if ($scheduleIds === []) {
            return [];
        }

        /** @var array<array<string, mixed>> $rows */
        $rows = $this->getFactory()
            ->createRecurringScheduleHistoryQuery()
            ->filterByFkRecurringSchedule_In($scheduleIds)
            ->filterByEventType(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_PLACED)
            ->addGroupByColumn(SpyRecurringScheduleHistoryTableMap::COL_FK_RECURRING_SCHEDULE)
            ->withColumn(sprintf('MAX(%s)', SpyRecurringScheduleHistoryTableMap::COL_CREATED_AT), static::VIRTUAL_COL_LAST_EXECUTION_DATE)
            ->select([SpyRecurringScheduleHistoryTableMap::COL_FK_RECURRING_SCHEDULE, static::VIRTUAL_COL_LAST_EXECUTION_DATE])
            ->find()
            ->getData();

        $lastExecutionDates = [];

        foreach ($rows as $row) {
            $lastExecutionDates[(int)$row[SpyRecurringScheduleHistoryTableMap::COL_FK_RECURRING_SCHEDULE]] = (string)$row[static::VIRTUAL_COL_LAST_EXECUTION_DATE];
        }

        return $lastExecutionDates;
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
