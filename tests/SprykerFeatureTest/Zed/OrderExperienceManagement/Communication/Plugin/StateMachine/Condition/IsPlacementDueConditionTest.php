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
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition\IsPlacementDueConditionPlugin;
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
 * @group IsPlacementDueConditionPluginTest
 */
class IsPlacementDueConditionTest extends Unit
{
    protected OrderExperienceManagementBusinessTester $tester;

    public function testGetNameReturnsExpectedConditionName(): void
    {
        $this->assertSame('RecurringOrders/IsPlacementDue', (new IsPlacementDueConditionPlugin())->getName());
    }

    public function testCheckReturnsFalseWhenScheduleNotFound(): void
    {
        $repositoryMock = $this->createMock(OrderExperienceManagementRepositoryInterface::class);
        $repositoryMock->method('findRecurringScheduleDueData')->willReturn(null);

        $this->assertFalse($this->createCondition($repositoryMock)->check(
            (new StateMachineItemTransfer())->setIdentifier(99),
        ));
    }

    public function testCheckReturnsFalseWhenTriggerDateIsInFuture(): void
    {
        $repositoryMock = $this->createMock(OrderExperienceManagementRepositoryInterface::class);
        $repositoryMock->method('findRecurringScheduleDueData')->willReturn(
            (new RecurringScheduleDueDataTransfer())
                ->setNextTriggerDate((new DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s')),
        );

        $this->assertFalse($this->createCondition($repositoryMock)->check(
            (new StateMachineItemTransfer())->setIdentifier(1),
        ));
    }

    public function testCheckReturnsTrueWhenTriggerDateIsInThePast(): void
    {
        $repositoryMock = $this->createMock(OrderExperienceManagementRepositoryInterface::class);
        $repositoryMock->method('findRecurringScheduleDueData')->willReturn(
            (new RecurringScheduleDueDataTransfer())
                ->setNextTriggerDate((new DateTimeImmutable('-1 day'))->format('Y-m-d H:i:s')),
        );

        $this->assertTrue($this->createCondition($repositoryMock)->check(
            (new StateMachineItemTransfer())->setIdentifier(1),
        ));
    }

    protected function createCondition(OrderExperienceManagementRepositoryInterface $repository): IsPlacementDueConditionPlugin
    {
        return new class ($repository) extends IsPlacementDueConditionPlugin {
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
