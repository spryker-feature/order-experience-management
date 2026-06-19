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
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleQuery;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Cadence\WeeklyCadenceTypePlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Command\CompletePlacementCommandPlugin;
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
 * @group CompletePlacementCommandPluginTest
 */
class CompletePlacementCommandTest extends Unit
{
    protected const string NEXT_TRIGGER_DATE = '2026-01-01';

    protected const string EXPECTED_NEXT_TRIGGER_DATE = '2026-01-08';

    protected OrderExperienceManagementBusinessTester $tester;

    public function testGetNameReturnsExpectedCommandName(): void
    {
        $this->assertSame('RecurringOrders/CompletePlacement', (new CompletePlacementCommandPlugin())->getName());
    }

    public function testRunAdvancesNextTriggerDateByOneCadencePeriod(): void
    {
        // Arrange
        $this->tester->setDependency(OrderExperienceManagementDependencyProvider::PLUGINS_CADENCE_TYPE, [new WeeklyCadenceTypePlugin()]);
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::CADENCE_TYPE => SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            RecurringScheduleTransfer::NEXT_TRIGGER_DATE => static::NEXT_TRIGGER_DATE,
        ]);

        // Act
        $this->createCommand()->run(
            (new StateMachineItemTransfer())->setIdentifier($recurringScheduleTransfer->getIdRecurringScheduleOrFail()),
        );

        // Assert
        $recurringScheduleEntity = SpyRecurringScheduleQuery::create()
            ->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail());

        $this->assertSame(static::EXPECTED_NEXT_TRIGGER_DATE, $recurringScheduleEntity->getNextTriggerDate()->format('Y-m-d'));
    }

    protected function createCommand(): CompletePlacementCommandPlugin
    {
        $command = new CompletePlacementCommandPlugin();
        $command->setBusinessFactory($this->tester->getFactory());

        return $command;
    }
}
