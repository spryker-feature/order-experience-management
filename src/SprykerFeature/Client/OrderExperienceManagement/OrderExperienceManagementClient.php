<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\OrderExperienceManagement;

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
use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \SprykerFeature\Client\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 */
class OrderExperienceManagementClient extends AbstractClient implements OrderExperienceManagementClientInterface
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
            ->createOrderExperienceManagementStub()
            ->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);
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
            ->createOrderExperienceManagementStub()
            ->updateRecurringOrderSettingsOnQuote($recurringOrderQuoteUpdateRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function triggerManualEventForSchedule(
        RecurringScheduleEventRequestTransfer $requestTransfer,
    ): RecurringScheduleEventResponseTransfer {
        return $this->getFactory()
            ->createOrderExperienceManagementStub()
            ->triggerManualEventForSchedule($requestTransfer);
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
            ->createOrderExperienceManagementStub()
            ->getRecurringScheduleStatusCountCollection($recurringScheduleCriteriaTransfer);
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
            ->createOrderExperienceManagementStub()
            ->resumeScheduleWithDate($requestTransfer);
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
            ->createOrderExperienceManagementStub()
            ->updateRecurringScheduleCollection($requestTransfer);
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
            ->createOrderExperienceManagementStub()
            ->getRecurringScheduleReview($recurringScheduleCriteriaTransfer);
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
            ->createOrderExperienceManagementStub()
            ->approveScheduleReview($requestTransfer);
    }
}
