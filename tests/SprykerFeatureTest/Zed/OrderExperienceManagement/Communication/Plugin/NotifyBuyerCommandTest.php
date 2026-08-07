<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Plugin;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\RecurringOrderBuyerMailNotificationSenderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Command\NotifyBuyerCommandPlugin;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Communication
 * @group Plugin
 * @group NotifyBuyerCommandPluginTest
 * Add your own group annotations below this line
 */
class NotifyBuyerCommandTest extends Unit
{
    protected OrderExperienceManagementBusinessTester $tester;

    public function testGetNameReturnsExpectedCommandName(): void
    {
        // Arrange
        $plugin = new NotifyBuyerCommandPlugin();

        // Act
        $name = $plugin->getName();

        // Assert
        $this->assertSame('RecurringOrders/NotifyBuyer', $name);
    }

    public function testRunDelegatesNotificationToSender(): void
    {
        // Arrange
        $idRecurringSchedule = 42;

        $notificationSenderMock = $this->createMock(RecurringOrderBuyerMailNotificationSenderInterface::class);
        $notificationSenderMock
            ->expects($this->once())
            ->method('notifyUpcomingOrder')
            ->with($idRecurringSchedule);

        $plugin = $this->createPlugin($notificationSenderMock);

        // Act
        $plugin->run((new StateMachineItemTransfer())->setIdentifier($idRecurringSchedule));
    }

    protected function createPlugin(
        RecurringOrderBuyerMailNotificationSenderInterface $notificationSender,
    ): NotifyBuyerCommandPlugin {
        $businessFactoryMock = $this->getMockBuilder(OrderExperienceManagementBusinessFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $businessFactoryMock->method('createRecurringOrderBuyerMailNotificationSender')->willReturn($notificationSender);

        return new class ($businessFactoryMock) extends NotifyBuyerCommandPlugin {
            public function __construct(
                private readonly OrderExperienceManagementBusinessFactory $businessFactoryOverride,
            ) {
            }

            public function getBusinessFactory(): OrderExperienceManagementBusinessFactory
            {
                return $this->businessFactoryOverride;
            }
        };
    }
}
