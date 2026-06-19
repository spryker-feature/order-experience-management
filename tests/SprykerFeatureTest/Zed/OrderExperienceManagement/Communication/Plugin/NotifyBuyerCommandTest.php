<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Plugin;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Spryker\Shared\Log\Config\LoggerConfigInterface;
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

        $businessFactoryMock = $this->getMockBuilder(OrderExperienceManagementBusinessFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $businessFactoryMock
            ->method('createRecurringOrderBuyerMailNotificationSender')
            ->willReturn($notificationSenderMock);

        $plugin = new class ($businessFactoryMock) extends NotifyBuyerCommandPlugin {
            public function __construct(private readonly OrderExperienceManagementBusinessFactory $factoryOverride)
            {
            }

            public function getBusinessFactory(): OrderExperienceManagementBusinessFactory
            {
                return $this->factoryOverride;
            }
        };

        $stateMachineItemTransfer = (new StateMachineItemTransfer())->setIdentifier($idRecurringSchedule);

        // Act
        $plugin->run($stateMachineItemTransfer);
    }

    public function testRunSwallowsExceptionAndLogsErrorWithoutRethrowing(): void
    {
        $idRecurringSchedule = 5;
        $exception = new RuntimeException('SMTP connection refused');

        $notificationSenderMock = $this->createMock(RecurringOrderBuyerMailNotificationSenderInterface::class);
        $notificationSenderMock->method('notifyUpcomingOrder')->willThrowException($exception);

        $businessFactoryMock = $this->getMockBuilder(OrderExperienceManagementBusinessFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $businessFactoryMock->method('createRecurringOrderBuyerMailNotificationSender')
            ->willReturn($notificationSenderMock);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains((string)$idRecurringSchedule));

        $plugin = new class ($businessFactoryMock, $loggerMock) extends NotifyBuyerCommandPlugin {
            public function __construct(
                private readonly OrderExperienceManagementBusinessFactory $factoryOverride,
                private readonly LoggerInterface $loggerOverride,
            ) {
            }

            public function getBusinessFactory(): OrderExperienceManagementBusinessFactory
            {
                return $this->factoryOverride;
            }

            public function getLogger(?LoggerConfigInterface $loggerConfig = null): LoggerInterface
            {
                return $this->loggerOverride;
            }
        };

        // run() must not throw — the SM must always advance out of notifying
        $plugin->run((new StateMachineItemTransfer())->setIdentifier($idRecurringSchedule));
    }
}
