<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Plugin\Cadence;

use Codeception\Test\Unit;
use DateTimeImmutable;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Exception\InvalidCadenceValueException;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\BiWeeklyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\ByDaysCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\EveryNWeeksCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\MonthlyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\WeeklyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\CadenceTypePluginInterface;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Communication
 * @group Plugin
 * @group Cadence
 * @group CadenceTypePluginsTest
 * Add your own group annotations below this line
 */
class CadenceTypePluginsTest extends Unit
{
    protected OrderExperienceManagementBusinessTester $tester;

    public function testWeeklyPluginReturnsCorrectName(): void
    {
        $this->assertSame(OrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY, (new WeeklyCadenceTypePlugin())->getName());
    }

    public function testWeeklyPluginAdvancesSevenDays(): void
    {
        $base = new DateTimeImmutable('2025-01-01');

        $result = (new WeeklyCadenceTypePlugin())->getNextTriggerDate($base, null);

        $this->assertSame('2025-01-08', $result->format('Y-m-d'));
    }

    public function testBiWeeklyPluginReturnsCorrectName(): void
    {
        $this->assertSame(OrderExperienceManagementConfig::CADENCE_TYPE_BI_WEEKLY, (new BiWeeklyCadenceTypePlugin())->getName());
    }

    public function testBiWeeklyPluginAdvancesFourteenDays(): void
    {
        $base = new DateTimeImmutable('2025-01-01');

        $result = (new BiWeeklyCadenceTypePlugin())->getNextTriggerDate($base, null);

        $this->assertSame('2025-01-15', $result->format('Y-m-d'));
    }

    public function testMonthlyPluginReturnsCorrectName(): void
    {
        $this->assertSame(OrderExperienceManagementConfig::CADENCE_TYPE_MONTHLY, (new MonthlyCadenceTypePlugin())->getName());
    }

    public function testMonthlyPluginAdvancesOneCalendarMonth(): void
    {
        $base = new DateTimeImmutable('2025-01-15');

        $result = (new MonthlyCadenceTypePlugin())->getNextTriggerDate($base, null);

        $this->assertSame('2025-02-15', $result->format('Y-m-d'));
    }

    public function testMonthlyPluginRollsOverWhenTargetDayDoesNotExistInNextMonth(): void
    {
        // Jan 31 has no counterpart in a 28-day February, so PHP's `+1 month` overflows into March.
        $base = new DateTimeImmutable('2025-01-31');

        $result = (new MonthlyCadenceTypePlugin())->getNextTriggerDate($base, null);

        $this->assertSame('2025-03-03', $result->format('Y-m-d'));
    }

    public function testEveryNWeeksPluginReturnsCorrectName(): void
    {
        $this->assertSame(OrderExperienceManagementConfig::CADENCE_TYPE_EVERY_N_WEEKS, (new EveryNWeeksCadenceTypePlugin())->getName());
    }

    public function testEveryNWeeksPluginAdvancesNWeeks(): void
    {
        $base = new DateTimeImmutable('2025-01-01');

        $result = (new EveryNWeeksCadenceTypePlugin())->getNextTriggerDate($base, 3);

        $this->assertSame('2025-01-22', $result->format('Y-m-d'));
    }

    public function testEveryNWeeksPluginThrowsWhenCadenceValueIsNull(): void
    {
        $this->expectException(InvalidCadenceValueException::class);

        (new EveryNWeeksCadenceTypePlugin())->getNextTriggerDate(new DateTimeImmutable(), null);
    }

    public function testEveryNWeeksPluginThrowsWhenCadenceValueIsZero(): void
    {
        $this->expectException(InvalidCadenceValueException::class);

        (new EveryNWeeksCadenceTypePlugin())->getNextTriggerDate(new DateTimeImmutable(), 0);
    }

    public function testEveryNWeeksPluginThrowsWhenCadenceValueIsNegative(): void
    {
        $this->expectException(InvalidCadenceValueException::class);

        (new EveryNWeeksCadenceTypePlugin())->getNextTriggerDate(new DateTimeImmutable(), -1);
    }

    public function testWeeklyPluginReturnsDisplayKey(): void
    {
        $this->assertSame('recurring_orders.cadence.weekly', (new WeeklyCadenceTypePlugin())->getDisplayKey());
    }

    public function testBiWeeklyPluginReturnsDisplayKey(): void
    {
        $this->assertSame('recurring_orders.cadence.bi_weekly', (new BiWeeklyCadenceTypePlugin())->getDisplayKey());
    }

    public function testMonthlyPluginReturnsDisplayKey(): void
    {
        $this->assertSame('recurring_orders.cadence.monthly', (new MonthlyCadenceTypePlugin())->getDisplayKey());
    }

