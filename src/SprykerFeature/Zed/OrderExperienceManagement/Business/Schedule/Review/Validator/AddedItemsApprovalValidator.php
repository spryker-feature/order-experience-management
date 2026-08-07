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
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\ScheduleReviewItemAdditionValidatorInterface;

class AddedItemsApprovalValidator implements ScheduleApprovalValidatorInterface
{
    public function __construct(
        protected readonly ScheduleReviewItemAdditionValidatorInterface $scheduleReviewItemAdditionValidator,
    ) {
    }

    public function validate(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): ?ErrorTransfer {
        return $this->scheduleReviewItemAdditionValidator->validate(
            $recurringScheduleEventRequestTransfer->getAddedItems()->getArrayCopy(),
            $recurringScheduleReviewResponseTransfer->getResolvedAddedItems(),
            $recurringScheduleReviewResponseTransfer->getRecurringScheduleOrFail(),
        );
    }
}
