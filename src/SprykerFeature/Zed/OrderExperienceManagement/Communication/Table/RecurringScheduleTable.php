<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Table;

use ArrayObject;
use DateTime;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleTableFilterTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\SortTransfer;
use Spryker\Service\UtilText\Model\Url\Url;
use Spryker\Zed\Gui\Communication\Table\AbstractTable;
use Spryker\Zed\Gui\Communication\Table\TableConfiguration;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface;

class RecurringScheduleTable extends AbstractTable
{
    protected const string COL_NAME = 'name';

    protected const string COL_COMPANY = 'company';

    protected const string COL_BUSINESS_UNIT = 'business_unit';

    protected const string COL_OWNER = 'owner';

    protected const string COL_STATUS = 'schedule_status';

    protected const string COL_FREQUENCY = 'frequency';

    protected const string COL_CYCLE_TOTAL = 'cycle_total';

    protected const string COL_NEXT_TRIGGER_DATE = 'next_trigger_date';

    protected const string COL_LAST_EXECUTION_DATE = 'last_execution_date';

    protected const string COL_ACTIONS = 'actions';

    /**
     * The Gui table template prepends the current `/module/controller/`, so this is the action-relative path only.
     *
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller\RecurringScheduleController::tableAction()
     */
    protected const string URL_TABLE = '/table';

    /**
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller\RecurringScheduleController::viewAction()
     */
    protected const string URL_VIEW = '/order-experience-management/recurring-schedule/view';

    protected const string PARAM_ID_RECURRING_SCHEDULE = 'id-recurring-schedule';

    protected const string DATE_FORMAT = 'Y-m-d';

    protected const int MONEY_DIVISOR = 100;

    protected const array SORTABLE_COLUMN_TO_SORT_FIELD = [
        self::COL_NAME => 'spy_recurring_schedule.name',
        self::COL_STATUS => 'spy_recurring_schedule.status',
        self::COL_FREQUENCY => 'spy_recurring_schedule.cadence_type',
        self::COL_NEXT_TRIGGER_DATE => 'spy_recurring_schedule.next_trigger_date',
    ];

    protected const array STATUS_TO_LABEL_CLASS = [
        SharedOrderExperienceManagementConfig::STATUS_ACTIVE => 'label-info',
        SharedOrderExperienceManagementConfig::STATUS_PAUSED => 'label-warning',
        SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED => 'label-warning',
        SharedOrderExperienceManagementConfig::STATUS_CANCELLED => 'label-danger',
        SharedOrderExperienceManagementConfig::STATUS_FAILED => 'label-danger',
        SharedOrderExperienceManagementConfig::STATUS_DRAFT => 'label-default',
    ];

    protected RecurringScheduleTableFilterTransfer $recurringScheduleTableFilterTransfer;

    public function __construct(protected readonly OrderExperienceManagementFacadeInterface $orderExperienceManagementFacade)
    {
        $this->recurringScheduleTableFilterTransfer = new RecurringScheduleTableFilterTransfer();
    }

    public function applyCriteria(?RecurringScheduleTableFilterTransfer $recurringScheduleTableFilterTransfer): void
    {
        $this->recurringScheduleTableFilterTransfer = $recurringScheduleTableFilterTransfer ?? new RecurringScheduleTableFilterTransfer();
    }

    protected function configure(TableConfiguration $config): TableConfiguration
    {
        $config->setHeader([
            static::COL_NAME => 'Name',
            static::COL_COMPANY => 'Company',
            static::COL_BUSINESS_UNIT => 'Business Unit',
            static::COL_OWNER => 'Owner',
            static::COL_STATUS => 'Status',
            static::COL_FREQUENCY => 'Frequency',
            static::COL_CYCLE_TOTAL => 'Cycle Total',
            static::COL_NEXT_TRIGGER_DATE => 'Next Trigger Date',
            static::COL_LAST_EXECUTION_DATE => 'Last Execution',
            static::COL_ACTIONS => 'Actions',
        ]);

        $config->setRawColumns([
            static::COL_STATUS,
            static::COL_ACTIONS,
        ]);

        $config->setSortable([
            static::COL_NAME,
            static::COL_STATUS,
            static::COL_FREQUENCY,
            static::COL_NEXT_TRIGGER_DATE,
        ]);

        $config->setSearchable([
            static::COL_NAME,
        ]);

        $config->setDefaultSortField(static::COL_NEXT_TRIGGER_DATE, TableConfiguration::SORT_ASC);
        $config->setUrl($this->getTableUrl());

        return $config;
    }

