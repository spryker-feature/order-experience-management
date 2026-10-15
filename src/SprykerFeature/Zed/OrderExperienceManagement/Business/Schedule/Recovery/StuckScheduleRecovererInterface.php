<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Recovery;

use Generated\Shared\Transfer\RecurringScheduleRecoveryResultTransfer;

interface StuckScheduleRecovererInterface
{
    public function recoverStuckSchedules(): RecurringScheduleRecoveryResultTransfer;
}
