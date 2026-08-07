<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Forecast;

use Codeception\Test\Unit;
use DateTimeImmutable;
use Generated\Shared\Transfer\RecurringScheduleForecastCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleForecastTransfer;
use Generated\Shared\Transfer\RecurringScheduleHistoryTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleItemQuery;
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
 * @group MonthlyForecastCalculatorTest
 * Add your own group annotations below this line
 */
class MonthlyForecastCalculatorTest extends Unit
{
    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::PLUGINS_CADENCE_TYPE, [new MonthlyCadenceTypePlugin()]);
        $this->tester->ensureRecurringScheduleTablesAreEmpty();
    }

    public function testForecastSumsActiveMonthlySchedulePerCycleValueOncePerMonth(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $this->createActiveMonthlySchedule($idCustomer, 'EUR', 2, 500);

        // Act
        $forecastCollectionTransfer = $this->calculateForecast();

        // Assert
        $this->assertCount(1, $forecastCollectionTransfer->getForecasts());
        $forecastTransfer = $this->findForecastByCurrency($forecastCollectionTransfer->getForecasts(), 'EUR');
        $this->assertNotNull($forecastTransfer);
        $this->assertSame(1000, $forecastTransfer->getEstimatedTotal());
        $this->assertSame(1, $forecastTransfer->getScheduleCount());
    }

    public function testForecastExcludesNonActiveSchedules(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $this->createActiveMonthlySchedule($idCustomer, 'EUR', 1, 1000);

        $pausedScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_PAUSED,
            RecurringScheduleTransfer::CADENCE_TYPE => SharedOrderExperienceManagementConfig::CADENCE_TYPE_MONTHLY,
            RecurringScheduleTransfer::PRICE_MODE => 'GROSS_MODE',
            RecurringScheduleTransfer::CURRENCY_ISO_CODE => 'EUR',
        ]);
        $this->tester->haveRecurringScheduleItem($pausedScheduleTransfer->getIdRecurringScheduleOrFail(), [
            RecurringScheduleItemTransfer::QUANTITY => 1,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 9999,
        ]);

        // Act
        $forecastCollectionTransfer = $this->calculateForecast();

        // Assert
        $this->assertCount(1, $forecastCollectionTransfer->getForecasts());
        $this->assertSame(1000, $forecastCollectionTransfer->getForecasts()->offsetGet(0)->getEstimatedTotal());
    }

    public function testForecastGroupsTotalsByCurrency(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $this->createActiveMonthlySchedule($idCustomer, 'EUR', 1, 1000);
        $this->createActiveMonthlySchedule($idCustomer, 'USD', 1, 500);

        // Act
        $forecastCollectionTransfer = $this->calculateForecast();

        // Assert
        $this->assertCount(2, $forecastCollectionTransfer->getForecasts());
        $this->assertSame(1000, $this->findForecastByCurrency($forecastCollectionTransfer->getForecasts(), 'EUR')?->getEstimatedTotal());
        $this->assertSame(500, $this->findForecastByCurrency($forecastCollectionTransfer->getForecasts(), 'USD')?->getEstimatedTotal());
    }

    public function testForecastIsEmptyWhenNoActiveSchedulesExist(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_CANCELLED,
            RecurringScheduleTransfer::CADENCE_TYPE => SharedOrderExperienceManagementConfig::CADENCE_TYPE_MONTHLY,
        ]);

        // Act
        $forecastCollectionTransfer = $this->calculateForecast();

        // Assert
        $this->assertCount(0, $forecastCollectionTransfer->getForecasts());
    }

    public function testForecastExcludesActiveSchedulesNotDueWithinCurrentMonth(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $firstOfNextMonth = (new DateTimeImmutable('first day of next month'))->format('Y-m-d');

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
            RecurringScheduleTransfer::CADENCE_TYPE => SharedOrderExperienceManagementConfig::CADENCE_TYPE_MONTHLY,
            RecurringScheduleTransfer::PRICE_MODE => 'GROSS_MODE',
            RecurringScheduleTransfer::CURRENCY_ISO_CODE => 'EUR',
            RecurringScheduleTransfer::FIRST_TRIGGER_DATE => $firstOfNextMonth,
            RecurringScheduleTransfer::NEXT_TRIGGER_DATE => $firstOfNextMonth,
        ]);
        $this->tester->haveRecurringScheduleItem($recurringScheduleTransfer->getIdRecurringScheduleOrFail(), [
            RecurringScheduleItemTransfer::QUANTITY => 1,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 1000,
        ]);

        // Act
        $forecastCollectionTransfer = $this->calculateForecast();

        // Assert
        $this->assertCount(0, $forecastCollectionTransfer->getForecasts());
    }

    public function testForecastUsesNetPriceAndNextDeliveryQuantityWhenPresent(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $today = (new DateTimeImmutable())->format('Y-m-d');

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
            RecurringScheduleTransfer::CADENCE_TYPE => SharedOrderExperienceManagementConfig::CADENCE_TYPE_MONTHLY,
            RecurringScheduleTransfer::PRICE_MODE => 'NET_MODE',
            RecurringScheduleTransfer::CURRENCY_ISO_CODE => 'EUR',
            RecurringScheduleTransfer::FIRST_TRIGGER_DATE => $today,
            RecurringScheduleTransfer::NEXT_TRIGGER_DATE => $today,
        ]);
        $recurringScheduleItemTransfer = $this->tester->haveRecurringScheduleItem($recurringScheduleTransfer->getIdRecurringScheduleOrFail(), [
            RecurringScheduleItemTransfer::QUANTITY => 5,
            RecurringScheduleItemTransfer::REFERENCE_NET_PRICE => 300,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 999,
        ]);
        $recurringScheduleItemEntity = SpyRecurringScheduleItemQuery::create()
            ->findOneByIdRecurringScheduleItem($recurringScheduleItemTransfer->getIdRecurringScheduleItemOrFail());
        $recurringScheduleItemEntity->setNextDeliveryQuantity(2)->save();

        // Act
        $forecastCollectionTransfer = $this->calculateForecast();

        // Assert
        $forecastTransfer = $this->findForecastByCurrency($forecastCollectionTransfer->getForecasts(), 'EUR');
        $this->assertNotNull($forecastTransfer);
        $this->assertSame(600, $forecastTransfer->getEstimatedTotal());
    }

    public function testForecastAddsExecutedOrderSubtotalsPlacedWithinCurrentMonth(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $this->createActiveMonthlySchedule($idCustomer, 'EUR', 2, 500);
        $this->havePlacedOrderForNewSchedule($idCustomer, 'EUR', 700, (new DateTimeImmutable('first day of this month'))->format('Y-m-d H:i:s'));

        // Act
        $forecastCollectionTransfer = $this->calculateForecast();

        // Assert
        $forecastTransfer = $this->findForecastByCurrency($forecastCollectionTransfer->getForecasts(), 'EUR');
        $this->assertNotNull($forecastTransfer);
        $this->assertSame(1000, $forecastTransfer->getPlannedTotal());
        $this->assertSame(700, $forecastTransfer->getExecutedTotal());
        $this->assertSame(1, $forecastTransfer->getExecutedOrderCount());
        $this->assertSame(1700, $forecastTransfer->getEstimatedTotal());
    }

    public function testForecastCountsExecutedOrderSubtotalOncePerOrderDespiteMultipleTotalsRevisions(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $this->havePlacedOrderForNewSchedule(
            $idCustomer,
            'EUR',
            700,
            (new DateTimeImmutable('first day of this month'))->format('Y-m-d H:i:s'),
            4,
        );

        // Act
        $forecastCollectionTransfer = $this->calculateForecast();

        // Assert
        $forecastTransfer = $this->findForecastByCurrency($forecastCollectionTransfer->getForecasts(), 'EUR');
        $this->assertNotNull($forecastTransfer);
        $this->assertSame(700, $forecastTransfer->getExecutedTotal());
        $this->assertSame(1, $forecastTransfer->getExecutedOrderCount());
    }

    public function testForecastExcludesExecutedOrdersPlacedOutsideCurrentMonth(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $this->havePlacedOrderForNewSchedule(
            $idCustomer,
            'EUR',
            700,
            (new DateTimeImmutable('last day of previous month'))->format('Y-m-d 23:59:59'),
        );

        // Act
        $forecastCollectionTransfer = $this->calculateForecast();

        // Assert
        $this->assertCount(0, $forecastCollectionTransfer->getForecasts());
    }

    public function testForecastExcludesNonPlacedHistoryEventsFromExecutedTotals(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $this->havePlacedOrderForNewSchedule(
            $idCustomer,
            'EUR',
            700,
            (new DateTimeImmutable('first day of this month'))->format('Y-m-d H:i:s'),
            1,
            SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_FAILED,
        );

        // Act
        $forecastCollectionTransfer = $this->calculateForecast();

        // Assert
        $this->assertCount(0, $forecastCollectionTransfer->getForecasts());
    }

    public function testForecastNarrowsBothPlannedAndExecutedSidesWithTheSamePeriodConfig(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('getForecastPeriodFrom', OrderExperienceManagementConfig::FORECAST_PERIOD_FROM_TODAY);
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();

        // Mid-month reference so "today" always has earlier in-month dates to exclude, whatever the real date is.
        $referenceDate = new DateTimeImmutable((new DateTimeImmutable('first day of this month'))->format('Y-m-15'));
        $firstOfMonth = (new DateTimeImmutable('first day of this month'));

        // Both fall before the reference date, so narrowing the shared window to "today" must drop both sides.
        $this->createActiveMonthlySchedule($idCustomer, 'EUR', 2, 500, $firstOfMonth->format('Y-m-d'));
        $this->havePlacedOrderForNewSchedule($idCustomer, 'EUR', 700, $firstOfMonth->format('Y-m-d 00:00:00'));

        // Act
        $forecastCollectionTransfer = $this->calculateForecast($referenceDate);

        // Assert
        $this->assertCount(0, $forecastCollectionTransfer->getForecasts());
    }

    protected function havePlacedOrderForNewSchedule(
        int $idCustomer,
        string $currencyIsoCode,
        int $subtotal,
        string $placedAt,
        int $totalsRevisionCount = 1,
        string $eventType = SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_PLACED
    ): void {
        $firstOfNextMonth = (new DateTimeImmutable('first day of next month'))->format('Y-m-d');

        // Due next month, so the schedule contributes nothing to the planned side and only the placed order counts.
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
            RecurringScheduleTransfer::CADENCE_TYPE => SharedOrderExperienceManagementConfig::CADENCE_TYPE_MONTHLY,
            RecurringScheduleTransfer::CURRENCY_ISO_CODE => $currencyIsoCode,
            RecurringScheduleTransfer::FIRST_TRIGGER_DATE => $firstOfNextMonth,
            RecurringScheduleTransfer::NEXT_TRIGGER_DATE => $firstOfNextMonth,
        ]);

        $this->tester->haveRecurringScheduleHistory($recurringScheduleTransfer->getIdRecurringScheduleOrFail(), [
            RecurringScheduleHistoryTransfer::ID_SALES_ORDER => $this->tester->haveSalesOrderWithTotals($currencyIsoCode, $subtotal, $totalsRevisionCount),
            RecurringScheduleHistoryTransfer::EVENT_TYPE => $eventType,
            RecurringScheduleHistoryTransfer::CREATED_AT => $placedAt,
        ]);
    }

    protected function calculateForecast(?DateTimeImmutable $referenceDate = null): RecurringScheduleForecastCollectionTransfer
    {
        /** @var \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory $businessFactory */
        $businessFactory = $this->tester->getFactory();

        return $businessFactory->createMonthlyForecastCalculator()->calculate($referenceDate ?? new DateTimeImmutable());
    }

    protected function createActiveMonthlySchedule(
        int $idCustomer,
        string $currencyIsoCode,
        int $quantity,
        int $grossPrice,
        ?string $nextTriggerDate = null
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

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\RecurringScheduleForecastTransfer>|iterable $forecastTransfers
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
