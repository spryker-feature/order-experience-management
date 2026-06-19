<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business;

use Generated\Shared\Transfer\RecurringOrderQuoteUpdateRequestTransfer;
use Generated\Shared\Transfer\RecurringOrderQuoteUpdateResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleStatusCountCollectionTransfer;
use Spryker\Zed\Kernel\Business\AbstractFacade;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory getFactory()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface getEntityManager()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface getRepository()
 */
class OrderExperienceManagementFacade extends AbstractFacade implements OrderExperienceManagementFacadeInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRecurringScheduleCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
    ): RecurringScheduleCollectionTransfer {
        return $this->getFactory()
            ->createRecurringScheduleReader()
            ->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRecurringScheduleStatusCountCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
    ): RecurringScheduleStatusCountCollectionTransfer {
        return $this->getFactory()
            ->createRecurringScheduleReader()
            ->getRecurringScheduleStatusCountCollection($recurringScheduleCriteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function updateRecurringOrderSettingsOnQuote(
        RecurringOrderQuoteUpdateRequestTransfer $recurringOrderQuoteUpdateRequestTransfer
    ): RecurringOrderQuoteUpdateResponseTransfer {
        return $this->getFactory()
            ->createRecurringOrderQuoteUpdater()
            ->updateRecurringOrderSettingsOnQuote($recurringOrderQuoteUpdateRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isRecurringScheduleValid(int $idRecurringSchedule): bool
    {
        return $this->getFactory()
            ->createRecurringSchedulePrePlacementValidator()
            ->isRecurringScheduleValid($idRecurringSchedule);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function triggerManualEventForSchedule(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
    ): RecurringScheduleEventResponseTransfer {
        $isSuccessful = $this->getFactory()->createScheduleEventTrigger()->triggerEvent(
            $recurringScheduleEventRequestTransfer->getUuidOrFail(),
            $recurringScheduleEventRequestTransfer->getEventOrFail(),
            $recurringScheduleEventRequestTransfer->getIdCustomerOrFail(),
            $recurringScheduleEventRequestTransfer->getCustomer(),
        );

        return (new RecurringScheduleEventResponseTransfer())->setIsSuccessful($isSuccessful);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function resumeScheduleWithDate(
        RecurringScheduleEventRequestTransfer $requestTransfer,
    ): RecurringScheduleEventResponseTransfer {
        return $this->getFactory()
            ->createScheduleResumeWriter()
            ->resumeWithDate($requestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function updateRecurringScheduleCollection(
        RecurringScheduleCollectionRequestTransfer $requestTransfer,
    ): RecurringScheduleCollectionResponseTransfer {
        return $this->getFactory()
            ->createScheduleItemUpdater()
            ->updateItemQuantities($requestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRecurringScheduleReview(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleReviewResponseTransfer {
        return $this->getFactory()
            ->createScheduleReviewBuilder()
            ->buildReview($recurringScheduleCriteriaTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function approveScheduleReview(
        RecurringScheduleEventRequestTransfer $requestTransfer,
    ): RecurringScheduleEventResponseTransfer {
        return $this->getFactory()
            ->createScheduleReviewApprover()
            ->approve($requestTransfer);
    }
}
