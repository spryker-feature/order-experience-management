<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Validator;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Spryker\Shared\Log\Config\LoggerConfigInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\RecurringOrderBuyerMailNotificationSenderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\RecurringSchedulePrePlacementValidator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\RecurringScheduleValidationResultExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\ScheduleValidatorPluginInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Validator
 * @group RecurringSchedulePrePlacementValidatorTest
 */
class RecurringSchedulePrePlacementValidatorTest extends Unit
{
    protected OrderExperienceManagementBusinessTester $tester;

    public function testIsRecurringScheduleValidReturnsFalseWhenScheduleNotFound(): void
    {
        $repositoryMock = $this->createMock(OrderExperienceManagementRepositoryInterface::class);
        $repositoryMock->method('findRecurringScheduleById')->willReturn(null);

        $result = $this->createValidator(repository: $repositoryMock)->isRecurringScheduleValid(1);

        $this->assertFalse($result);
    }

    public function testIsRecurringScheduleValidReturnsTrueWhenAllPluginsPass(): void
    {
        $result = $this->createValidator()->isRecurringScheduleValid(1);

        $this->assertTrue($result);
    }

    public function testIsRecurringScheduleValidReturnsFalseAndSendsNotificationWhenValidationFails(): void
    {
        $notificationSenderMock = $this->createMock(RecurringOrderBuyerMailNotificationSenderInterface::class);
        $notificationSenderMock->expects($this->once())->method('notifyValidationFailed')->with(1);

        $result = $this->createValidator(
            notificationSender: $notificationSenderMock,
            scheduleValidatorPlugins: [$this->createInvalidatingPlugin()],
        )->isRecurringScheduleValid(1);

        $this->assertFalse($result);
    }

    public function testIsRecurringScheduleValidReturnsFalseAndLogsErrorWhenMailNotificationThrows(): void
    {
        $idRecurringSchedule = 7;
        $exception = new RuntimeException('SMTP connection refused');

        $notificationSenderMock = $this->createMock(RecurringOrderBuyerMailNotificationSenderInterface::class);
        $notificationSenderMock->method('notifyValidationFailed')->willThrowException($exception);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains((string)$idRecurringSchedule));

        $result = $this->createValidatorWithLogger(
            loggerOverride: $loggerMock,
            notificationSender: $notificationSenderMock,
            scheduleValidatorPlugins: [$this->createInvalidatingPlugin()],
        )->isRecurringScheduleValid($idRecurringSchedule);

        $this->assertFalse($result);
    }

    protected function createInvalidatingPlugin(): ScheduleValidatorPluginInterface
    {
        $plugin = $this->createMock(ScheduleValidatorPluginInterface::class);
        $plugin->method('validate')->willReturnCallback(
            static fn (RecurringScheduleTransfer $schedule, RecurringScheduleValidationResultTransfer $result): RecurringScheduleValidationResultTransfer => $result->setIsValid(false),
        );

        return $plugin;
    }

    protected function createValidator(
        ?OrderExperienceManagementRepositoryInterface $repository = null,
        ?RecurringOrderBuyerMailNotificationSenderInterface $notificationSender = null,
        array $scheduleValidatorPlugins = [],
    ): RecurringSchedulePrePlacementValidator {
        $expanderMock = $this->createMock(RecurringScheduleValidationResultExpanderInterface::class);
        $expanderMock->method('expand')->willReturnArgument(0);

        return new RecurringSchedulePrePlacementValidator(
            $repository ?? $this->createRepositoryReturningSchedule(),
            $this->createMock(OrderExperienceManagementEntityManagerInterface::class),
            $notificationSender ?? $this->createMock(RecurringOrderBuyerMailNotificationSenderInterface::class),
            $scheduleValidatorPlugins,
            $expanderMock,
        );
    }

    protected function createValidatorWithLogger(
        LoggerInterface $loggerOverride,
        ?RecurringOrderBuyerMailNotificationSenderInterface $notificationSender = null,
        array $scheduleValidatorPlugins = [],
    ): RecurringSchedulePrePlacementValidator {
        $expanderMock = $this->createMock(RecurringScheduleValidationResultExpanderInterface::class);
        $expanderMock->method('expand')->willReturnArgument(0);

        return new class (
            $this->createRepositoryReturningSchedule(),
            $this->createMock(OrderExperienceManagementEntityManagerInterface::class),
            $notificationSender ?? $this->createMock(RecurringOrderBuyerMailNotificationSenderInterface::class),
            $scheduleValidatorPlugins,
            $expanderMock,
            $loggerOverride,
        ) extends RecurringSchedulePrePlacementValidator {
            public function __construct(
                OrderExperienceManagementRepositoryInterface $subscriptionRepository,
                OrderExperienceManagementEntityManagerInterface $subscriptionEntityManager,
                RecurringOrderBuyerMailNotificationSenderInterface $recurringOrderBuyerMailNotificationSender,
                array $scheduleValidatorPlugins,
                RecurringScheduleValidationResultExpanderInterface $recurringScheduleValidationResultExpander,
                private readonly LoggerInterface $loggerOverride,
            ) {
                parent::__construct(
                    $subscriptionRepository,
                    $subscriptionEntityManager,
                    $recurringOrderBuyerMailNotificationSender,
                    $scheduleValidatorPlugins,
                    $recurringScheduleValidationResultExpander,
                );
            }

            public function getLogger(?LoggerConfigInterface $loggerConfig = null): LoggerInterface
            {
                return $this->loggerOverride;
            }
        };
    }

    protected function createRepositoryReturningSchedule(): OrderExperienceManagementRepositoryInterface
    {
        $repositoryMock = $this->createMock(OrderExperienceManagementRepositoryInterface::class);
        $repositoryMock->method('findRecurringScheduleById')->willReturn(new RecurringScheduleTransfer());

        return $repositoryMock;
    }
}
