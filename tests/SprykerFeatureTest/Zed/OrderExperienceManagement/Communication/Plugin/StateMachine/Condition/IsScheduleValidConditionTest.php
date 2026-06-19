<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacade;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition\IsScheduleValidConditionPlugin;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Communication
 * @group Plugin
 * @group StateMachine
 * @group Condition
 * @group IsScheduleValidConditionPluginTest
 */
class IsScheduleValidConditionTest extends Unit
{
    protected OrderExperienceManagementBusinessTester $tester;

    public function testGetNameReturnsExpectedConditionName(): void
    {
        $this->assertSame('RecurringOrders/IsScheduleValid', (new IsScheduleValidConditionPlugin())->getName());
    }

    public function testCheckReturnsTrueWhenFacadeReturnsTrue(): void
    {
        $facadeMock = $this->createMock(OrderExperienceManagementFacade::class);
        $facadeMock->method('isRecurringScheduleValid')->willReturn(true);

        $this->assertTrue($this->createCondition($facadeMock)->check(
            (new StateMachineItemTransfer())->setIdentifier(1),
        ));
    }

    public function testCheckReturnsFalseWhenFacadeReturnsFalse(): void
    {
        $facadeMock = $this->createMock(OrderExperienceManagementFacade::class);
        $facadeMock->method('isRecurringScheduleValid')->willReturn(false);

        $this->assertFalse($this->createCondition($facadeMock)->check(
            (new StateMachineItemTransfer())->setIdentifier(1),
        ));
    }

    public function testCheckPassesScheduleIdentifierToFacade(): void
    {
        $idRecurringSchedule = 42;

        $facadeMock = $this->createMock(OrderExperienceManagementFacade::class);
        $facadeMock->expects($this->once())
            ->method('isRecurringScheduleValid')
            ->with($idRecurringSchedule)
            ->willReturn(true);

        $this->createCondition($facadeMock)->check(
            (new StateMachineItemTransfer())->setIdentifier($idRecurringSchedule),
        );
    }

    protected function createCondition(OrderExperienceManagementFacade $facade): IsScheduleValidConditionPlugin
    {
        return new class ($facade) extends IsScheduleValidConditionPlugin {
            public function __construct(private readonly OrderExperienceManagementFacade $facadeOverride)
            {
            }

            public function getFacade(): OrderExperienceManagementFacade
            {
                return $this->facadeOverride;
            }
        };
    }
}
