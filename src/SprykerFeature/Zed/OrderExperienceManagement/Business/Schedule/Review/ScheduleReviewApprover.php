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
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Exception\ScheduleReviewConfirmationException;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\AddedItemResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Quote\StandingScheduleQuoteOverrideApplierInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Validator\ScheduleApprovalValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\ScheduleEventTriggerInterface;

class ScheduleReviewApprover implements ScheduleReviewApproverInterface
{
    use TransactionTrait;

    protected const string GLOSSARY_KEY_APPROVE_FAILED = 'recurring_orders.review.approve_failed';

    public function __construct(
        protected readonly ScheduleReviewBuilderInterface $scheduleReviewBuilder,
        protected readonly ScheduleReviewChangeApplierInterface $scheduleReviewChangeApplier,
        protected readonly ScheduleEventTriggerInterface $scheduleEventTrigger,
        protected readonly ScheduleApprovalValidatorInterface $scheduleApprovalValidator,
        protected readonly AddedItemResolverInterface $addedItemResolver,
        protected readonly StandingScheduleQuoteOverrideApplierInterface $standingScheduleQuoteOverrideApplier,
    ) {
    }

    public function approve(RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer): RecurringScheduleEventResponseTransfer
    {
        $recurringScheduleReviewResponseTransfer = $this->scheduleReviewBuilder->buildApprovalReview(
            $this->buildCriteria($recurringScheduleEventRequestTransfer),
            $recurringScheduleEventRequestTransfer->getAcceptedItems()->getArrayCopy(),
            $recurringScheduleEventRequestTransfer->getQuote(),
        );

        $recurringScheduleReviewResponseTransfer = $this->resolveAddedItems(
            $recurringScheduleEventRequestTransfer,
            $recurringScheduleReviewResponseTransfer,
        );

        $errorTransfer = $this->scheduleApprovalValidator->validate(
            $recurringScheduleEventRequestTransfer,
            $recurringScheduleReviewResponseTransfer,
        );

        if ($errorTransfer !== null) {
            return $this->createErrorResponse($errorTransfer->getMessageOrFail(), $errorTransfer->getParameters());
        }

        return $this->applyApprovedChangesAndConfirm($recurringScheduleEventRequestTransfer, $recurringScheduleReviewResponseTransfer);
    }

    protected function buildCriteria(RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer): RecurringScheduleCriteriaTransfer
    {
        $customerTransfer = $recurringScheduleEventRequestTransfer->getCustomer()
            ?? (new CustomerTransfer())->setIdCustomer($recurringScheduleEventRequestTransfer->getIdCustomerOrFail());

        $recurringScheduleConditionsTransfer = (new RecurringScheduleConditionsTransfer())
            ->addUuid($recurringScheduleEventRequestTransfer->getUuidOrFail())
            ->setIsGroupedByGroupKey(true)
            ->setIsWithItems(true);

        return (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions($recurringScheduleConditionsTransfer)
            ->setCustomer($customerTransfer);
    }

    protected function resolveAddedItems(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): RecurringScheduleReviewResponseTransfer {
        $recurringScheduleTransfer = $recurringScheduleReviewResponseTransfer->getRecurringSchedule();
        $recurringScheduleItemAdditionTransfers = $recurringScheduleEventRequestTransfer->getAddedItems()->getArrayCopy();

        if ($recurringScheduleTransfer === null || $recurringScheduleItemAdditionTransfers === []) {
            return $recurringScheduleReviewResponseTransfer;
        }

        return $recurringScheduleReviewResponseTransfer->setResolvedAddedItems(
            $this->addedItemResolver->resolveAddedItems($recurringScheduleItemAdditionTransfers, $recurringScheduleTransfer),
        );
    }

    protected function applyApprovedChangesAndConfirm(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): RecurringScheduleEventResponseTransfer {
        try {
            $this->getTransactionHandler()->handleTransaction(function () use (
                $recurringScheduleEventRequestTransfer,
                $recurringScheduleReviewResponseTransfer,
            ): void {
                $this->executeApplyApprovedChangesAndConfirmTransaction(
                    $recurringScheduleEventRequestTransfer,
                    $recurringScheduleReviewResponseTransfer,
                );
            });
        } catch (ScheduleReviewConfirmationException) {
            return $this->createErrorResponse(static::GLOSSARY_KEY_APPROVE_FAILED);
        }

        return $this->createSuccessResponse();
    }

    /**
     * @throws \SprykerFeature\Zed\OrderExperienceManagement\Business\Exception\ScheduleReviewConfirmationException
     */
    protected function executeApplyApprovedChangesAndConfirmTransaction(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): void {
        $this->scheduleReviewChangeApplier->applyApprovedChanges(
            $recurringScheduleReviewResponseTransfer,
            $recurringScheduleEventRequestTransfer->getAcceptedItems()->getArrayCopy(),
            $recurringScheduleEventRequestTransfer->getScope(),
        );

        $this->standingScheduleQuoteOverrideApplier->applyStandingQuoteOverride(
            $recurringScheduleReviewResponseTransfer->getRecurringScheduleOrFail(),
            $recurringScheduleEventRequestTransfer,
        );

        if (
            !$this->scheduleEventTrigger->triggerEventForRecurringSchedule(
                $recurringScheduleReviewResponseTransfer->getRecurringScheduleOrFail(),
                SharedOrderExperienceManagementConfig::SM_EVENT_CONFIRM,
            )
        ) {
            throw new ScheduleReviewConfirmationException();
        }
    }

    protected function createSuccessResponse(): RecurringScheduleEventResponseTransfer
    {
        return (new RecurringScheduleEventResponseTransfer())->setIsSuccessful(true);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function createErrorResponse(string $message, array $parameters = []): RecurringScheduleEventResponseTransfer
    {
        return (new RecurringScheduleEventResponseTransfer())
            ->setIsSuccessful(false)
            ->addError((new ErrorTransfer())->setMessage($message)->setParameters($parameters));
    }
}
