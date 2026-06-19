<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Resolver;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface NotificationRecipientResolverInterface
{
    public function resolveRecipient(
        CustomerTransfer $buyerCustomerTransfer,
        RecurringScheduleTransfer $recurringScheduleTransfer,
    ): ?CustomerTransfer;
}
