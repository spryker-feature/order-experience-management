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
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\RecurringOrdersStateMachineHandlerPlugin;
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
    protected const int ID_STATE_MACHINE_ITEM_STATE = 5;

    protected const int ID_STATE_MACHINE_ITEM_STATE_OTHER = 9;

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

        // Act
        $isUpdated = $this->createPlugin()->itemStateUpdated(
            (new StateMachineItemTransfer())
                ->setIdentifier($recurringScheduleTransfer->getIdRecurringScheduleOrFail())
                ->setIdItemState(static::ID_STATE_MACHINE_ITEM_STATE)
                ->setStateName(SharedOrderExperienceManagementConfig::STATUS_PAUSED),
        );

        // Assert
        $recurringScheduleEntity = SpyRecurringScheduleQuery::create()
            ->findOneByIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail());

        $this->assertTrue($isUpdated);
        $this->assertSame(static::ID_STATE_MACHINE_ITEM_STATE, $recurringScheduleEntity->getFkStateMachineItemState());
        $this->assertSame(SharedOrderExperienceManagementConfig::STATUS_PAUSED, $recurringScheduleEntity->getStatus());
    }

    public function testGetStateMachineItemsByStateIdsReturnsOnlySchedulesInGivenStates(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $idMatchingSchedule = $this->haveRecurringScheduleInState($idCustomer, static::ID_STATE_MACHINE_ITEM_STATE);
        $this->haveRecurringScheduleInState($idCustomer, static::ID_STATE_MACHINE_ITEM_STATE_OTHER);

        // Act
        $stateMachineItemTransfers = $this->createPlugin()->getStateMachineItemsByStateIds([static::ID_STATE_MACHINE_ITEM_STATE]);

        // Assert
        $this->assertCount(1, $stateMachineItemTransfers);
        $this->assertSame($idMatchingSchedule, $stateMachineItemTransfers[0]->getIdentifier());
        $this->assertSame(static::ID_STATE_MACHINE_ITEM_STATE, $stateMachineItemTransfers[0]->getIdItemState());
    }

    public function testGetStateMachineItemsByStateIdsReturnsEmptyArrayWhenNoStateIdsGiven(): void
    {
        // Act
        $stateMachineItemTransfers = $this->createPlugin()->getStateMachineItemsByStateIds([]);

        // Assert
        $this->assertSame([], $stateMachineItemTransfers);
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
