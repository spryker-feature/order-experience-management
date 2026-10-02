<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleDueDataTransfer;
use Generated\Shared\Transfer\RecurringScheduleForecastTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\Map\SpyRecurringScheduleTableMap;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringSchedule;

class RecurringScheduleMapper
{
    /**
     * @var array<string>
     */
    protected const array CADENCE_OPTIONS_ARRAY_KEYS = ['cadence_options', 'cadenceOptions', 'CadenceOptions'];

    public const string VIRTUAL_COL_COMPANY_NAME = 'company_name';

    public const string VIRTUAL_COL_BUSINESS_UNIT_NAME = 'business_unit_name';

    public const string VIRTUAL_COL_ESTIMATED_TOTAL = 'estimated_total';

    public function mapRecurringScheduleTransferToRecurringScheduleEntityColumns(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        SpyRecurringSchedule $recurringScheduleEntity,
    ): SpyRecurringSchedule {
        $recurringScheduleEntity->fromArray(
            $this->withoutCadenceOptions($recurringScheduleTransfer->modifiedToArray()),
        );

        if ($this->isCadenceOptionsModified($recurringScheduleTransfer)) {
            $recurringScheduleEntity->setCadenceOptions(
                $this->encodeCadenceOptions($recurringScheduleTransfer->getCadenceOptions()),
            );
        }

        return $recurringScheduleEntity;
    }

    public function mapRecurringScheduleTransferToRecurringScheduleEntity(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        SpyRecurringSchedule $recurringScheduleEntity,
    ): SpyRecurringSchedule {
        $recurringScheduleEntity = $this->mapRecurringScheduleTransferToRecurringScheduleEntityColumns(
            $recurringScheduleTransfer,
            $recurringScheduleEntity,
        );

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
        $recurringScheduleTransfer->fromArray($this->withoutCadenceOptions($recurringScheduleEntity->toArray()), true);
        $recurringScheduleTransfer->setCadenceOptions($this->decodeCadenceOptions($recurringScheduleEntity->getCadenceOptions()));

        $recurringScheduleTransfer->setIdCustomer($recurringScheduleEntity->getFkCustomer());
        $recurringScheduleTransfer->setIdCompanyUser($recurringScheduleEntity->getFkCompanyUser());
        $recurringScheduleTransfer->setIdSourceSalesOrder($recurringScheduleEntity->getFkSourceSalesOrder());
        $recurringScheduleTransfer->setIdStateMachineItemState($recurringScheduleEntity->getFkStateMachineItemState());

        if ($recurringScheduleEntity->hasVirtualColumn(static::VIRTUAL_COL_COMPANY_NAME)) {
            $recurringScheduleTransfer->setCompanyName($recurringScheduleEntity->getVirtualColumn(static::VIRTUAL_COL_COMPANY_NAME));
        }

        if ($recurringScheduleEntity->hasVirtualColumn(static::VIRTUAL_COL_BUSINESS_UNIT_NAME)) {
            $recurringScheduleTransfer->setBusinessUnitName($recurringScheduleEntity->getVirtualColumn(static::VIRTUAL_COL_BUSINESS_UNIT_NAME));
        }

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
     * @param array<array<string, mixed>> $rows
     *
     * @return array<\Generated\Shared\Transfer\RecurringScheduleForecastTransfer>
     */
    public function mapRowsToRecurringScheduleForecastTransfers(array $rows): array
    {
        $recurringScheduleForecastTransfers = [];

        foreach ($rows as $row) {
            $recurringScheduleForecastTransfers[] = $this->mapRowToRecurringScheduleForecastTransfer(
                $row,
                new RecurringScheduleForecastTransfer(),
            );
        }

        return $recurringScheduleForecastTransfers;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function mapRowToRecurringScheduleForecastTransfer(
        array $row,
        RecurringScheduleForecastTransfer $recurringScheduleForecastTransfer,
    ): RecurringScheduleForecastTransfer {
        $cadenceValue = $row[SpyRecurringScheduleTableMap::COL_CADENCE_VALUE];

        return $recurringScheduleForecastTransfer
            ->setCurrencyIsoCode((string)$row[SpyRecurringScheduleTableMap::COL_CURRENCY_ISO_CODE])
            ->setCadenceType((string)$row[SpyRecurringScheduleTableMap::COL_CADENCE_TYPE])
            ->setCadenceValue($cadenceValue === null ? null : (int)$cadenceValue)
            ->setCadenceOptions($this->decodeCadenceOptions($row[SpyRecurringScheduleTableMap::COL_CADENCE_OPTIONS] ?? null))
            ->setNextTriggerDate((string)$row[SpyRecurringScheduleTableMap::COL_NEXT_TRIGGER_DATE])
            ->setEstimatedTotal((int)$row[static::VIRTUAL_COL_ESTIMATED_TOTAL]);
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

    /**
     * @param array<string, mixed> $recurringScheduleData
     *
     * @return array<string, mixed>
     */
    protected function withoutCadenceOptions(array $recurringScheduleData): array
    {
        foreach (static::CADENCE_OPTIONS_ARRAY_KEYS as $cadenceOptionsArrayKey) {
            unset($recurringScheduleData[$cadenceOptionsArrayKey]);
        }

        return $recurringScheduleData;
    }

    protected function isCadenceOptionsModified(RecurringScheduleTransfer $recurringScheduleTransfer): bool
    {
        $recurringScheduleData = $recurringScheduleTransfer->modifiedToArray();

        foreach (static::CADENCE_OPTIONS_ARRAY_KEYS as $cadenceOptionsArrayKey) {
            if (array_key_exists($cadenceOptionsArrayKey, $recurringScheduleData)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string> $cadenceOptions
     */
    protected function encodeCadenceOptions(array $cadenceOptions): ?string
    {
        if ($cadenceOptions === []) {
            return null;
        }

        return json_encode(array_values($cadenceOptions), JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string>
     */
    protected function decodeCadenceOptions(?string $cadenceOptions): array
    {
        if ($cadenceOptions === null || $cadenceOptions === '') {
            return [];
        }

        $decodedCadenceOptions = json_decode($cadenceOptions, true);

        if (!is_array($decodedCadenceOptions)) {
            return [];
        }

        $stringCadenceOptions = [];

        foreach ($decodedCadenceOptions as $decodedCadenceOption) {
            if (is_array($decodedCadenceOption)) {
                continue;
            }

            $stringCadenceOptions[] = (string)$decodedCadenceOption;
        }

        return $stringCadenceOptions;
    }
}
