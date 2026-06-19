<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Notification;

use Spryker\Zed\Mail\Business\MailFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Mapper\RecurringOrderNotificationMailMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Reader\RecurringScheduleBuyerReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Resolver\NotificationRecipientResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class RecurringOrderBuyerMailNotificationSender implements RecurringOrderBuyerMailNotificationSenderInterface
{
    public function __construct(
        protected readonly OrderExperienceManagementRepositoryInterface $subscriptionRepository,
        protected readonly RecurringScheduleBuyerReaderInterface $buyerReader,
        protected readonly NotificationRecipientResolverInterface $recipientResolver,
        private readonly RecurringOrderNotificationMailMapperInterface $mailMapper,
        protected readonly MailFacadeInterface $mailFacade,
    ) {
    }

    public function notifyUpcomingOrder(int $idRecurringSchedule): void
    {
        $this->sendNotification(
            $idRecurringSchedule,
            fn ($schedule, $buyer, $recipient) => $this->mailMapper->mapRecurringScheduleToMailTransfer($schedule, $buyer, $recipient),
        );
    }

    public function notifyValidationFailed(int $idRecurringSchedule): void
    {
        $this->sendNotification(
            $idRecurringSchedule,
            fn ($schedule, $buyer, $recipient) => $this->mailMapper->mapRecurringScheduleToValidationFailedMailTransfer($schedule, $buyer, $recipient),
        );
    }

    public function notifyPlacementFailure(int $idRecurringSchedule): void
    {
        $this->sendNotification(
            $idRecurringSchedule,
            fn ($schedule, $buyer, $recipient) => $this->mailMapper->mapRecurringScheduleToFailureMailTransfer($schedule, $buyer, $recipient),
        );
    }

    /**
     * @param callable(\Generated\Shared\Transfer\RecurringScheduleTransfer, \Generated\Shared\Transfer\CustomerTransfer, \Generated\Shared\Transfer\CustomerTransfer): \Generated\Shared\Transfer\MailTransfer $buildMailTransfer
     */
    protected function sendNotification(int $idRecurringSchedule, callable $buildMailTransfer): void
    {
        $recurringScheduleTransfer = $this->subscriptionRepository->findRecurringScheduleById($idRecurringSchedule);

        if ($recurringScheduleTransfer === null) {
            return;
        }

        $buyerCustomerTransfer = $this->buyerReader->findBuyerCustomer($recurringScheduleTransfer);

        if ($buyerCustomerTransfer === null) {
            return;
        }

        $recipientCustomerTransfer = $this->recipientResolver->resolveRecipient($buyerCustomerTransfer, $recurringScheduleTransfer);

        if ($recipientCustomerTransfer === null) {
            return;
        }

        $this->mailFacade->handleMail($buildMailTransfer($recurringScheduleTransfer, $buyerCustomerTransfer, $recipientCustomerTransfer));
    }
}
