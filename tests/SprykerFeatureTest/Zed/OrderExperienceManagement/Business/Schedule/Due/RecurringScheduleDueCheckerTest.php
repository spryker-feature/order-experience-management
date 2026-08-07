<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Due;

use Codeception\Test\Unit;
use DateTimeImmutable;
use Generated\Shared\Transfer\RecurringScheduleDueDataTransfer;
use Generated\Shared\Transfer\RecurringScheduleHistoryTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Due\RecurringScheduleDueChecker;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Due
 * @group RecurringScheduleDueCheckerTest
 * Add your own group annotations below this line
 */
class RecurringScheduleDueCheckerTest extends Unit
{
    protected const int ID_RECURRING_SCHEDULE = 1;

    public function testIsOrderPlacedReturnsFalseWhenNoHistoryEntryExists(): void
    {
        // Arrange
        $checker = $this->createChecker($this->createRepositoryMock('findLatestHistoryByScheduleId', null));

        // Act & Assert
        $this->assertFalse($checker->isOrderPlaced(static::ID_RECURRING_SCHEDULE));
    }

    public function testIsOrderPlacedReturnsFalseWhenLatestEventIsNotPlaced(): void
    {
        // Arrange
        $recurringScheduleHistoryTransfer = (new RecurringScheduleHistoryTransfer())
            ->setEventType(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_FAILED);
        $checker = $this->createChecker($this->createRepositoryMock('findLatestHistoryByScheduleId', $recurringScheduleHistoryTransfer));

        // Act & Assert
        $this->assertFalse($checker->isOrderPlaced(static::ID_RECURRING_SCHEDULE));
    }

    public function testIsOrderPlacedReturnsTrueWhenLatestEventIsPlaced(): void
    {
        // Arrange
        $recurringScheduleHistoryTransfer = (new RecurringScheduleHistoryTransfer())
            ->setEventType(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_PLACED);
        $checker = $this->createChecker($this->createRepositoryMock('findLatestHistoryByScheduleId', $recurringScheduleHistoryTransfer));

        // Act & Assert
        $this->assertTrue($checker->isOrderPlaced(static::ID_RECURRING_SCHEDULE));
    }

    public function testIsPlacementDueReturnsFalseWhenScheduleNotFound(): void
    {
        // Arrange
        $checker = $this->createChecker($this->createRepositoryMock('findRecurringScheduleDueData', null));

        // Act & Assert
        $this->assertFalse($checker->isPlacementDue(static::ID_RECURRING_SCHEDULE));
    }

