<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface RecurringSchedulePriceToleranceValidatorInterface
{
    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $requestedItemTransfers
     */
    public function findError(
        RecurringScheduleTransfer $requestedScheduleTransfer,
        array $requestedItemTransfers,
        RecurringScheduleTransfer $existingScheduleTransfer,
    ): ?ErrorTransfer;
}
