<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator;

use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleErrorTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

class CheckoutValidationResultBuilder implements CheckoutValidationResultBuilderInterface
{
    public function __construct(
        protected readonly OrderExperienceManagementConfig $subscriptionConfig,
    ) {
    }

    public function buildValidationResult(
        CheckoutResponseTransfer $checkoutResponseTransfer,
        RecurringScheduleTransfer $recurringScheduleTransfer,
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleValidationResultTransfer {
        $itemsByGroupKey = $this->indexItemsByGroupKey($recurringScheduleTransfer);
        $recurringScheduleValidationResultTransfer->setIsValid(false);

        foreach ($checkoutResponseTransfer->getErrors() as $checkoutErrorTransfer) {
            $groupKey = $checkoutErrorTransfer->getGroupKey();
            $errorType = $checkoutErrorTransfer->getErrorType();

            if ($groupKey === null || !isset($itemsByGroupKey[$groupKey]) || !$errorType) {
                $recurringScheduleValidationResultTransfer->addBlockingError(
                    (new RecurringScheduleErrorTransfer())->setMessage($checkoutErrorTransfer->getMessage()),
                );

                continue;
            }

            $recurringScheduleItemReviewTransfer = (new RecurringScheduleItemReviewTransfer())
                ->setRecurringScheduleItem($itemsByGroupKey[$groupKey]);
            $recurringScheduleItemReviewTransfer->addReviewReason($this->resolveReviewReasonGroup($errorType));

            $recurringScheduleValidationResultTransfer->addItemReview($recurringScheduleItemReviewTransfer);
        }

        return $recurringScheduleValidationResultTransfer;
    }

    protected function resolveReviewReasonGroup(string $errorType): string
    {
        foreach ($this->subscriptionConfig->getReviewReasonGroupMap() as $reviewReasonGroup => $errorTypes) {
            if (in_array($errorType, $errorTypes, true)) {
                return $reviewReasonGroup;
            }
        }

        return $this->subscriptionConfig->getDefaultReviewReasonGroup();
    }

    /**
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    protected function indexItemsByGroupKey(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        $itemsByGroupKey = [];

        foreach ($recurringScheduleTransfer->getItems() as $recurringScheduleItemTransfer) {
            $groupKey = $recurringScheduleItemTransfer->getGroupKey();

            if ($groupKey === null) {
                continue;
            }

            $itemsByGroupKey[$groupKey] = $recurringScheduleItemTransfer;
        }

        return $itemsByGroupKey;
    }
}
