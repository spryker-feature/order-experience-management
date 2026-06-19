<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator;

use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

class RecurringScheduleValidationResultExpander implements RecurringScheduleValidationResultExpanderInterface
{
    public function __construct(
        protected readonly OrderExperienceManagementConfig $subscriptionConfig,
    ) {
    }

    public function expand(RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer): RecurringScheduleValidationResultTransfer
    {
        $nonPurchasableReviewReasonGroups = $this->subscriptionConfig->getNonPurchasableReviewReasonGroups();

        foreach ($recurringScheduleValidationResultTransfer->getItemReviews() as $itemReviewTransfer) {
            foreach ($itemReviewTransfer->getReviewReasons() as $reviewReason) {
                if (!in_array($reviewReason, $nonPurchasableReviewReasonGroups, true)) {
                    continue;
                }

                $itemReviewTransfer->setIsPurchasable(false);

                break;
            }
        }

        return $recurringScheduleValidationResultTransfer;
    }
}
