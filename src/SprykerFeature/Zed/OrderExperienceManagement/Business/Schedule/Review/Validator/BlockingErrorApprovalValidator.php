<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Validator;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\RecurringScheduleErrorTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

class BlockingErrorApprovalValidator implements ScheduleApprovalValidatorInterface
{
    public function validate(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): ?ErrorTransfer {
        $hasAddedItems = $recurringScheduleEventRequestTransfer->getAddedItems()->count() > 0;

        foreach ($recurringScheduleReviewResponseTransfer->getBlockingErrors() as $recurringScheduleErrorTransfer) {
            if ($recurringScheduleErrorTransfer->getIsSuccess() === true) {
                continue;
            }

            if ($hasAddedItems && $this->isEmptyOrderError($recurringScheduleErrorTransfer)) {
                continue;
            }

            return (new ErrorTransfer())
                ->setMessage($recurringScheduleErrorTransfer->getMessageOrFail())
                ->setParameters($recurringScheduleErrorTransfer->getParameters());
        }

        return null;
    }

    protected function isEmptyOrderError(RecurringScheduleErrorTransfer $recurringScheduleErrorTransfer): bool
    {
        return $recurringScheduleErrorTransfer->getCode() === SharedOrderExperienceManagementConfig::REVIEW_ERROR_CODE_EMPTY_ORDER;
    }
}
