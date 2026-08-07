<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Due\RecurringScheduleDueCheckerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition\IsScheduleDueConditionPlugin;
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

    public function testCheckReturnsTrueWhenCheckerReturnsTrue(): void
    {
        $checkerMock = $this->createMock(RecurringScheduleDueCheckerInterface::class);
        $checkerMock->method('isScheduleDue')->willReturn(true);

        $this->assertTrue($this->createCondition($checkerMock)->check(
            (new StateMachineItemTransfer())->setIdentifier(1),
        ));
    }

    public function testCheckReturnsFalseWhenCheckerReturnsFalse(): void
    {
        $checkerMock = $this->createMock(RecurringScheduleDueCheckerInterface::class);
        $checkerMock->method('isScheduleDue')->willReturn(false);

        $this->assertFalse($this->createCondition($checkerMock)->check(
            (new StateMachineItemTransfer())->setIdentifier(1),
        ));
    }

    public function testCheckPassesScheduleIdentifierToChecker(): void
    {
        $idRecurringSchedule = 42;

        $checkerMock = $this->createMock(RecurringScheduleDueCheckerInterface::class);
        $checkerMock->expects($this->once())
            ->method('isScheduleDue')
            ->with($idRecurringSchedule)
            ->willReturn(true);

        $this->createCondition($checkerMock)->check(
            (new StateMachineItemTransfer())->setIdentifier($idRecurringSchedule),
        );
    }

    protected function createCondition(RecurringScheduleDueCheckerInterface $recurringScheduleDueChecker): IsScheduleDueConditionPlugin
    {
        $businessFactoryMock = $this->getMockBuilder(OrderExperienceManagementBusinessFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $businessFactoryMock->method('createRecurringScheduleDueChecker')->willReturn($recurringScheduleDueChecker);

        return new class ($businessFactoryMock) extends IsScheduleDueConditionPlugin {
            public function __construct(private readonly OrderExperienceManagementBusinessFactory $businessFactoryOverride)
            {
            }

            public function getBusinessFactory(): OrderExperienceManagementBusinessFactory
            {
                return $this->businessFactoryOverride;
            }
        };
    }
}
