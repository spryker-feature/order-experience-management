<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\ScheduleEventTriggerInterface;

class ScheduleReviewApprover implements ScheduleReviewApproverInterface
{
    protected const string ERROR_MESSAGE_APPROVE_FAILED = 'recurring_orders.review.approve_failed';

    protected const string ERROR_MESSAGE_ALL_ITEMS_REMOVED = 'recurring_orders.review.all_items_removed';

    protected const string ERROR_MESSAGE_PRICES_CHANGED = 'recurring_orders.review.prices_changed';

    public function __construct(
        protected readonly ScheduleReviewBuilderInterface $scheduleReviewBuilder,
        protected readonly ScheduleReviewChangeApplierInterface $scheduleReviewChangeApplier,
        protected readonly ScheduleEventTriggerInterface $scheduleEventTrigger,
    ) {
    }

    public function approve(RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer): RecurringScheduleEventResponseTransfer
    {
        $uuid = $recurringScheduleEventRequestTransfer->getUuidOrFail();
        $idCustomer = $recurringScheduleEventRequestTransfer->getIdCustomerOrFail();
        $customerTransfer = $recurringScheduleEventRequestTransfer->getCustomer();
        $acceptedItemReviewTransfers = $recurringScheduleEventRequestTransfer->getAcceptedItems()->getArrayCopy();

        $recurringScheduleReviewResponseTransfer = $this->scheduleReviewBuilder->buildApprovalReview(
            $this->buildCriteria($uuid, $idCustomer, $customerTransfer),
            $acceptedItemReviewTransfers,
        );

        $recurringScheduleTransfer = $recurringScheduleReviewResponseTransfer->getRecurringSchedule();

        if ($recurringScheduleTransfer === null || $recurringScheduleTransfer->getStatus() !== SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED) {
            return $this->createErrorResponse(static::ERROR_MESSAGE_APPROVE_FAILED);
        }

        if ($this->hasPriceDriftBeyondAccepted($recurringScheduleReviewResponseTransfer)) {
            return $this->createErrorResponse(static::ERROR_MESSAGE_PRICES_CHANGED);
        }

        if (!$this->hasItemsRemainingAfterApproval($recurringScheduleReviewResponseTransfer)) {
            return $this->createErrorResponse(static::ERROR_MESSAGE_ALL_ITEMS_REMOVED);
        }

        $this->scheduleReviewChangeApplier->applyApprovedChanges($recurringScheduleReviewResponseTransfer, $acceptedItemReviewTransfers);

        $isConfirmed = $this->scheduleEventTrigger->triggerEvent($uuid, SharedOrderExperienceManagementConfig::SM_EVENT_CONFIRM, $idCustomer, $customerTransfer);

        if (!$isConfirmed) {
            return $this->createErrorResponse(static::ERROR_MESSAGE_APPROVE_FAILED);
        }

        return (new RecurringScheduleEventResponseTransfer())->setIsSuccessful(true);
    }

    protected function hasPriceDriftBeyondAccepted(RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer): bool
    {
        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            if (in_array(SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED, $recurringScheduleItemReviewTransfer->getReviewReasons(), true)) {
                return true;
            }
        }

        return false;
    }

    protected function hasItemsRemainingAfterApproval(RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer): bool
    {
        if ($recurringScheduleReviewResponseTransfer->getUnchangedItems()->count() > 0) {
            return true;
        }

        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            if ($recurringScheduleItemReviewTransfer->getIsPurchasable() !== false) {
                return true;
            }
        }

        return false;
    }

    protected function buildCriteria(string $uuid, int $idCustomer, ?CustomerTransfer $customerTransfer): RecurringScheduleCriteriaTransfer
    {
        $customerTransfer ??= (new CustomerTransfer())->setIdCustomer($idCustomer);

        $recurringScheduleConditionsTransfer = (new RecurringScheduleConditionsTransfer())
            ->addUuid($uuid)
            ->setGroupItemsByGroupKey(true)
            ->setIsWithItems(true);

        return (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions($recurringScheduleConditionsTransfer)
            ->setCustomer($customerTransfer);
    }

    protected function createErrorResponse(string $message): RecurringScheduleEventResponseTransfer
    {
        return (new RecurringScheduleEventResponseTransfer())
            ->setIsSuccessful(false)
            ->addError((new ErrorTransfer())->setMessage($message));
    }
}
