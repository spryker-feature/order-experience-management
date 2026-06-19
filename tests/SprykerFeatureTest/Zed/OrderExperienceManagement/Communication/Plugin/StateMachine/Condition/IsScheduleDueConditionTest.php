<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition;

use Codeception\Test\Unit;
use DateTimeImmutable;
use Generated\Shared\Transfer\RecurringScheduleDueDataTransfer;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition\IsScheduleDueConditionPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
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
 * @group IsScheduleDueConditionPluginTest
 */
class IsScheduleDueConditionTest extends Unit
{
    protected OrderExperienceManagementBusinessTester $tester;

    public function testGetNameReturnsExpectedConditionName(): void
    {
        $this->assertSame('RecurringOrders/IsScheduleDue', (new IsScheduleDueConditionPlugin())->getName());
    }

    public function testCheckReturnsFalseWhenScheduleNotFound(): void
    {
        $repositoryMock = $this->createMock(OrderExperienceManagementRepositoryInterface::class);
        $repositoryMock->method('findRecurringScheduleDueData')->willReturn(null);

        $condition = $this->createCondition($repositoryMock);

        $this->assertFalse($condition->check((new StateMachineItemTransfer())->setIdentifier(99)));
    }

    public function testCheckReturnsFalseWhenNotificationWindowHasNotOpened(): void
    {
        // Trigger is 3 days away; with a 48 h window notifyFrom = +1 day → still in the future.
        $triggerDate = (new DateTimeImmutable('+3 days'))->format('Y-m-d H:i:s');

        $repositoryMock = $this->createMock(OrderExperienceManagementRepositoryInterface::class);
        $repositoryMock->method('findRecurringScheduleDueData')->willReturn(
            (new RecurringScheduleDueDataTransfer())
                ->setNextTriggerDate($triggerDate)
                ->setNotificationWindowHours(48),
        );

        $condition = $this->createCondition($repositoryMock);

        $this->assertFalse($condition->check((new StateMachineItemTransfer())->setIdentifier(1)));
    }

    public function testCheckReturnsTrueWhenNotificationWindowHasOpened(): void
    {
        // Trigger is 1 day away; with a 48 h window notifyFrom = −1 day → already passed.
        $triggerDate = (new DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s');

        $repositoryMock = $this->createMock(OrderExperienceManagementRepositoryInterface::class);
        $repositoryMock->method('findRecurringScheduleDueData')->willReturn(
            (new RecurringScheduleDueDataTransfer())
                ->setNextTriggerDate($triggerDate)
                ->setNotificationWindowHours(48),
        );

        $condition = $this->createCondition($repositoryMock);

        $this->assertTrue($condition->check((new StateMachineItemTransfer())->setIdentifier(1)));
    }

    public function testCheckUsesPerScheduleWindowHoursInsteadOfConfigDefault(): void
    {
        // Trigger is ~2 days + 1 h away.
        // Config default (48 h) would give notifyFrom = +1 h → not yet due → false.
        // Schedule-level override (72 h) gives notifyFrom = −23 h → already passed → true.
        $triggerDate = (new DateTimeImmutable('+49 hours'))->format('Y-m-d H:i:s');

        $repositoryMock = $this->createMock(OrderExperienceManagementRepositoryInterface::class);
        $repositoryMock->method('findRecurringScheduleDueData')->willReturn(
            (new RecurringScheduleDueDataTransfer())
                ->setNextTriggerDate($triggerDate)
                ->setNotificationWindowHours(72),
        );

        $configMock = $this->getMockBuilder(OrderExperienceManagementConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configMock->expects($this->never())->method('getDefaultNotificationWindowHours');

        $condition = $this->createCondition($repositoryMock, $configMock);

        $this->assertTrue($condition->check((new StateMachineItemTransfer())->setIdentifier(1)));
    }

    public function testCheckFallsBackToConfigDefaultWhenScheduleWindowHoursIsNull(): void
    {
        // Trigger is 1 day away; config default 48 h gives notifyFrom = −1 day → due.
        $triggerDate = (new DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s');

        $repositoryMock = $this->createMock(OrderExperienceManagementRepositoryInterface::class);
        $repositoryMock->method('findRecurringScheduleDueData')->willReturn(
            (new RecurringScheduleDueDataTransfer())
                ->setNextTriggerDate($triggerDate)
                ->setNotificationWindowHours(null),
        );

        $configMock = $this->getMockBuilder(OrderExperienceManagementConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configMock->expects($this->once())
            ->method('getDefaultNotificationWindowHours')
            ->willReturn(48);

        $condition = $this->createCondition($repositoryMock, $configMock);

        $this->assertTrue($condition->check((new StateMachineItemTransfer())->setIdentifier(1)));
    }

    protected function createCondition(
        OrderExperienceManagementRepositoryInterface $repository,
        ?OrderExperienceManagementConfig $config = null,
    ): IsScheduleDueConditionPlugin {
        $configMock = $config ?? $this->getMockBuilder(OrderExperienceManagementConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new class ($repository, $configMock) extends IsScheduleDueConditionPlugin {
            public function __construct(
                private readonly OrderExperienceManagementRepositoryInterface $repositoryOverride,
                private readonly OrderExperienceManagementConfig $configOverride,
            ) {
            }

            public function getRepository(): OrderExperienceManagementRepositoryInterface
            {
                return $this->repositoryOverride;
            }

            public function getConfig(): OrderExperienceManagementConfig
            {
                return $this->configOverride;
            }
        };
    }
}