    public function testEveryNWeeksPluginReturnsDisplayKey(): void
    {
        $this->assertSame('recurring_orders.cadence.every_n_weeks', (new EveryNWeeksCadenceTypePlugin())->getDisplayKey());
    }

    /**
     * @dataProvider provideOptionIgnoringPlugins
     */
    public function testPluginsWithoutOptionsIgnoreCadenceOptions(
        CadenceTypePluginInterface $cadenceTypePlugin,
        ?int $cadenceValue
    ): void {
        // Arrange
        $base = new DateTimeImmutable('2026-07-15');

        // Act
        $withoutOptions = $cadenceTypePlugin->getNextTriggerDate($base, $cadenceValue);
        $withOptions = $cadenceTypePlugin->getNextTriggerDate($base, $cadenceValue, ['1', '4']);

        // Assert
        $this->assertSame($withoutOptions->format('Y-m-d'), $withOptions->format('Y-m-d'));
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function provideOptionIgnoringPlugins(): array
    {
        return [
            'weekly' => [new WeeklyCadenceTypePlugin(), null],
            'bi-weekly' => [new BiWeeklyCadenceTypePlugin(), null],
            'monthly' => [new MonthlyCadenceTypePlugin(), null],
            'every n weeks' => [new EveryNWeeksCadenceTypePlugin(), 3],
        ];
    }

    public function testByDaysPluginReturnsCorrectName(): void
    {
        // Arrange
        $cadenceTypePlugin = new ByDaysCadenceTypePlugin();

        // Act
        $name = $cadenceTypePlugin->getName();

        // Assert
        $this->assertSame(OrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS, $name);
    }

    public function testByDaysPluginReturnsCorrectDisplayKey(): void
    {
        // Arrange
        $cadenceTypePlugin = new ByDaysCadenceTypePlugin();

        // Act
        $displayKey = $cadenceTypePlugin->getDisplayKey();

        // Assert
        $this->assertSame('recurring_orders.cadence.by_days', $displayKey);
    }

    /**
     * @dataProvider provideCadenceTypeShapes
     *
     * @param array<string> $expectedRequiredCadenceFields
     */
    public function testPluginDeclaresWhichCadenceParametersItTakes(
        CadenceTypePluginInterface $cadenceTypePlugin,
        array $expectedRequiredCadenceFields
    ): void {
        // Act
        $cadenceValidationResponseTransfer = $cadenceTypePlugin->isCadenceValid(null, []);

        // Assert
        $this->assertSame(
            $expectedRequiredCadenceFields,
            $cadenceValidationResponseTransfer->getRequiredCadenceFields(),
        );
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function provideCadenceTypeShapes(): array
    {
        return [
            'weekly takes neither' => [new WeeklyCadenceTypePlugin(), []],
            'bi-weekly takes neither' => [new BiWeeklyCadenceTypePlugin(), []],
            'monthly takes neither' => [new MonthlyCadenceTypePlugin(), []],
            'every n weeks takes a value' => [
                new EveryNWeeksCadenceTypePlugin(),
                [SharedOrderExperienceManagementConfig::CADENCE_FIELD_CADENCE_VALUE],
            ],
            'by days takes options' => [
                new ByDaysCadenceTypePlugin(),
                [SharedOrderExperienceManagementConfig::CADENCE_FIELD_CADENCE_OPTIONS],
            ],
        ];
    }

    /**
     * @dataProvider provideParameterlessPlugins
     */
    public function testPluginsWithoutParametersAcceptAnyValueAndOptions(
        CadenceTypePluginInterface $cadenceTypePlugin
    ): void {
        // Act
        $cadenceValidationResponseTransfer = $cadenceTypePlugin->isCadenceValid(null, []);

        // Assert
        $this->assertTrue($cadenceValidationResponseTransfer->getIsSuccess());
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function provideParameterlessPlugins(): array
    {
        return [
            'weekly' => [new WeeklyCadenceTypePlugin()],
            'bi-weekly' => [new BiWeeklyCadenceTypePlugin()],
            'monthly' => [new MonthlyCadenceTypePlugin()],
        ];
    }

    /**
     * @dataProvider provideEveryNWeeksValueValidity
     */
    public function testEveryNWeeksPluginAcceptsOnlyAPositiveInterval(?int $cadenceValue, bool $expectedIsValid): void
    {
        // Act
        $cadenceValidationResponseTransfer = (new EveryNWeeksCadenceTypePlugin())->isCadenceValid($cadenceValue, []);

        // Assert
        $this->assertSame($expectedIsValid, $cadenceValidationResponseTransfer->getIsSuccess());
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function provideEveryNWeeksValueValidity(): array
    {
        return [
            'one week' => [1, true],
            'several weeks' => [6, true],
            'missing interval' => [null, false],
            'zero would never advance the schedule' => [0, false],
            'negative interval' => [-1, false],
        ];
    }
}
