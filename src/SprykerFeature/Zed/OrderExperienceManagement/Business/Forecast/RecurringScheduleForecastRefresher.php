<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Forecast;

use DateTimeImmutable;
use Generated\Shared\Transfer\RecurringScheduleForecastCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleForecastSnapshotTransfer;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;

class RecurringScheduleForecastRefresher implements RecurringScheduleForecastRefresherInterface
{
    protected const string DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    protected const string MONTH_LABEL_FORMAT = 'F Y';

    public function __construct(
        protected readonly MonthlyForecastCalculatorInterface $monthlyForecastCalculator,
        protected readonly OrderExperienceManagementEntityManagerInterface $entityManager,
        protected readonly UtilEncodingServiceInterface $utilEncodingService,
        protected readonly OrderExperienceManagementConfig $config
    ) {
    }

    public function refresh(): void
    {
        $referenceDate = new DateTimeImmutable();
        $recurringScheduleForecastCollectionTransfer = $this->monthlyForecastCalculator->calculate($referenceDate);

        $recurringScheduleForecastSnapshotTransfer = (new RecurringScheduleForecastSnapshotTransfer())
            ->setForecastKey($this->config->getMonthlyForecastKey())
            ->setLabel($referenceDate->format(static::MONTH_LABEL_FORMAT))
            ->setCalculatedAt($referenceDate->format(static::DATE_TIME_FORMAT))
            ->setResult($this->encodeForecasts($recurringScheduleForecastCollectionTransfer));

        $this->entityManager->saveRecurringScheduleForecast($recurringScheduleForecastSnapshotTransfer);
    }

    /**
     * Keyed by currency ISO code — forecasts are unique per currency, so the payload is a string-keyed map.
     */
    protected function encodeForecasts(RecurringScheduleForecastCollectionTransfer $recurringScheduleForecastCollectionTransfer): string
    {
        $forecasts = [];

        foreach ($recurringScheduleForecastCollectionTransfer->getForecasts() as $recurringScheduleForecastTransfer) {
            $forecasts[(string)$recurringScheduleForecastTransfer->getCurrencyIsoCode()] = $recurringScheduleForecastTransfer->toArray(true, true);
        }

        return (string)$this->utilEncodingService->encodeJson($forecasts);
    }
}
