<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Forecast;

use Generated\Shared\Transfer\RecurringScheduleForecastCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleForecastTransfer;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class RecurringScheduleForecastReader implements RecurringScheduleForecastReaderInterface
{
    public function __construct(
        protected readonly OrderExperienceManagementRepositoryInterface $repository,
        protected readonly UtilEncodingServiceInterface $utilEncodingService,
        protected readonly OrderExperienceManagementConfig $config
    ) {
    }

    public function getMonthlyForecastCollection(): RecurringScheduleForecastCollectionTransfer
    {
        $recurringScheduleForecastCollectionTransfer = new RecurringScheduleForecastCollectionTransfer();

        $recurringScheduleForecastSnapshotTransfer = $this->repository->findRecurringScheduleForecastSnapshot(
            $this->config->getMonthlyForecastKey(),
        );

        if ($recurringScheduleForecastSnapshotTransfer === null) {
            return $recurringScheduleForecastCollectionTransfer;
        }

        $recurringScheduleForecastCollectionTransfer
            ->setLabel($recurringScheduleForecastSnapshotTransfer->getLabel())
            ->setCalculatedAt($recurringScheduleForecastSnapshotTransfer->getCalculatedAt());

        return $this->addForecastsFromResult(
            $recurringScheduleForecastCollectionTransfer,
            $recurringScheduleForecastSnapshotTransfer->getResult(),
        );
    }

    protected function addForecastsFromResult(
        RecurringScheduleForecastCollectionTransfer $recurringScheduleForecastCollectionTransfer,
        ?string $result
    ): RecurringScheduleForecastCollectionTransfer {
        if ($result === null || $result === '') {
            return $recurringScheduleForecastCollectionTransfer;
        }

        /** @var array<array<string, mixed>> $forecasts */
        $forecasts = (array)$this->utilEncodingService->decodeJson($result, true);

        foreach ($forecasts as $forecast) {
            $recurringScheduleForecastCollectionTransfer->addForecast(
                (new RecurringScheduleForecastTransfer())->fromArray($forecast, true),
            );
        }

        return $recurringScheduleForecastCollectionTransfer;
    }
}
