<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Date;

use Codeception\Test\Unit;
use DateTimeImmutable;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\WeekDayOptionsReader;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\WeekDayTriggerDateCalculator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Date\FirstTriggerDateResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\EveryNWeeksCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\MonthlyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\WeeklyCadenceTypePlugin;
use SprykerFeatureTest\Zed\OrderExperienceManagement\ByDaysCadenceTypePluginStub;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Date
 * @group FirstTriggerDateResolverTest
 * Add your own group annotations below this line
 */
class FirstTriggerDateResolverTest extends Unit
{
    protected const string DATE_FORMAT = 'Y-m-d';

    public function testReturnsFutureStartDateUnchanged(): void
    {
        // Arrange
        $startDate = (new DateTimeImmutable('+30 days'))->format(static::DATE_FORMAT);

        // Act
        $firstTriggerDate = $this->createResolver()->resolve(
            $startDate,
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            null,
        );

        // Assert
        $this->assertSame($startDate, $firstTriggerDate->format(static::DATE_FORMAT));
    }

    public function testReturnsTomorrowUnchangedAsItIsAlreadyInTheFuture(): void
    {
        // Arrange
        $startDate = (new DateTimeImmutable('tomorrow'))->format(static::DATE_FORMAT);

        // Act
        $firstTriggerDate = $this->createResolver()->resolve(
            $startDate,
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_MONTHLY,
            null,
        );

        // Assert
        $this->assertSame($startDate, $firstTriggerDate->format(static::DATE_FORMAT));
    }

    public function testAdvancesTodayByOneCadencePeriod(): void
    {
        // Arrange
        $expected = (new DateTimeImmutable('today'))->modify('+7 days')->format(static::DATE_FORMAT);

        // Act
        $firstTriggerDate = $this->createResolver()->resolve(
            (new DateTimeImmutable('today'))->format(static::DATE_FORMAT),
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            null,
        );

        // Assert - the order placed at checkout already covers today.
        $this->assertSame($expected, $firstTriggerDate->format(static::DATE_FORMAT));
    }

    public function testAdvancesTodayByTheConfiguredNumberOfWeeks(): void
    {
        // Arrange
        $expected = (new DateTimeImmutable('today'))->modify('+3 weeks')->format(static::DATE_FORMAT);

        // Act
        $firstTriggerDate = $this->createResolver()->resolve(
            (new DateTimeImmutable('today'))->format(static::DATE_FORMAT),
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_EVERY_N_WEEKS,
            3,
        );

        // Assert
        $this->assertSame($expected, $firstTriggerDate->format(static::DATE_FORMAT));
    }

    public function testAdvancesFromTodayWhenStartDateIsMissing(): void
    {
        // Arrange
        $expected = (new DateTimeImmutable('today'))->modify('+7 days')->format(static::DATE_FORMAT);

        // Act
        $firstTriggerDate = $this->createResolver()->resolve(
            null,
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            null,
        );

        // Assert - the checkout validator rejects an empty start date, so this branch is defensive only.
        $this->assertSame($expected, $firstTriggerDate->format(static::DATE_FORMAT));
    }

    public function testAdvancesFromTodayWhenStartDateIsMalformed(): void
    {
        // Arrange
        $expected = (new DateTimeImmutable('today'))->modify('+7 days')->format(static::DATE_FORMAT);

        // Act
        $firstTriggerDate = $this->createResolver()->resolve(
            'not-a-date',
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            null,
        );

        // Assert
        $this->assertSame($expected, $firstTriggerDate->format(static::DATE_FORMAT));
    }

    public function testAdvancesPastStartDateByOneCadencePeriod(): void
    {
        // Arrange
        $startDate = (new DateTimeImmutable('-1 day'))->format(static::DATE_FORMAT);
        $expected = (new DateTimeImmutable($startDate))->modify('+7 days')->format(static::DATE_FORMAT);

        // Act
        $firstTriggerDate = $this->createResolver()->resolve(
            $startDate,
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            null,
        );

        // Assert - past dates are rejected upstream; advancing keeps the resolver total either way.
        $this->assertSame($expected, $firstTriggerDate->format(static::DATE_FORMAT));
    }

    public function testResolvesTimeToMidnightSoTheDateIsNotShiftedByTheCurrentTime(): void
    {
        // Act
        $firstTriggerDate = $this->createResolver()->resolve(
            (new DateTimeImmutable('+30 days'))->format(static::DATE_FORMAT),
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            null,
        );

        // Assert
        $this->assertSame('00:00:00', $firstTriggerDate->format('H:i:s'));
    }

    /**
     * @dataProvider provideByDaysFutureStartDates
     *
     * @param array<string> $cadenceOptions
     */
    public function testResolvesByDaysFutureStartDateToTheStartDateItself(array $cadenceOptions): void
    {
        // Arrange
        $futureWednesday = (new DateTimeImmutable('next wednesday'))->modify('+2 weeks');

        // Act
        $firstTriggerDate = $this->createResolver()->resolve(
            $futureWednesday->format(static::DATE_FORMAT),
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS,
            null,
            $cadenceOptions,
        );

        // Assert
        $this->assertSame(
            $futureWednesday->format(static::DATE_FORMAT),
            $firstTriggerDate->format(static::DATE_FORMAT),
        );
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function provideByDaysFutureStartDates(): array
    {
        return [
            'start date itself is a selected day' => [['3']],
            'start date is not a selected day' => [['5']],
            'start date is not any of several selected days' => [['1', '4']],
        ];
    }

    public function testResolvesByDaysStartDateOfTodayToTheNextMatchingDayNeverToday(): void
    {
        // Arrange
        $today = new DateTimeImmutable('today');
        $todayWeekDay = $today->format('N');

        // Act
        $firstTriggerDate = $this->createResolver()->resolve(
            $today->format(static::DATE_FORMAT),
            SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS,
            null,
            [$todayWeekDay],
        );

        // Assert
        $this->assertSame($today->modify('+7 days')->format(static::DATE_FORMAT), $firstTriggerDate->format(static::DATE_FORMAT));
    }

    protected function createResolver(): FirstTriggerDateResolver
    {
        return new FirstTriggerDateResolver(
            $this->createCadenceResolver([
                new WeeklyCadenceTypePlugin(),
                new MonthlyCadenceTypePlugin(),
                new EveryNWeeksCadenceTypePlugin(),
                new ByDaysCadenceTypePluginStub(),
            ]),
            new WeekDayTriggerDateCalculator(new WeekDayOptionsReader()),
        );
    }

    /**
     * @param array<\SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\CadenceTypePluginInterface> $cadenceTypePlugins
     */
    protected function createCadenceResolver(array $cadenceTypePlugins): CadenceResolver
    {
        return new CadenceResolver($cadenceTypePlugins);
    }
}
