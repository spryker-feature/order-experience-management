<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\Validator;

use Generated\Shared\Transfer\CadenceValidationResponseTransfer;

interface WeekDayOptionsValidatorInterface
{
    /**
     * @param array<string> $cadenceOptions
     */
    public function areWeekDaysValid(array $cadenceOptions): CadenceValidationResponseTransfer;
}
