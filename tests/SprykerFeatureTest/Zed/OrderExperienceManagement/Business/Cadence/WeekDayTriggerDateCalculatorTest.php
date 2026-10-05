<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Cadence;

use Codeception\Test\Unit;
use DateTimeImmutable;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\WeekDayOptionsReader;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\WeekDayTriggerDateCalculator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Exception\InvalidCadenceOptionsException;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Cadence
 * @group WeekDayTriggerDateCalculatorTest
 * Add your own group annotations below this line
 */
class WeekDayTriggerDateCalculatorTest extends Unit
{
    protected const string DATE_FORMAT = 'Y-m-d';

    /**
     * 2026-07-15 is a Wednesday (ISO weekday 3).
     */
    protected const string BASE_WEDNESDAY = '2026-07-15';

    /**
     * @dataProvider provideNextTriggerDates
     *
     * @param array<string> $cadenceOptions
     */
    public function testCalculateNextTriggerDateReturnsTheNextSelectedWeekDay(
        array $cadenceOptions,
        string $expectedDate
    ): void {
        // Arrange
        $baseDate = new DateTimeImmutable(static::BASE_WEDNESDAY);

        // Act
        $nextTriggerDate = $this->createCalculator()->calculateNextTriggerDate($baseDate, $cadenceOptions);

        // Assert
        $this->assertSame($expectedDate, $nextTriggerDate->format(static::DATE_FORMAT));
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function provideNextTriggerDates(): array
    {
        return [
            'single later day in the same week' => [['5'], '2026-07-17'],
            'single earlier day wraps into the next week' => [['1'], '2026-07-20'],
            'base day itself is selected, so it wraps a full week' => [['3'], '2026-07-22'],
            'nearest of several selected days wins' => [['1', '4'], '2026-07-16'],
            'wraps across Sunday into Monday' => [['7'], '2026-07-19'],
            'every day selected advances a single day' => [['1', '2', '3', '4', '5', '6', '7'], '2026-07-16'],
            'duplicate and unsorted options are normalized' => [['4', '4', '1'], '2026-07-16'],
        ];
    }

    public function testCalculateNextTriggerDateThrowsWhenNoWeekDayIsSelected(): void
    {
        // Arrange
        $baseDate = new DateTimeImmutable(static::BASE_WEDNESDAY);

        // Expect
        $this->expectException(InvalidCadenceOptionsException::class);

        // Act
        $this->createCalculator()->calculateNextTriggerDate($baseDate, []);
    }

    public function testCalculateNextTriggerDateThrowsWhenEveryOptionIsOutOfRange(): void
    {
        // Arrange
        $baseDate = new DateTimeImmutable(static::BASE_WEDNESDAY);

        // Expect
        $this->expectException(InvalidCadenceOptionsException::class);

        // Act
        $this->createCalculator()->calculateNextTriggerDate($baseDate, ['0', '8', 'monday']);
    }

    public function testCalculateNextTriggerDateKeepsTheTimeOfTheBaseDate(): void
    {
        // Arrange
        $baseDate = new DateTimeImmutable(static::BASE_WEDNESDAY . ' 09:30:00');

        // Act
        $nextTriggerDate = $this->createCalculator()->calculateNextTriggerDate($baseDate, ['5']);

        // Assert
        $this->assertSame('09:30:00', $nextTriggerDate->format('H:i:s'));
    }

    protected function createCalculator(): WeekDayTriggerDateCalculator
    {
        return new WeekDayTriggerDateCalculator(new WeekDayOptionsReader());
    }

    public function testSearchesAFullWeekEvenWhenOnlyOneWeekDayIsSelected(): void
    {
        // Arrange
        $baseDate = new DateTimeImmutable(static::BASE_WEDNESDAY);

        // Act
        $nextTriggerDate = $this->createCalculator()->calculateNextTriggerDate(
            $baseDate,
            [(string)SharedOrderExperienceManagementConfig::DAY_TUESDAY],
        );

        // Assert
        $this->assertSame(6, (int)$baseDate->diff($nextTriggerDate)->format('%a'));
    }
}
