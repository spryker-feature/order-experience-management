<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface ScheduleEventTriggerInterface
{
    public function triggerEvent(string $uuid, string $event, int $idCustomer, ?CustomerTransfer $customerTransfer = null): bool;

    public function triggerEventForRecurringSchedule(RecurringScheduleTransfer $recurringScheduleTransfer, string $event): bool;

    public function triggerManualEvent(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
    ): RecurringScheduleEventResponseTransfer;
}
