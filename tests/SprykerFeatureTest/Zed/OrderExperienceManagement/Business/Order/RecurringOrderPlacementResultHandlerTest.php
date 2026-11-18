<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Order;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use RuntimeException;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\RecurringOrderBuyerMailNotificationSenderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\RecurringOrderPlacementResultHandler;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\History\RecurringSchedulePlacementHistoryWriterInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Order
 * @group RecurringOrderPlacementResultHandlerTest
 * Add your own group annotations below this line
 */
class RecurringOrderPlacementResultHandlerTest extends Unit
{
    public function testHandleResultWritesHistoryAndSkipsNotificationOnSuccess(): void
    {
        // Arrange
        $historyWriterMock = $this->createMock(RecurringSchedulePlacementHistoryWriterInterface::class);
        $historyWriterMock->expects($this->once())->method('writeHistory');
        $mailSenderMock = $this->createMock(RecurringOrderBuyerMailNotificationSenderInterface::class);
        $mailSenderMock->expects($this->never())->method('notifyPlacementFailure');

        // Act
        (new RecurringOrderPlacementResultHandler($historyWriterMock, $mailSenderMock))->handleResult(
            (new RecurringScheduleTransfer())->setIdRecurringSchedule(1),
            (new CheckoutResponseTransfer())->setIsSuccess(true),
        );
    }

    public function testHandleResultNotifiesBuyerOnFailure(): void
    {
        // Arrange
        $historyWriterMock = $this->createMock(RecurringSchedulePlacementHistoryWriterInterface::class);
        $historyWriterMock->expects($this->once())->method('writeHistory');
        $mailSenderMock = $this->createMock(RecurringOrderBuyerMailNotificationSenderInterface::class);
        $mailSenderMock->expects($this->once())->method('notifyPlacementFailure')->with(7);

        // Act
        (new RecurringOrderPlacementResultHandler($historyWriterMock, $mailSenderMock))->handleResult(
            (new RecurringScheduleTransfer())->setIdRecurringSchedule(7),
            (new CheckoutResponseTransfer())->setIsSuccess(false),
        );
    }

    public function testHandleResultDoesNotThrowWhenNotificationFails(): void
    {
        // Arrange
        $historyWriterMock = $this->createMock(RecurringSchedulePlacementHistoryWriterInterface::class);
        $mailSenderMock = $this->createMock(RecurringOrderBuyerMailNotificationSenderInterface::class);
        $mailSenderMock->method('notifyPlacementFailure')->willThrowException(new RuntimeException('smtp down'));

        // Act
        (new RecurringOrderPlacementResultHandler($historyWriterMock, $mailSenderMock))->handleResult(
            (new RecurringScheduleTransfer())->setIdRecurringSchedule(7),
            (new CheckoutResponseTransfer())->setIsSuccess(false),
        );

        // Assert
        $this->addToAssertionCount(1);
    }
}
