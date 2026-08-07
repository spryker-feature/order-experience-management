<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Validator;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;

class ItemsRemainingApprovalValidator implements ScheduleApprovalValidatorInterface
{
    protected const string GLOSSARY_KEY_ALL_ITEMS_REMOVED = 'recurring_orders.review.all_items_removed';

    public function validate(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): ?ErrorTransfer {
        if ($recurringScheduleEventRequestTransfer->getAddedItems()->count() > 0) {
            return null;
        }

        if ($this->hasItemsRemainingAfterApproval($recurringScheduleReviewResponseTransfer)) {
            return null;
        }

        return (new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_ALL_ITEMS_REMOVED);
    }

    protected function hasItemsRemainingAfterApproval(RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer): bool
    {
        if ($recurringScheduleReviewResponseTransfer->getUnchangedItems()->count() > 0) {
            return true;
        }

        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            if ($recurringScheduleItemReviewTransfer->getIsPurchasable() !== false) {
                return true;
            }
        }

        return false;
    }
}
