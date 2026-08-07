<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Forecast;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\RecurringScheduleForecastCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleForecastTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Forecast
 * @group RecurringScheduleForecastReaderTest
 * Add your own group annotations below this line
 */
class RecurringScheduleForecastReaderTest extends Unit
{
    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->ensureRecurringScheduleForecastTableIsEmpty();
    }

    public function testGetMonthlyForecastCollectionReturnsStoredSnapshot(): void
    {
        // Arrange
        $this->tester->haveRecurringScheduleForecast(
            OrderExperienceManagementConfig::FORECAST_KEY_MONTHLY,
            (string)json_encode([
                ['currencyIsoCode' => 'EUR', 'estimatedTotal' => 1000, 'scheduleCount' => 2],
                ['currencyIsoCode' => 'USD', 'estimatedTotal' => 500, 'scheduleCount' => 1],
            ], JSON_THROW_ON_ERROR),
            'July 2026',
            '2026-07-01 10:00:00',
        );

        // Act
        $recurringScheduleForecastCollectionTransfer = $this->getMonthlyForecastCollection();

        // Assert
        $this->assertSame('July 2026', $recurringScheduleForecastCollectionTransfer->getLabel());
        $this->assertStringStartsWith('2026-07-01 10:00:00', (string)$recurringScheduleForecastCollectionTransfer->getCalculatedAt());
        $this->assertCount(2, $recurringScheduleForecastCollectionTransfer->getForecasts());

        $forecastTransfer = $this->findForecastByCurrency($recurringScheduleForecastCollectionTransfer->getForecasts(), 'EUR');
        $this->assertNotNull($forecastTransfer);
        $this->assertSame(1000, $forecastTransfer->getEstimatedTotal());
        $this->assertSame(2, $forecastTransfer->getScheduleCount());
    }

    public function testGetMonthlyForecastCollectionReturnsEmptyCollectionWhenNoSnapshotExists(): void
    {
        // Act
        $recurringScheduleForecastCollectionTransfer = $this->getMonthlyForecastCollection();

        // Assert
        $this->assertNull($recurringScheduleForecastCollectionTransfer->getLabel());
        $this->assertCount(0, $recurringScheduleForecastCollectionTransfer->getForecasts());
    }

    public function testGetMonthlyForecastCollectionReturnsLabelWithoutForecastsWhenResultIsEmpty(): void
    {
        // Arrange
        $this->tester->haveRecurringScheduleForecast(
            OrderExperienceManagementConfig::FORECAST_KEY_MONTHLY,
            '[]',
            'July 2026',
        );

        // Act
        $recurringScheduleForecastCollectionTransfer = $this->getMonthlyForecastCollection();

        // Assert
        $this->assertSame('July 2026', $recurringScheduleForecastCollectionTransfer->getLabel());
        $this->assertCount(0, $recurringScheduleForecastCollectionTransfer->getForecasts());
    }

    protected function getMonthlyForecastCollection(): RecurringScheduleForecastCollectionTransfer
    {
        /** @var \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory $businessFactory */
        $businessFactory = $this->tester->getFactory();

        return $businessFactory->createRecurringScheduleForecastReader()->getMonthlyForecastCollection();
    }

    /**
     * @param iterable<\Generated\Shared\Transfer\RecurringScheduleForecastTransfer> $forecastTransfers
     */
    protected function findForecastByCurrency(iterable $forecastTransfers, string $currencyIsoCode): ?RecurringScheduleForecastTransfer
    {
        foreach ($forecastTransfers as $forecastTransfer) {
            if ($forecastTransfer->getCurrencyIsoCode() === $currencyIsoCode) {
                return $forecastTransfer;
            }
        }

        return null;
    }
}
