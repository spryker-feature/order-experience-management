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
use SprykerFeature\Zed\OrderExperienceManagement\Business\Exception\InvalidCadenceValueException;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\BiWeeklyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\EveryNWeeksCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\MonthlyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\WeeklyCadenceTypePlugin;
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
}
