<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Quote;

use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface StandingScheduleQuoteOverrideApplierInterface
{
    public function applyStandingQuoteOverride(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer
    ): void;
}
