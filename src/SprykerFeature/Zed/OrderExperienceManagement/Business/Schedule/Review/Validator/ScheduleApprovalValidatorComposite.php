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

class ScheduleApprovalValidatorComposite implements ScheduleApprovalValidatorInterface
{
    /**
     * @param array<\SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Validator\ScheduleApprovalValidatorInterface> $approvalValidators
     */
    public function __construct(
        protected readonly array $approvalValidators,
    ) {
    }

    public function validate(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): ?ErrorTransfer {
        foreach ($this->approvalValidators as $approvalValidator) {
            $errorTransfer = $approvalValidator->validate($recurringScheduleEventRequestTransfer, $recurringScheduleReviewResponseTransfer);

            if ($errorTransfer !== null) {
                return $errorTransfer;
            }
        }

        return null;
    }
}
