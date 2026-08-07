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
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Indexer\RecurringScheduleItemIndexerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

class CheckoutValidationResultBuilder implements CheckoutValidationResultBuilderInterface
{
    protected const string GLOSSARY_KEY_ALL_ITEMS_SKIPPED = 'recurring_orders.review.all_items_removed';

    public function __construct(
        protected readonly OrderExperienceManagementConfig $config,
        protected readonly RecurringScheduleItemIndexerInterface $recurringScheduleItemIndexer,
    ) {
    }

    public function buildValidationResult(
        CheckoutResponseTransfer $checkoutResponseTransfer,
        RecurringScheduleTransfer $recurringScheduleTransfer,
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleValidationResultTransfer {
        $itemsByGroupKey = $this->recurringScheduleItemIndexer->indexByGroupKey($recurringScheduleTransfer);
        $recurringScheduleValidationResultTransfer->setIsValid($checkoutResponseTransfer->getIsSuccess());

        foreach ($checkoutResponseTransfer->getErrors() as $checkoutErrorTransfer) {
            $groupKey = $checkoutErrorTransfer->getGroupKey();
            $errorType = $checkoutErrorTransfer->getErrorType();

            if ($groupKey === null || !isset($itemsByGroupKey[$groupKey]) || !$errorType) {
                $recurringScheduleValidationResultTransfer->addBlockingError(
                    (new RecurringScheduleErrorTransfer())
                        ->setMessage($checkoutErrorTransfer->getMessage())
                        ->setParameters($checkoutErrorTransfer->getParameters())
                        ->setIsSuccess($checkoutResponseTransfer->getIsSuccess()),
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

    public function buildEmptyOrderValidationResult(
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleValidationResultTransfer {
        return $recurringScheduleValidationResultTransfer
            ->setIsValid(false)
            ->addBlockingError(
                (new RecurringScheduleErrorTransfer())
                    ->setMessage(static::GLOSSARY_KEY_ALL_ITEMS_SKIPPED)
                    ->setCode(SharedOrderExperienceManagementConfig::REVIEW_ERROR_CODE_EMPTY_ORDER)
                    ->setIsSuccess(false),
            );
    }

    protected function resolveReviewReasonGroup(string $errorType): string
    {
        foreach ($this->config->getReviewReasonGroupMap() as $reviewReasonGroup => $errorTypes) {
            if (in_array($errorType, $errorTypes, true)) {
                return $reviewReasonGroup;
            }
        }

        return $this->config->getDefaultReviewReasonGroup();
    }
}
