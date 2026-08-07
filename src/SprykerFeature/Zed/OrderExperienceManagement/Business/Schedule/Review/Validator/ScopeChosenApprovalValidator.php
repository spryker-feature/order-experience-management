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

class ScopeChosenApprovalValidator implements ScheduleApprovalValidatorInterface
{
    protected const string GLOSSARY_KEY_SCOPE_REQUIRED = 'recurring_orders.review.scope_required';

    public function validate(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): ?ErrorTransfer {
        $isScopeChosen = in_array($recurringScheduleEventRequestTransfer->getScope(), [
            SharedOrderExperienceManagementConfig::SCOPE_STANDING,
            SharedOrderExperienceManagementConfig::SCOPE_OCCURRENCE,
        ], true);

        if ($isScopeChosen) {
            return null;
        }

        if ($recurringScheduleEventRequestTransfer->getAddedItems()->count() > 0) {
            return $this->createError();
        }

        foreach ($recurringScheduleEventRequestTransfer->getAcceptedItems() as $recurringScheduleItemReviewTransfer) {
            if ($recurringScheduleItemReviewTransfer->getAcceptedQuantity() !== null || $recurringScheduleItemReviewTransfer->getIsRemoved() === true) {
                return $this->createError();
            }
        }

        return null;
    }

    protected function createError(): ErrorTransfer
    {
        return (new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_SCOPE_REQUIRED);
    }
}
