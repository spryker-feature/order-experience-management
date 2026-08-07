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

class PriceDriftApprovalValidator implements ScheduleApprovalValidatorInterface
{
    protected const string GLOSSARY_KEY_PRICES_CHANGED = 'recurring_orders.review.prices_changed';

    public function validate(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): ?ErrorTransfer {
        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            if (in_array(SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED, $recurringScheduleItemReviewTransfer->getReviewReasons(), true)) {
                return (new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_PRICES_CHANGED);
            }
        }

        return null;
    }
}
