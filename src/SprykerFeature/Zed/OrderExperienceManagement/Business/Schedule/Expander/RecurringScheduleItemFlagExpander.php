<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander;

use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

class RecurringScheduleItemFlagExpander implements RecurringScheduleExpanderInterface
{
    public function isApplicable(RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer): bool
    {
        return (bool)$recurringScheduleCriteriaTransfer->getRecurringScheduleConditions()?->getIsWithItems();
    }

    public function expand(
        RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer,
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleCollectionTransfer {
        foreach ($recurringScheduleCollectionTransfer->getRecurringSchedules() as $recurringScheduleTransfer) {
            $this->applyItemFlags($recurringScheduleTransfer);
        }

        return $recurringScheduleCollectionTransfer;
    }

    protected function applyItemFlags(RecurringScheduleTransfer $recurringScheduleTransfer): void
    {
        foreach ($recurringScheduleTransfer->getItems() as $recurringScheduleItemTransfer) {
            if (!$this->isOneTimeItem($recurringScheduleItemTransfer)) {
                continue;
            }

            $recurringScheduleItemTransfer->addFlag(SharedOrderExperienceManagementConfig::ITEM_FLAG_ONE_TIME);
        }
    }

    protected function isOneTimeItem(RecurringScheduleItemTransfer $recurringScheduleItemTransfer): bool
    {
        $nextDeliveryQuantity = $recurringScheduleItemTransfer->getNextDeliveryQuantity();

        return $nextDeliveryQuantity !== null
            && $nextDeliveryQuantity !== $recurringScheduleItemTransfer->getQuantity();
    }
}
