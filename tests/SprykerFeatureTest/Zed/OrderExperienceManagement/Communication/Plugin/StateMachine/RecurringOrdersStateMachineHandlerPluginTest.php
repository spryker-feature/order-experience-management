<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleQuery;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineItemStateQuery;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineProcessQuery;
use Spryker\Zed\StateMachine\Dependency\Plugin\CommandPluginInterface;
use Spryker\Zed\StateMachine\Dependency\Plugin\ConditionPluginInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\RecurringOrdersStateMachineHandlerPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Communication
 * @group Plugin
 * @group StateMachine
 * @group RecurringOrdersStateMachineHandlerPluginTest
 */
class RecurringOrdersStateMachineHandlerPluginTest extends Unit
{
    protected const string STATE_NAME = 'paused';

    protected const string STATE_NAME_OTHER = 'cancelled';

    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->ensureRecurringScheduleTablesAreEmpty();
    }

    public function testItemStateUpdatedPersistsStateMachineItemStateAndStatusOnRecurringSchedule(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer, [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
        ]);
        $idStateMachineItemState = $this->haveStateMachineItemState(static::STATE_NAME);

        // Act
        $isUpdated = $this->createPlugin()->itemStateUpdated(
            (new StateMachineItemTransfer())
                ->setIdentifier($recurringScheduleTransfer->getIdRecurringScheduleOrFail())
                ->setIdItemState($idStateMachineItemState)
                ->setStateName(SharedOrderExperienceManagementConfig::STATUS_PAUSED),
        );

        // Assert
        $recurringScheduleEntity = SpyRecurringScheduleQuery::create()
            ->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail());

        $this->assertTrue($isUpdated);
        $this->assertSame($idStateMachineItemState, $recurringScheduleEntity->getFkStateMachineItemState());
        $this->assertSame(SharedOrderExperienceManagementConfig::STATUS_PAUSED, $recurringScheduleEntity->getStatus());
    }

    public function testGetStateMachineItemsByStateIdsReturnsOnlySchedulesInGivenStates(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $idStateMachineItemState = $this->haveStateMachineItemState(static::STATE_NAME);
        $idMatchingSchedule = $this->haveRecurringScheduleInState($idCustomer, $idStateMachineItemState);
        $this->haveRecurringScheduleInState($idCustomer, $this->haveStateMachineItemState(static::STATE_NAME_OTHER));

        // Act
        $stateMachineItemTransfers = $this->createPlugin()->getStateMachineItemsByStateIds([$idStateMachineItemState]);

        // Assert
        $this->assertCount(1, $stateMachineItemTransfers);
        $this->assertSame($idMatchingSchedule, $stateMachineItemTransfers[0]->getIdentifier());
        $this->assertSame($idStateMachineItemState, $stateMachineItemTransfers[0]->getIdItemState());
    }

    public function testGetStateMachineItemsByStateIdsReturnsEmptyArrayWhenNoStateIdsGiven(): void
    {
        // Act
        $stateMachineItemTransfers = $this->createPlugin()->getStateMachineItemsByStateIds([]);

        // Assert
        $this->assertSame([], $stateMachineItemTransfers);
    }

    public function testGetCommandPluginsReturnsConfiguredCommandPlugins(): void
    {
        // Act
        $commandPlugins = $this->createPlugin()->getCommandPlugins();

        // Assert
        $this->assertNotEmpty($commandPlugins);
        $this->assertContainsOnlyInstancesOf(CommandPluginInterface::class, $commandPlugins);
    }

    public function testGetConditionPluginsReturnsConfiguredConditionPlugins(): void
    {
        // Act
        $conditionPlugins = $this->createPlugin()->getConditionPlugins();

        // Assert
        $this->assertNotEmpty($conditionPlugins);
        $this->assertContainsOnlyInstancesOf(ConditionPluginInterface::class, $conditionPlugins);
    }

    public function testGetStateMachineNameReturnsConfiguredName(): void
    {
        $this->assertSame(
            (new OrderExperienceManagementConfig())->getStateMachineName(),
            $this->createPlugin()->getStateMachineName(),
        );
    }

    public function testGetActiveProcessesReturnsConfiguredProcess(): void
    {
        $this->assertSame(
            [(new OrderExperienceManagementConfig())->getProcessName()],
            $this->createPlugin()->getActiveProcesses(),
        );
    }

    public function testGetInitialStateForProcessReturnsConfiguredInitialState(): void
    {
        $this->assertSame(
            (new OrderExperienceManagementConfig())->getInitialState(),
            $this->createPlugin()->getInitialStateForProcess((new OrderExperienceManagementConfig())->getProcessName()),
        );
    }

    /**
     * Returns a real `spy_state_machine_item_state` id: `spy_recurring_schedule.fk_state_machine_item_state`
     * is a foreign key, so a fabricated id would violate the constraint.
     */
    protected function haveStateMachineItemState(string $stateName): int
    {
        $config = new OrderExperienceManagementConfig();

        $stateMachineProcessEntity = SpyStateMachineProcessQuery::create()
            ->filterByStateMachineName($config->getStateMachineName())
            ->filterByName($config->getProcessName())
            ->findOneOrCreate();
        $stateMachineProcessEntity->save();

        $stateMachineItemStateEntity = SpyStateMachineItemStateQuery::create()
            ->filterByFkStateMachineProcess($stateMachineProcessEntity->getIdStateMachineProcess())
            ->filterByName($stateName)
            ->findOneOrCreate();
        $stateMachineItemStateEntity->save();

        return $stateMachineItemStateEntity->getIdStateMachineItemState();
    }

    protected function haveRecurringScheduleInState(int $idCustomer, int $idStateMachineItemState): int
    {
        $idRecurringSchedule = $this->tester->haveRecurringSchedule($idCustomer)->getIdRecurringScheduleOrFail();

        SpyRecurringScheduleQuery::create()
            ->findOneByIdRecurringSchedule($idRecurringSchedule)
            ->setFkStateMachineItemState($idStateMachineItemState)
            ->save();

        return $idRecurringSchedule;
    }

    protected function createPlugin(): RecurringOrdersStateMachineHandlerPlugin
    {
        $plugin = new RecurringOrdersStateMachineHandlerPlugin();
        $plugin->setBusinessFactory($this->tester->getFactory());

        return $plugin;
    }
}
