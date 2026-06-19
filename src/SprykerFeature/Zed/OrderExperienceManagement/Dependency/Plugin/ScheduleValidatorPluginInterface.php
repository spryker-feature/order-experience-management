<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin;

use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;

interface ScheduleValidatorPluginInterface
{
    /**
     * Specification:
     * - Validates one aspect of the given recurring schedule.
     * - Adds item reviews and blocking errors to the provided result transfer when issues are found.
     * - Sets isValid=false on the result transfer when this aspect fails.
     * - Returns the result transfer unchanged when this aspect passes.
     *
     * @api
     */
    public function validate(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleValidationResultTransfer;
}
