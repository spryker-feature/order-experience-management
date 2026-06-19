<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander;

use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;

abstract class AbstractRecurringScheduleExpander
{
    /**
     * @return array<int>
     */
    protected function extractScheduleIds(
        RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer,
    ): array {
        $scheduleIds = [];

        foreach ($recurringScheduleCollectionTransfer->getRecurringSchedules() as $recurringScheduleTransfer) {
            $scheduleIds[] = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();
        }

        return $scheduleIds;
    }
}
