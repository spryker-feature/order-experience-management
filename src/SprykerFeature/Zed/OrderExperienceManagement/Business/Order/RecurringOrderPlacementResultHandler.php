<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order;

use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\RecurringOrderBuyerMailNotificationSenderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\History\RecurringSchedulePlacementHistoryWriterInterface;
use Throwable;

class RecurringOrderPlacementResultHandler implements RecurringOrderPlacementResultHandlerInterface
{
    use LoggerTrait;

    public function __construct(
        protected RecurringSchedulePlacementHistoryWriterInterface $historyWriter,
        protected RecurringOrderBuyerMailNotificationSenderInterface $mailNotificationSender,
    ) {
    }

    public function handleResult(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        CheckoutResponseTransfer $checkoutResponseTransfer
    ): void {
        $this->historyWriter->writeHistory($recurringScheduleTransfer, $checkoutResponseTransfer);
        $this->notifyOnFailure($recurringScheduleTransfer, $checkoutResponseTransfer);
    }

    protected function notifyOnFailure(RecurringScheduleTransfer $recurringScheduleTransfer, CheckoutResponseTransfer $checkoutResponseTransfer): void
    {
        if ($checkoutResponseTransfer->getIsSuccess()) {
            return;
        }

        try {
            $this->mailNotificationSender->notifyPlacementFailure($recurringScheduleTransfer->getIdRecurringScheduleOrFail());
        } catch (Throwable $throwable) {
            $this->getLogger()->error(
                sprintf('Placement failure notification email could not be sent for schedule ID %d: %s', $recurringScheduleTransfer->getIdRecurringScheduleOrFail(), $throwable->getMessage()),
                ['exception' => $throwable],
            );
        }
    }
}
