<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander;

use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Grouping\RecurringScheduleItemGrouperInterface;

class RecurringScheduleGroupingExpander implements RecurringScheduleExpanderInterface
{
    public function __construct(
        protected RecurringScheduleItemGrouperInterface $recurringScheduleItemGrouper,
    ) {
    }

    public function isApplicable(RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer): bool
    {
        return (bool)$recurringScheduleCriteriaTransfer->getRecurringScheduleConditions()?->getIsGroupedByGroupKey();
    }

    public function expand(
        RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer,
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleCollectionTransfer {
        foreach ($recurringScheduleCollectionTransfer->getRecurringSchedules() as $recurringScheduleTransfer) {
            $recurringScheduleTransfer = $this->recurringScheduleItemGrouper->groupItems($recurringScheduleTransfer);
        }

        return $recurringScheduleCollectionTransfer;
    }
}
