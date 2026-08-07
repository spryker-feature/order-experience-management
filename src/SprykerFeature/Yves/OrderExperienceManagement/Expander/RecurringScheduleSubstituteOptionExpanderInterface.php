<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Expander;

use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;

interface RecurringScheduleSubstituteOptionExpanderInterface
{
    public function expandWithSubstituteOptions(
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): RecurringScheduleReviewResponseTransfer;
}