    protected function getTableUrl(): string
    {
        return Url::generate(static::URL_TABLE, $this->getRequest()->query->all())->build();
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function prepareData(TableConfiguration $config): array
    {
        $recurringScheduleCriteriaTransfer = $this->buildCriteria($config);
        $recurringScheduleCollectionTransfer = $this->orderExperienceManagementFacade->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);

        $total = $recurringScheduleCollectionTransfer->getPaginationOrFail()->getNbResults() ?? 0;
        $this->setTotal($total);
        $this->setFiltered($total);

        $rows = [];

        foreach ($recurringScheduleCollectionTransfer->getRecurringSchedules() as $recurringScheduleTransfer) {
            $rows[] = $this->formatRow($recurringScheduleTransfer);
        }

        return $rows;
    }

    protected function buildCriteria(TableConfiguration $config): RecurringScheduleCriteriaTransfer
    {
        $recurringScheduleConditionsTransfer = (new RecurringScheduleConditionsTransfer())
            ->setIsWithItems(true)
            ->setIsWithCustomer(true)
            ->setIsWithCompany(true)
            ->setIsWithLastExecution(true);

        $this->applyFilters($recurringScheduleConditionsTransfer);
        $this->applySearch($recurringScheduleConditionsTransfer);

        $paginationTransfer = (new PaginationTransfer())
            ->setOffset($this->getOffset())
            ->setLimit($this->getLimit());

        return (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions($recurringScheduleConditionsTransfer)
            ->setPagination($paginationTransfer)
            ->setSortCollection($this->buildSortCollection($config));
    }

    protected function applyFilters(RecurringScheduleConditionsTransfer $recurringScheduleConditionsTransfer): void
    {
        $recurringScheduleTableFilterTransfer = $this->recurringScheduleTableFilterTransfer;

        if ($recurringScheduleTableFilterTransfer->getIdCompany() !== null) {
            $recurringScheduleConditionsTransfer->addCompanyId($recurringScheduleTableFilterTransfer->getIdCompany());
        }

        if ($recurringScheduleTableFilterTransfer->getIdCompanyBusinessUnit() !== null) {
            $recurringScheduleConditionsTransfer->addCompanyBusinessUnitId($recurringScheduleTableFilterTransfer->getIdCompanyBusinessUnit());
        }

        if ($recurringScheduleTableFilterTransfer->getStatuses()) {
            $recurringScheduleConditionsTransfer->setStatuses($recurringScheduleTableFilterTransfer->getStatuses());
        }

        if ($recurringScheduleTableFilterTransfer->getCadenceTypes()) {
            $recurringScheduleConditionsTransfer->setCadenceTypes($recurringScheduleTableFilterTransfer->getCadenceTypes());
        }

        $recurringScheduleConditionsTransfer
            ->setEstimatedTotalMin($recurringScheduleTableFilterTransfer->getCycleTotalFrom())
            ->setEstimatedTotalMax($recurringScheduleTableFilterTransfer->getCycleTotalTo())
            ->setNextTriggerDateFrom($recurringScheduleTableFilterTransfer->getNextTriggerDateFrom())
            ->setNextTriggerDateTo($recurringScheduleTableFilterTransfer->getNextTriggerDateTo());
    }

    protected function applySearch(RecurringScheduleConditionsTransfer $recurringScheduleConditionsTransfer): void
    {
        $searchTerm = $this->getSearchTerm();

        if (is_array($searchTerm) && ($searchTerm['value'] ?? '') !== '') {
            $recurringScheduleConditionsTransfer->addName($searchTerm['value']);
        }
    }

    /**
     * @return \ArrayObject<int, \Generated\Shared\Transfer\SortTransfer>
     */
    protected function buildSortCollection(TableConfiguration $config): ArrayObject
    {
        /** @var \ArrayObject<int, \Generated\Shared\Transfer\SortTransfer> $sortCollection */
        $sortCollection = new ArrayObject();
        $headerColumns = array_keys($config->getHeader());

        foreach ($this->getOrders($config) as $order) {
            $columnIndex = $order[static::SORT_BY_COLUMN] ?? null;
            $columnName = $columnIndex !== null ? ($headerColumns[$columnIndex] ?? null) : null;

            if ($columnName === null || !isset(static::SORTABLE_COLUMN_TO_SORT_FIELD[$columnName])) {
                continue;
            }

            $isAscending = strtolower((string)($order[static::SORT_BY_DIRECTION] ?? TableConfiguration::SORT_ASC)) === strtolower(TableConfiguration::SORT_ASC);

            $sortCollection->append(
                (new SortTransfer())
                    ->setField(static::SORTABLE_COLUMN_TO_SORT_FIELD[$columnName])
                    ->setIsAscending($isAscending),
            );
        }

        return $sortCollection;
    }

    /**
     * @return array<string, string>
     */
    protected function formatRow(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        return [
            static::COL_NAME => $recurringScheduleTransfer->getName() ?? '',
            static::COL_COMPANY => $recurringScheduleTransfer->getCompanyName() ?? '',
            static::COL_BUSINESS_UNIT => $recurringScheduleTransfer->getBusinessUnitName() ?? '',
            static::COL_OWNER => $recurringScheduleTransfer->getCreatedByName() ?? '',
            static::COL_STATUS => $this->formatStatus($recurringScheduleTransfer->getStatus()),
            static::COL_FREQUENCY => $this->formatFrequency($recurringScheduleTransfer),
            static::COL_CYCLE_TOTAL => $this->formatCycleTotal($recurringScheduleTransfer),
            static::COL_NEXT_TRIGGER_DATE => $this->formatDate($recurringScheduleTransfer->getNextTriggerDate(), static::DATE_FORMAT),
            static::COL_LAST_EXECUTION_DATE => $this->formatDate($recurringScheduleTransfer->getLastExecutionDate(), static::DATE_FORMAT),
            static::COL_ACTIONS => $this->buildLinks($recurringScheduleTransfer),
        ];
    }

    protected function formatStatus(?string $status): string
    {
        if ($status === null) {
            return '';
        }

        $labelClass = static::STATUS_TO_LABEL_CLASS[$status] ?? 'label-default';

        return $this->generateLabel(ucfirst(str_replace('_', ' ', $status)), $labelClass);
    }

    protected function formatFrequency(RecurringScheduleTransfer $recurringScheduleTransfer): string
    {
        $cadenceType = $recurringScheduleTransfer->getCadenceType();

        if ($cadenceType === null) {
            return '';
        }

        $cadenceValue = $recurringScheduleTransfer->getCadenceValue();
        $cadenceLabel = ucfirst(str_replace('_', ' ', $cadenceType));

        if ($cadenceValue !== null) {
            return sprintf('%s (%d)', $cadenceLabel, $cadenceValue);
        }

        return $cadenceLabel;
    }

    protected function formatCycleTotal(RecurringScheduleTransfer $recurringScheduleTransfer): string
    {
        $estimatedTotal = $recurringScheduleTransfer->getEstimatedTotal();

        if ($estimatedTotal === null) {
            return '';
        }

        return sprintf(
            '%s %s',
            number_format($estimatedTotal / static::MONEY_DIVISOR, 2),
            $recurringScheduleTransfer->getCurrencyIsoCode() ?? '',
        );
    }

    protected function formatDate(?string $date, string $format): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        return (new DateTime($date))->format($format);
    }

    protected function buildLinks(RecurringScheduleTransfer $recurringScheduleTransfer): string
    {
        return $this->generateViewButton(
            Url::generate(static::URL_VIEW, [
                static::PARAM_ID_RECURRING_SCHEDULE => $recurringScheduleTransfer->getIdRecurringSchedule(),
            ])->build(),
            'View',
        );
    }
}
