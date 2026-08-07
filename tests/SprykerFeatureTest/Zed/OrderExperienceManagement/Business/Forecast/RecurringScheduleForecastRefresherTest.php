<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Forecast;

use Codeception\Test\Unit;
use DateTimeImmutable;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleForecastQuery;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\MonthlyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementDependencyProvider;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Forecast
 * @group RecurringScheduleForecastRefresherTest
 * Add your own group annotations below this line
 */
class RecurringScheduleForecastRefresherTest extends Unit
{
    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::PLUGINS_CADENCE_TYPE, [new MonthlyCadenceTypePlugin()]);
        $this->tester->ensureRecurringScheduleTablesAreEmpty();
        $this->tester->ensureRecurringScheduleForecastTableIsEmpty();
    }

    public function testRefreshStoresMonthlyForecastSnapshotForActiveSchedules(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $this->createActiveMonthlySchedule($idCustomer, 'EUR', 2, 500);

        // Act
        $this->refresh();

        // Assert
        $recurringScheduleForecastEntity = SpyRecurringScheduleForecastQuery::create()
            ->filterByForecastKey(OrderExperienceManagementConfig::FORECAST_KEY_MONTHLY)
            ->findOne();

        $this->assertNotNull($recurringScheduleForecastEntity);
        $this->assertSame((new DateTimeImmutable())->format('F Y'), $recurringScheduleForecastEntity->getLabel());
        $this->assertNotEmpty($recurringScheduleForecastEntity->getCalculatedAt());

        /** @var array<string, array<string, mixed>> $forecasts */
        $forecasts = json_decode($recurringScheduleForecastEntity->getResult(), true);
        $this->assertCount(1, $forecasts);
        $this->assertArrayHasKey('EUR', $forecasts);
        $this->assertSame(1000, $forecasts['EUR']['estimatedTotal']);
        $this->assertSame(1, $forecasts['EUR']['scheduleCount']);
    }

    public function testRefreshUpdatesSingleRowOnRepeatedRuns(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $this->createActiveMonthlySchedule($idCustomer, 'EUR', 1, 1000);

        // Act
        $this->refresh();
        $this->refresh();

        // Assert
        $this->assertSame(
            1,
            SpyRecurringScheduleForecastQuery::create()
                ->filterByForecastKey(OrderExperienceManagementConfig::FORECAST_KEY_MONTHLY)
                ->count(),
        );
    }

    public function testRefreshStoresEmptyForecastWhenNoActiveSchedulesExist(): void
    {
        // Act
        $this->refresh();

        // Assert
        $recurringScheduleForecastEntity = SpyRecurringScheduleForecastQuery::create()
            ->filterByForecastKey(OrderExperienceManagementConfig::FORECAST_KEY_MONTHLY)
            ->findOne();

        $this->assertNotNull($recurringScheduleForecastEntity);
        $this->assertSame([], json_decode($recurringScheduleForecastEntity->getResult(), true));
    }

    public function testRefreshCountsSchedulesDueEarlierThisMonthWhenForecastWindowStartsAtMonthStart(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $firstOfMonth = (new DateTimeImmutable('first day of this month'))->format('Y-m-d');
        $this->createActiveMonthlySchedule($idCustomer, 'EUR', 1, 1000, $firstOfMonth);

        // Act
        $this->refresh();

        // Assert
        /** @var array<string, array<string, mixed>> $forecasts */
        $forecasts = json_decode($this->findStoredResult(), true);
        $this->assertArrayHasKey('EUR', $forecasts);
        $this->assertSame(1000, $forecasts['EUR']['estimatedTotal']);
    }

    public function testRefreshExcludesSchedulesDueAfterConfiguredForecastWindowEnd(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $firstOfNextMonth = (new DateTimeImmutable('first day of next month'))->format('Y-m-d');
        $this->createActiveMonthlySchedule($idCustomer, 'EUR', 1, 1000, $firstOfNextMonth);

        // Act
        $this->refresh();

        // Assert
        $this->assertSame([], json_decode($this->findStoredResult(), true));
    }

    protected function refresh(): void
    {
        /** @var \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory $businessFactory */
        $businessFactory = $this->tester->getFactory();

        $businessFactory->createRecurringScheduleForecastRefresher()->refresh();
    }

    protected function findStoredResult(): string
    {
        $recurringScheduleForecastEntity = SpyRecurringScheduleForecastQuery::create()
            ->filterByForecastKey(OrderExperienceManagementConfig::FORECAST_KEY_MONTHLY)
            ->findOne();

        $this->assertNotNull($recurringScheduleForecastEntity);

        return $recurringScheduleForecastEntity->getResult();
    }

    protected function createActiveMonthlySchedule(
        int $idCustomer,
        string $currencyIsoCode,
        int $quantity,
        int $grossPrice,
        ?string $nextTriggerDate = null,
    ): void {
        $firstOfMonth = (new DateTimeImmutable('first day of this month'))->format('Y-m-d');
        $nextTriggerDate ??= (new DateTimeImmutable())->format('Y-m-d');

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
            RecurringScheduleTransfer::CADENCE_TYPE => SharedOrderExperienceManagementConfig::CADENCE_TYPE_MONTHLY,
            RecurringScheduleTransfer::PRICE_MODE => 'GROSS_MODE',
            RecurringScheduleTransfer::CURRENCY_ISO_CODE => $currencyIsoCode,
            RecurringScheduleTransfer::FIRST_TRIGGER_DATE => $firstOfMonth,
            RecurringScheduleTransfer::NEXT_TRIGGER_DATE => $nextTriggerDate,
        ]);

        $this->tester->haveRecurringScheduleItem($recurringScheduleTransfer->getIdRecurringScheduleOrFail(), [
            RecurringScheduleItemTransfer::QUANTITY => $quantity,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => $grossPrice,
        ]);
    }
}
