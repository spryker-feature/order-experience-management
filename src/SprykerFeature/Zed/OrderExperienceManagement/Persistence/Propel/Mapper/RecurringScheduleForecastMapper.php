<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\RecurringScheduleForecastSnapshotTransfer;
use Generated\Shared\Transfer\RecurringScheduleForecastTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleForecast;
use Orm\Zed\Sales\Persistence\Map\SpySalesOrderTableMap;

class RecurringScheduleForecastMapper
{
    public const string VIRTUAL_COL_EXECUTED_TOTAL = 'executed_total';

    public const string VIRTUAL_COL_EXECUTED_ORDER_COUNT = 'executed_order_count';

    /**
     * @param array<array<string, mixed>> $rows
     *
     * @return array<\Generated\Shared\Transfer\RecurringScheduleForecastTransfer>
     */
    public function mapRowsToExecutedRecurringScheduleForecastTransfers(array $rows): array
    {
        $recurringScheduleForecastTransfers = [];

        foreach ($rows as $row) {
            $recurringScheduleForecastTransfers[] = $this->mapRowToExecutedRecurringScheduleForecastTransfer(
                $row,
                new RecurringScheduleForecastTransfer(),
            );
        }

        return $recurringScheduleForecastTransfers;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function mapRowToExecutedRecurringScheduleForecastTransfer(
        array $row,
        RecurringScheduleForecastTransfer $recurringScheduleForecastTransfer,
    ): RecurringScheduleForecastTransfer {
        return $recurringScheduleForecastTransfer
            ->setCurrencyIsoCode((string)$row[SpySalesOrderTableMap::COL_CURRENCY_ISO_CODE])
            ->setExecutedTotal((int)$row[static::VIRTUAL_COL_EXECUTED_TOTAL])
            ->setExecutedOrderCount((int)$row[static::VIRTUAL_COL_EXECUTED_ORDER_COUNT]);
    }

    public function mapRecurringScheduleForecastSnapshotTransferToRecurringScheduleForecastEntity(
        RecurringScheduleForecastSnapshotTransfer $recurringScheduleForecastSnapshotTransfer,
        SpyRecurringScheduleForecast $recurringScheduleForecastEntity,
    ): SpyRecurringScheduleForecast {
        $recurringScheduleForecastEntity->fromArray($recurringScheduleForecastSnapshotTransfer->modifiedToArray());

        return $recurringScheduleForecastEntity;
    }

    public function mapRecurringScheduleForecastEntityToRecurringScheduleForecastSnapshotTransfer(
        SpyRecurringScheduleForecast $recurringScheduleForecastEntity,
        RecurringScheduleForecastSnapshotTransfer $recurringScheduleForecastSnapshotTransfer,
    ): RecurringScheduleForecastSnapshotTransfer {
        return $recurringScheduleForecastSnapshotTransfer->fromArray($recurringScheduleForecastEntity->toArray(), true);
    }
}
