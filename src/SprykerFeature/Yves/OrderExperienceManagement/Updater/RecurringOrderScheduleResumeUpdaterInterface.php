<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Updater;

use DateTimeInterface;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventResponseTransfer;

interface RecurringOrderScheduleResumeUpdaterInterface
{
    public function resumeWithDate(
        string $uuid,
        CustomerTransfer $customerTransfer,
        DateTimeInterface $nextExecutionDate,
    ): RecurringScheduleEventResponseTransfer;
}
