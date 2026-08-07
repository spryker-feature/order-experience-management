<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Forecast;

use Codeception\Test\Unit;
use DateTimeImmutable;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Forecast\MonthlyOccurrenceCounter;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\BiWeeklyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\EveryNWeeksCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\MonthlyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\WeeklyCadenceTypePlugin;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Forecast
 * @group MonthlyOccurrenceCounterTest
 * Add your own group annotations below this line
 */
class MonthlyOccurrenceCounterTest extends Unit
{
    /**
     * July 2026 has 31 days and starts on a Wednesday, giving deterministic weekly counts.
     */
    protected const string REFERENCE_DATE = '2026-07-15';

    protected function createCounter(): MonthlyOccurrenceCounter
    {
        return new MonthlyOccurrenceCounter(new CadenceResolver([
            new WeeklyCadenceTypePlugin(),
            new BiWeeklyCadenceTypePlugin(),
            new MonthlyCadenceTypePlugin(),
            new EveryNWeeksCadenceTypePlugin(),
        ]));
    }

    public function testWeeklyAnchoredOnFirstDayCountsFiveOccurrences(): void
    {
        // Arrange
        $counter = $this->createCounter();

        // Act
        $occurrenceCount = $counter->count(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            null,
            '2026-07-01',
            new DateTimeImmutable(static::REFERENCE_DATE),
        );

        // Assert
        $this->assertSame(5, $occurrenceCount);
    }

    public function testWeeklyAnchoredLaterCountsFourOccurrences(): void
    {
        // Arrange
        $counter = $this->createCounter();

        // Act
        $occurrenceCount = $counter->count(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            null,
            '2026-07-04',
            new DateTimeImmutable(static::REFERENCE_DATE),
        );

        // Assert
        $this->assertSame(4, $occurrenceCount);
    }

    public function testBiWeeklyCountsThreeOccurrences(): void
    {
        // Arrange
        $counter = $this->createCounter();

        // Act
        $occurrenceCount = $counter->count(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_BI_WEEKLY,
            null,
            '2026-07-01',
            new DateTimeImmutable(static::REFERENCE_DATE),
        );

        // Assert
        $this->assertSame(3, $occurrenceCount);
    }

    public function testMonthlyCountsSingleOccurrence(): void
    {
        // Arrange
        $counter = $this->createCounter();

        // Act
        $occurrenceCount = $counter->count(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_MONTHLY,
            null,
            '2026-07-10',
            new DateTimeImmutable(static::REFERENCE_DATE),
        );

        // Assert
        $this->assertSame(1, $occurrenceCount);
    }

    public function testEveryNWeeksCountsOccurrencesByInterval(): void
    {
        // Arrange
        $counter = $this->createCounter();

        // Act
        $occurrenceCount = $counter->count(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_EVERY_N_WEEKS,
            3,
            '2026-07-01',
            new DateTimeImmutable(static::REFERENCE_DATE),
        );

        // Assert
        $this->assertSame(2, $occurrenceCount);
    }

    public function testReturnsZeroWhenAnchorIsAfterTheCurrentMonth(): void
    {
        // Arrange
        $counter = $this->createCounter();

        // Act
        $occurrenceCount = $counter->count(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            null,
            '2026-08-05',
            new DateTimeImmutable(static::REFERENCE_DATE),
        );

        // Assert
        $this->assertSame(0, $occurrenceCount);
    }

    public function testReturnsZeroForEveryNWeeksWithoutCadenceValue(): void
    {
        // Arrange
        $counter = $this->createCounter();

        // Act
        $occurrenceCount = $counter->count(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_EVERY_N_WEEKS,
            null,
            '2026-07-01',
            new DateTimeImmutable(static::REFERENCE_DATE),
        );

        // Assert
        $this->assertSame(0, $occurrenceCount);
    }

    public function testReturnsZeroForUnsupportedCadenceType(): void
    {
        // Arrange
        $counter = $this->createCounter();

        // Act
        $occurrenceCount = $counter->count(
            'unsupported_cadence',
            null,
            '2026-07-01',
            new DateTimeImmutable(static::REFERENCE_DATE),
        );

        // Assert
        $this->assertSame(0, $occurrenceCount);
    }

    public function testReturnsZeroWhenCadenceTypeIsNull(): void
    {
        // Arrange
        $counter = $this->createCounter();

        // Act
        $occurrenceCount = $counter->count(
            null,
            null,
            '2026-07-01',
            new DateTimeImmutable(static::REFERENCE_DATE),
        );

        // Assert
        $this->assertSame(0, $occurrenceCount);
    }

    public function testReturnsZeroWhenAnchorDateIsNull(): void
    {
        // Arrange
        $counter = $this->createCounter();

        // Act
        $occurrenceCount = $counter->count(
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            null,
            null,
            new DateTimeImmutable(static::REFERENCE_DATE),
        );

        // Assert
        $this->assertSame(0, $occurrenceCount);
    }
}
