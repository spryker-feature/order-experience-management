<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Mapper;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\MailTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface RecurringOrderNotificationMailMapperInterface
{
    public function mapRecurringScheduleToMailTransfer(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        CustomerTransfer $buyerCustomerTransfer,
        CustomerTransfer $recipientCustomerTransfer,
    ): MailTransfer;

    public function mapRecurringScheduleToValidationFailedMailTransfer(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        CustomerTransfer $buyerCustomerTransfer,
        CustomerTransfer $recipientCustomerTransfer,
    ): MailTransfer;

    public function mapRecurringScheduleToFailureMailTransfer(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        CustomerTransfer $buyerCustomerTransfer,
        CustomerTransfer $recipientCustomerTransfer,
    ): MailTransfer;
}