    public function testIsPlacementDueReturnsFalseWhenTriggerDateIsInFuture(): void
    {
        // Arrange
        $recurringScheduleDueDataTransfer = (new RecurringScheduleDueDataTransfer())
            ->setNextTriggerDate((new DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s'));
        $checker = $this->createChecker($this->createRepositoryMock('findRecurringScheduleDueData', $recurringScheduleDueDataTransfer));

        // Act & Assert
        $this->assertFalse($checker->isPlacementDue(static::ID_RECURRING_SCHEDULE));
    }

    public function testIsPlacementDueReturnsTrueWhenTriggerDateIsInThePast(): void
    {
        // Arrange
        $recurringScheduleDueDataTransfer = (new RecurringScheduleDueDataTransfer())
            ->setNextTriggerDate((new DateTimeImmutable('-1 day'))->format('Y-m-d H:i:s'));
        $checker = $this->createChecker($this->createRepositoryMock('findRecurringScheduleDueData', $recurringScheduleDueDataTransfer));

        // Act & Assert
        $this->assertTrue($checker->isPlacementDue(static::ID_RECURRING_SCHEDULE));
    }

    public function testIsScheduleDueReturnsFalseWhenScheduleNotFound(): void
    {
        // Arrange
        $checker = $this->createChecker($this->createRepositoryMock('findRecurringScheduleDueData', null));

        // Act & Assert
        $this->assertFalse($checker->isScheduleDue(static::ID_RECURRING_SCHEDULE));
    }

    public function testIsScheduleDueReturnsFalseWhenNotificationWindowHasNotOpened(): void
    {
        // Arrange - trigger is 3 days away; with a 48 h window notifyFrom = +1 day → still in the future.
        $recurringScheduleDueDataTransfer = (new RecurringScheduleDueDataTransfer())
            ->setNextTriggerDate((new DateTimeImmutable('+3 days'))->format('Y-m-d H:i:s'))
            ->setNotificationWindowHours(48);
        $checker = $this->createChecker($this->createRepositoryMock('findRecurringScheduleDueData', $recurringScheduleDueDataTransfer));

        // Act & Assert
        $this->assertFalse($checker->isScheduleDue(static::ID_RECURRING_SCHEDULE));
    }

    public function testIsScheduleDueReturnsTrueWhenNotificationWindowHasOpened(): void
    {
        // Arrange - trigger is 1 day away; with a 48 h window notifyFrom = −1 day → already passed.
        $recurringScheduleDueDataTransfer = (new RecurringScheduleDueDataTransfer())
            ->setNextTriggerDate((new DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s'))
            ->setNotificationWindowHours(48);
        $checker = $this->createChecker($this->createRepositoryMock('findRecurringScheduleDueData', $recurringScheduleDueDataTransfer));

        // Act & Assert
        $this->assertTrue($checker->isScheduleDue(static::ID_RECURRING_SCHEDULE));
    }

    public function testIsScheduleDueUsesPerScheduleWindowHoursInsteadOfConfigDefault(): void
    {
        // Arrange - trigger ~49 h away; config default (48 h) → not due; schedule override (72 h) → due.
        $recurringScheduleDueDataTransfer = (new RecurringScheduleDueDataTransfer())
            ->setNextTriggerDate((new DateTimeImmutable('+49 hours'))->format('Y-m-d H:i:s'))
            ->setNotificationWindowHours(72);

        $configMock = $this->getMockBuilder(OrderExperienceManagementConfig::class)->disableOriginalConstructor()->getMock();
        $configMock->expects($this->never())->method('getDefaultNotificationWindowHours');

        $checker = $this->createChecker($this->createRepositoryMock('findRecurringScheduleDueData', $recurringScheduleDueDataTransfer), $configMock);

        // Act & Assert
        $this->assertTrue($checker->isScheduleDue(static::ID_RECURRING_SCHEDULE));
    }

    public function testIsScheduleDueFallsBackToConfigDefaultWhenScheduleWindowHoursIsNull(): void
    {
        // Arrange - trigger 1 day away; config default 48 h → notifyFrom = −1 day → due.
        $recurringScheduleDueDataTransfer = (new RecurringScheduleDueDataTransfer())
            ->setNextTriggerDate((new DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s'))
            ->setNotificationWindowHours(null);

        $configMock = $this->getMockBuilder(OrderExperienceManagementConfig::class)->disableOriginalConstructor()->getMock();
        $configMock->expects($this->once())->method('getDefaultNotificationWindowHours')->willReturn(48);

        $checker = $this->createChecker($this->createRepositoryMock('findRecurringScheduleDueData', $recurringScheduleDueDataTransfer), $configMock);

        // Act & Assert
        $this->assertTrue($checker->isScheduleDue(static::ID_RECURRING_SCHEDULE));
    }

    protected function createChecker(
        OrderExperienceManagementRepositoryInterface $repository,
        ?OrderExperienceManagementConfig $config = null,
    ): RecurringScheduleDueChecker {
        $configMock = $config ?? $this->getMockBuilder(OrderExperienceManagementConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        return new RecurringScheduleDueChecker($repository, $configMock);
    }

    protected function createRepositoryMock(string $method, mixed $returnValue): OrderExperienceManagementRepositoryInterface
    {
        $repositoryMock = $this->createMock(OrderExperienceManagementRepositoryInterface::class);
        $repositoryMock->method($method)->willReturn($returnValue);

        return $repositoryMock;
    }
}
