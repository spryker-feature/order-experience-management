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

class OrderTotalDriftApprovalValidator implements ScheduleApprovalValidatorInterface
{
    public function validate(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): ?ErrorTransfer {
        if ($recurringScheduleReviewResponseTransfer->getOrderReview() === null) {
            return null;
        }

        return (new ErrorTransfer())->setMessage(PriceDriftApprovalValidator::GLOSSARY_KEY_PRICES_CHANGED);
    }
}
