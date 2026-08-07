<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Forecast;

use DateTimeImmutable;
use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleForecastCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleForecastTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class MonthlyForecastCalculator implements MonthlyForecastCalculatorInterface
{
    protected const string DATE_FORMAT = 'Y-m-d';

    protected const string PERIOD_START_FORMAT = 'Y-m-d 00:00:00';

    protected const string PERIOD_END_FORMAT = 'Y-m-d 23:59:59';

    protected const int FORECAST_BATCH_SIZE = 500;

    public function __construct(
        protected readonly OrderExperienceManagementRepositoryInterface $repository,
        protected readonly MonthlyOccurrenceCounterInterface $monthlyOccurrenceCounter,
        protected readonly OrderExperienceManagementConfig $config
    ) {
    }

    public function calculate(DateTimeImmutable $referenceDate): RecurringScheduleForecastCollectionTransfer
    {
        $forecastTransfersByCurrency = $this->accumulatePlannedTotals([], $referenceDate);
        $forecastTransfersByCurrency = $this->accumulateExecutedTotals($forecastTransfersByCurrency, $referenceDate);

        return $this->buildForecastCollection($forecastTransfersByCurrency);
    }

    /**
     * Covers the remainder of the month: schedules still due to run, weighted by how often they will run.
     *
     * @param array<string, \Generated\Shared\Transfer\RecurringScheduleForecastTransfer> $forecastTransfersByCurrency
     *
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleForecastTransfer>
     */
    protected function accumulatePlannedTotals(array $forecastTransfersByCurrency, DateTimeImmutable $referenceDate): array
    {
        $recurringScheduleCriteriaTransfer = $this->createActiveScheduleCriteria($referenceDate);
        $offset = 0;

        do {
            $recurringScheduleCriteriaTransfer->setPagination(
                (new PaginationTransfer())->setOffset($offset)->setLimit(static::FORECAST_BATCH_SIZE),
            );

            $recurringScheduleForecastTransfers = $this->repository->getRecurringScheduleForecastData($recurringScheduleCriteriaTransfer);

            foreach ($recurringScheduleForecastTransfers as $recurringScheduleForecastTransfer) {
                $forecastTransfersByCurrency = $this->accumulatePlannedTotal($forecastTransfersByCurrency, $recurringScheduleForecastTransfer, $referenceDate);
            }

            $offset += static::FORECAST_BATCH_SIZE;
        } while (count($recurringScheduleForecastTransfers) === static::FORECAST_BATCH_SIZE);

        return $forecastTransfersByCurrency;
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\RecurringScheduleForecastTransfer> $forecastTransfersByCurrency
     *
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleForecastTransfer>
     */
    protected function accumulatePlannedTotal(
        array $forecastTransfersByCurrency,
        RecurringScheduleForecastTransfer $recurringScheduleForecastTransfer,
        DateTimeImmutable $referenceDate
    ): array {
        $occurrenceCount = $this->monthlyOccurrenceCounter->count(
            $recurringScheduleForecastTransfer->getCadenceType(),
            $recurringScheduleForecastTransfer->getCadenceValue(),
            $recurringScheduleForecastTransfer->getNextTriggerDate(),
            $referenceDate,
        );

        if ($occurrenceCount === 0) {
            return $forecastTransfersByCurrency;
        }

        $currencyIsoCode = (string)$recurringScheduleForecastTransfer->getCurrencyIsoCode();
        $forecastTransfer = $forecastTransfersByCurrency[$currencyIsoCode] ?? $this->createForecastTransfer($currencyIsoCode);

        $forecastTransfer
            ->setPlannedTotal((int)$forecastTransfer->getPlannedTotal() + $occurrenceCount * (int)$recurringScheduleForecastTransfer->getEstimatedTotal())
            ->setScheduleCount((int)$forecastTransfer->getScheduleCount() + 1);

        $forecastTransfersByCurrency[$currencyIsoCode] = $forecastTransfer;

        return $forecastTransfersByCurrency;
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\RecurringScheduleForecastTransfer> $forecastTransfersByCurrency
     *
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleForecastTransfer>
     */
    protected function accumulateExecutedTotals(array $forecastTransfersByCurrency, DateTimeImmutable $referenceDate): array
    {
        $executedForecastTransfers = $this->repository->getExecutedRecurringOrderTotals(
            $referenceDate->modify($this->config->getForecastPeriodFrom())->format(static::PERIOD_START_FORMAT),
            $referenceDate->modify($this->config->getForecastPeriodTo())->format(static::PERIOD_END_FORMAT),
        );

        foreach ($executedForecastTransfers as $executedForecastTransfer) {
            $currencyIsoCode = (string)$executedForecastTransfer->getCurrencyIsoCode();
            $forecastTransfer = $forecastTransfersByCurrency[$currencyIsoCode] ?? $this->createForecastTransfer($currencyIsoCode);

            $forecastTransfer
                ->setExecutedTotal((int)$forecastTransfer->getExecutedTotal() + (int)$executedForecastTransfer->getExecutedTotal())
                ->setExecutedOrderCount((int)$forecastTransfer->getExecutedOrderCount() + (int)$executedForecastTransfer->getExecutedOrderCount());

            $forecastTransfersByCurrency[$currencyIsoCode] = $forecastTransfer;
        }

        return $forecastTransfersByCurrency;
    }

    protected function createForecastTransfer(string $currencyIsoCode): RecurringScheduleForecastTransfer
    {
        return (new RecurringScheduleForecastTransfer())
            ->setCurrencyIsoCode($currencyIsoCode)
            ->setEstimatedTotal(0)
            ->setPlannedTotal(0)
            ->setExecutedTotal(0)
            ->setExecutedOrderCount(0)
            ->setScheduleCount(0);
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\RecurringScheduleForecastTransfer> $forecastTransfersByCurrency
     */
    protected function buildForecastCollection(array $forecastTransfersByCurrency): RecurringScheduleForecastCollectionTransfer
    {
        $recurringScheduleForecastCollectionTransfer = new RecurringScheduleForecastCollectionTransfer();

        foreach ($forecastTransfersByCurrency as $forecastTransfer) {
            $forecastTransfer->setEstimatedTotal(
                (int)$forecastTransfer->getPlannedTotal() + (int)$forecastTransfer->getExecutedTotal(),
            );

            $recurringScheduleForecastCollectionTransfer->addForecast($forecastTransfer);
        }

        return $recurringScheduleForecastCollectionTransfer;
    }

    protected function createActiveScheduleCriteria(DateTimeImmutable $referenceDate): RecurringScheduleCriteriaTransfer
    {
        $recurringScheduleConditionsTransfer = (new RecurringScheduleConditionsTransfer())
            ->addStatus(SharedOrderExperienceManagementConfig::STATUS_ACTIVE)
            ->setNextTriggerDateFrom($referenceDate->modify($this->config->getForecastPeriodFrom())->format(static::DATE_FORMAT))
            ->setNextTriggerDateTo($referenceDate->modify($this->config->getForecastPeriodTo())->format(static::DATE_FORMAT));

        return (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions($recurringScheduleConditionsTransfer);
    }
}
