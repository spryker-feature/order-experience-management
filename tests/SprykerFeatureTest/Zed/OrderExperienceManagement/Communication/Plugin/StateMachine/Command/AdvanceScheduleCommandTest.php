<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Command;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleHistoryQuery;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleQuery;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\WeeklyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Command\AdvanceScheduleCommandPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementDependencyProvider;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Communication
 * @group Plugin
 * @group StateMachine
 * @group Command
 * @group AdvanceScheduleCommandPluginTest
 */
class AdvanceScheduleCommandTest extends Unit
{
    protected const string NEXT_TRIGGER_DATE = '2026-01-01';

    protected const string EXPECTED_NEXT_TRIGGER_DATE = '2026-01-08';

    protected OrderExperienceManagementBusinessTester $tester;

    public function testGetNameReturnsExpectedCommandName(): void
    {
        $this->assertSame('RecurringOrders/AdvanceSchedule', (new AdvanceScheduleCommandPlugin())->getName());
    }

    public function testRunAdvancesNextTriggerDateByOneCadencePeriod(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->haveWeeklyScheduleDueOn(static::NEXT_TRIGGER_DATE);

        // Act
        $this->createCommand()->run(
            (new StateMachineItemTransfer())->setIdentifier($recurringScheduleTransfer->getIdRecurringScheduleOrFail()),
        );

        // Assert
        $recurringScheduleEntity = SpyRecurringScheduleQuery::create()
            ->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail());

        $this->assertSame(static::EXPECTED_NEXT_TRIGGER_DATE, $recurringScheduleEntity->getNextTriggerDate()->format('Y-m-d'));
    }

    public function testRunRecordsSkippedHistoryAtSkippedOccurrenceDate(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->haveWeeklyScheduleDueOn(static::NEXT_TRIGGER_DATE);

        // Act
        $this->createCommand()->run(
            (new StateMachineItemTransfer())->setIdentifier($recurringScheduleTransfer->getIdRecurringScheduleOrFail()),
        );

        // Assert
        $recurringScheduleHistoryEntity = SpyRecurringScheduleHistoryQuery::create()
            ->filterByFkRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail())
            ->findOne();

        $this->assertSame(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_SKIPPED, $recurringScheduleHistoryEntity->getEventType());
        $this->assertSame(static::NEXT_TRIGGER_DATE, $recurringScheduleHistoryEntity->getCreatedAt()->format('Y-m-d'));
    }

    protected function haveWeeklyScheduleDueOn(string $nextTriggerDate): RecurringScheduleTransfer
    {
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::PLUGINS_CADENCE_TYPE, [new WeeklyCadenceTypePlugin()]);
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();

        return $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::CADENCE_TYPE => SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            RecurringScheduleTransfer::NEXT_TRIGGER_DATE => $nextTriggerDate,
        ]);
    }

    protected function createCommand(): AdvanceScheduleCommandPlugin
    {
        $command = new AdvanceScheduleCommandPlugin();
        $command->setBusinessFactory($this->tester->getFactory());

        return $command;
    }
}
