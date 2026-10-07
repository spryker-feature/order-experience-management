<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Formatter;

use Generated\Shared\Transfer\RecurringScheduleTransfer;

interface CadenceLabelFormatterInterface
{
    public function formatCadence(RecurringScheduleTransfer $recurringScheduleTransfer): string;

    public function formatCadenceWithWeekDays(RecurringScheduleTransfer $recurringScheduleTransfer): string;
}
