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
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

class ScheduleAwaitingReviewApprovalValidator implements ScheduleApprovalValidatorInterface
{
    protected const string GLOSSARY_KEY_APPROVE_FAILED = 'recurring_orders.review.approve_failed';

    public function validate(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): ?ErrorTransfer {
        $recurringScheduleTransfer = $recurringScheduleReviewResponseTransfer->getRecurringSchedule();

        if (
            $recurringScheduleTransfer !== null
            && $recurringScheduleTransfer->getStatus() === SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED
        ) {
            return null;
        }

        return (new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_APPROVE_FAILED);
    }
}
