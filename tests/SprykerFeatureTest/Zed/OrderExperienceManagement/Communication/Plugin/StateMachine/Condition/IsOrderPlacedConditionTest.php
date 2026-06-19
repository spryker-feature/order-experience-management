<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\RecurringScheduleHistoryTransfer;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition\IsOrderPlacedConditionPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Communication
 * @group Plugin
 * @group StateMachine
 * @group Condition
 * @group IsOrderPlacedConditionPluginTest
 */
class IsOrderPlacedConditionTest extends Unit
{
    protected OrderExperienceManagementBusinessTester $tester;

    public function testGetNameReturnsExpectedConditionName(): void
    {
        $this->assertSame('RecurringOrders/IsOrderPlaced', (new IsOrderPlacedConditionPlugin())->getName());
    }

    public function testCheckReturnsFalseWhenNoHistoryEntryExists(): void
    {
        $repositoryMock = $this->createMock(OrderExperienceManagementRepositoryInterface::class);
        $repositoryMock->method('findLatestHistoryByScheduleId')->willReturn(null);

        $this->assertFalse($this->createCondition($repositoryMock)->check(
            (new StateMachineItemTransfer())->setIdentifier(1),
        ));
    }

    public function testCheckReturnsFalseWhenLatestEventIsNotPlaced(): void
    {
        $repositoryMock = $this->createMock(OrderExperienceManagementRepositoryInterface::class);
        $repositoryMock->method('findLatestHistoryByScheduleId')->willReturn(
            (new RecurringScheduleHistoryTransfer())->setEventType(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_FAILED),
        );

        $this->assertFalse($this->createCondition($repositoryMock)->check(
            (new StateMachineItemTransfer())->setIdentifier(1),
        ));
    }

    public function testCheckReturnsTrueWhenLatestEventIsPlaced(): void
    {
        $repositoryMock = $this->createMock(OrderExperienceManagementRepositoryInterface::class);
        $repositoryMock->method('findLatestHistoryByScheduleId')->willReturn(
            (new RecurringScheduleHistoryTransfer())->setEventType(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_PLACED),
        );

        $this->assertTrue($this->createCondition($repositoryMock)->check(
            (new StateMachineItemTransfer())->setIdentifier(1),
        ));
    }

    protected function createCondition(OrderExperienceManagementRepositoryInterface $repository): IsOrderPlacedConditionPlugin
    {
        return new class ($repository) extends IsOrderPlacedConditionPlugin {
            public function __construct(private readonly OrderExperienceManagementRepositoryInterface $repositoryOverride)
            {
            }

            public function getRepository(): OrderExperienceManagementRepositoryInterface
            {
                return $this->repositoryOverride;
            }
        };
    }
}
