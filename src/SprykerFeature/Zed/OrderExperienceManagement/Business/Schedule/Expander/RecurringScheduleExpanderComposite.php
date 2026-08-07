<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander;

use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;

class RecurringScheduleExpanderComposite implements RecurringScheduleExpanderInterface
{
    /**
     * @param array<\SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleExpanderInterface> $recurringScheduleExpanders
     */
    public function __construct(
        protected readonly array $recurringScheduleExpanders,
    ) {
    }

    public function isApplicable(RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer): bool
    {
        return true;
    }

    public function expand(
        RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer,
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleCollectionTransfer {
        foreach ($this->recurringScheduleExpanders as $recurringScheduleExpander) {
            if (!$recurringScheduleExpander->isApplicable($recurringScheduleCriteriaTransfer)) {
                continue;
            }

            $recurringScheduleCollectionTransfer = $recurringScheduleExpander->expand(
                $recurringScheduleCollectionTransfer,
                $recurringScheduleCriteriaTransfer,
            );
        }

        return $recurringScheduleCollectionTransfer;
    }
}
