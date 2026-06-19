<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Mapper;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\LocaleTransfer;
use Generated\Shared\Transfer\MailRecipientTransfer;
use Generated\Shared\Transfer\MailTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

class RecurringOrderNotificationMailMapper implements RecurringOrderNotificationMailMapperInterface
{
    /**
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Mail\RecurringOrderUpcomingNotificationMailTypeBuilderPlugin::MAIL_TYPE
     */
    protected const string MAIL_TYPE = 'recurring_orders.notify_buyer_upcoming_order';

    /**
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Mail\RecurringOrderValidationFailedMailTypeBuilderPlugin::MAIL_TYPE
     */
    protected const string VALIDATION_FAILED_MAIL_TYPE = 'recurring_orders.notify_buyer_validation_failed';

    protected const string MAIL_SUBJECT_KEY = 'recurring_orders.mail.notify_buyer_upcoming_order.subject';

    protected const string VALIDATION_FAILED_MAIL_SUBJECT_KEY = 'recurring_orders.mail.notify_buyer_validation_failed.subject';

    protected const string PLACEHOLDER_SCHEDULE_NAME = '%schedule_name%';

    /**
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Mail\RecurringOrderFailureMailTypeBuilderPlugin::MAIL_TYPE
     */
    protected const string FAILURE_MAIL_TYPE = 'recurring_orders.notify_buyer_placement_failure';

    protected const string FAILURE_MAIL_SUBJECT_KEY = 'recurring_orders.mail.notify_buyer_placement_failure.subject';

    protected const string PLACEHOLDER_EXECUTION_DATE = '%execution_date%';

    public function __construct(protected readonly OrderExperienceManagementConfig $subscriptionConfig)
    {
    }

    public function mapRecurringScheduleToMailTransfer(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        CustomerTransfer $buyerCustomerTransfer,
        CustomerTransfer $recipientCustomerTransfer,
    ): MailTransfer {
        $this->expandScheduleWithDetailUrl($recurringScheduleTransfer);

        return $this->buildBaseMailTransfer(static::MAIL_TYPE, static::MAIL_SUBJECT_KEY, $recurringScheduleTransfer, $buyerCustomerTransfer, $recipientCustomerTransfer)
            ->addSubjectTranslationParameter(static::PLACEHOLDER_EXECUTION_DATE, (string)$recurringScheduleTransfer->getNextTriggerDate());
    }

    public function mapRecurringScheduleToValidationFailedMailTransfer(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        CustomerTransfer $buyerCustomerTransfer,
        CustomerTransfer $recipientCustomerTransfer,
    ): MailTransfer {
        $this->expandScheduleWithReviewUrl($recurringScheduleTransfer);

        return $this->buildBaseMailTransfer(static::VALIDATION_FAILED_MAIL_TYPE, static::VALIDATION_FAILED_MAIL_SUBJECT_KEY, $recurringScheduleTransfer, $buyerCustomerTransfer, $recipientCustomerTransfer)
            ->addSubjectTranslationParameter(static::PLACEHOLDER_EXECUTION_DATE, (string)$recurringScheduleTransfer->getNextTriggerDate());
    }

    public function mapRecurringScheduleToFailureMailTransfer(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        CustomerTransfer $buyerCustomerTransfer,
        CustomerTransfer $recipientCustomerTransfer,
    ): MailTransfer {
        $this->expandScheduleWithDetailUrl($recurringScheduleTransfer);

        return $this->buildBaseMailTransfer(static::FAILURE_MAIL_TYPE, static::FAILURE_MAIL_SUBJECT_KEY, $recurringScheduleTransfer, $buyerCustomerTransfer, $recipientCustomerTransfer);
    }

    protected function buildBaseMailTransfer(
        string $type,
        string $subjectKey,
        RecurringScheduleTransfer $recurringScheduleTransfer,
        CustomerTransfer $buyerCustomerTransfer,
        CustomerTransfer $recipientCustomerTransfer,
    ): MailTransfer {
        return (new MailTransfer())
            ->setType($type)
            ->setLocale($this->resolveLocale($recurringScheduleTransfer, $buyerCustomerTransfer))
            ->setStoreName($recurringScheduleTransfer->getStoreName())
            ->setSubject($subjectKey)
            ->addSubjectTranslationParameter(static::PLACEHOLDER_SCHEDULE_NAME, (string)$recurringScheduleTransfer->getName())
            ->addRecipient($this->createRecipient($recipientCustomerTransfer))
            ->setRecurringSchedule($recurringScheduleTransfer)
            ->setCustomer($buyerCustomerTransfer);
    }

    protected function expandScheduleWithDetailUrl(RecurringScheduleTransfer $recurringScheduleTransfer): void
    {
        $uuid = $recurringScheduleTransfer->getUuid();

        if ($uuid === null) {
            return;
        }

        $recurringScheduleTransfer->setRecurringOrderDetailUrl(sprintf(
            $this->subscriptionConfig->getBaseUrlYves() . $this->subscriptionConfig->getRecurringOrderDetailUrlPath(),
            $uuid,
        ));
    }

    protected function expandScheduleWithReviewUrl(RecurringScheduleTransfer $recurringScheduleTransfer): void
    {
        $uuid = $recurringScheduleTransfer->getUuid();

        if ($uuid === null) {
            return;
        }

        $recurringScheduleTransfer->setRecurringOrderDetailUrl(sprintf(
            $this->subscriptionConfig->getBaseUrlYves() . $this->subscriptionConfig->getRecurringOrderReviewUrlPath(),
            $uuid,
        ));
    }

    protected function createRecipient(CustomerTransfer $recipientCustomerTransfer): MailRecipientTransfer
    {
        return (new MailRecipientTransfer())
            ->setEmail($recipientCustomerTransfer->getEmailOrFail())
            ->setName(sprintf('%s %s', $recipientCustomerTransfer->getFirstName(), $recipientCustomerTransfer->getLastName()));
    }

    protected function resolveLocale(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        CustomerTransfer $buyerCustomerTransfer,
    ): ?LocaleTransfer {
        $localeName = $recurringScheduleTransfer->getLocaleName();

        if ($localeName === null) {
            return $buyerCustomerTransfer->getLocale();
        }

        return (new LocaleTransfer())->setLocaleName($localeName);
    }
}
