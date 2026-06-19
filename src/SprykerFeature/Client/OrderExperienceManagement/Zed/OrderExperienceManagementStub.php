<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\OrderExperienceManagement\Zed;

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
use Spryker\Client\ZedRequest\ZedRequestClientInterface;

class OrderExperienceManagementStub implements OrderExperienceManagementStubInterface
{
    public function __construct(protected readonly ZedRequestClientInterface $zedRequestClient)
    {
    }

    public function updateRecurringOrderSettingsOnQuote(
        RecurringOrderQuoteUpdateRequestTransfer $recurringOrderQuoteUpdateRequestTransfer
    ): RecurringOrderQuoteUpdateResponseTransfer {
        /** @var \Generated\Shared\Transfer\RecurringOrderQuoteUpdateResponseTransfer $recurringOrderQuoteUpdateResponseTransfer */
        $recurringOrderQuoteUpdateResponseTransfer = $this->zedRequestClient->call(
            '/order-experience-management/gateway/update-recurring-order-settings-on-quote',
            $recurringOrderQuoteUpdateRequestTransfer,
        );

        return $recurringOrderQuoteUpdateResponseTransfer;
    }

    public function triggerManualEventForSchedule(
        RecurringScheduleEventRequestTransfer $requestTransfer,
    ): RecurringScheduleEventResponseTransfer {
        /** @var \Generated\Shared\Transfer\RecurringScheduleEventResponseTransfer $responseTransfer */
        $responseTransfer = $this->zedRequestClient->call(
            '/order-experience-management/gateway/trigger-manual-event-for-schedule',
            $requestTransfer,
        );

        return $responseTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller\GatewayController::getRecurringScheduleCollectionAction()
     */
    public function getRecurringScheduleCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
    ): RecurringScheduleCollectionTransfer {
        /** @var \Generated\Shared\Transfer\RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer */
        $recurringScheduleCollectionTransfer = $this->zedRequestClient->call(
            '/order-experience-management/gateway/get-recurring-schedule-collection',
            $recurringScheduleCriteriaTransfer,
        );

        return $recurringScheduleCollectionTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller\GatewayController::getRecurringScheduleStatusCountCollectionAction()
     */
    public function getRecurringScheduleStatusCountCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
    ): RecurringScheduleStatusCountCollectionTransfer {
        /** @var \Generated\Shared\Transfer\RecurringScheduleStatusCountCollectionTransfer $recurringScheduleStatusCountCollectionTransfer */
        $recurringScheduleStatusCountCollectionTransfer = $this->zedRequestClient->call(
            '/order-experience-management/gateway/get-recurring-schedule-status-count-collection',
            $recurringScheduleCriteriaTransfer,
        );

        return $recurringScheduleStatusCountCollectionTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller\GatewayController::resumeScheduleWithDateAction()
     */
    public function resumeScheduleWithDate(
        RecurringScheduleEventRequestTransfer $requestTransfer,
    ): RecurringScheduleEventResponseTransfer {
        /** @var \Generated\Shared\Transfer\RecurringScheduleEventResponseTransfer $responseTransfer */
        $responseTransfer = $this->zedRequestClient->call(
            '/order-experience-management/gateway/resume-schedule-with-date',
            $requestTransfer,
        );

        return $responseTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller\GatewayController::updateRecurringScheduleCollectionAction()
     */
    public function updateRecurringScheduleCollection(
        RecurringScheduleCollectionRequestTransfer $requestTransfer,
    ): RecurringScheduleCollectionResponseTransfer {
        /** @var \Generated\Shared\Transfer\RecurringScheduleCollectionResponseTransfer $responseTransfer */
        $responseTransfer = $this->zedRequestClient->call(
            '/order-experience-management/gateway/update-recurring-schedule-collection',
            $requestTransfer,
        );

        return $responseTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller\GatewayController::getRecurringScheduleReviewAction()
     */
    public function getRecurringScheduleReview(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleReviewResponseTransfer {
        /** @var \Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer */
        $recurringScheduleReviewResponseTransfer = $this->zedRequestClient->call(
            '/order-experience-management/gateway/get-recurring-schedule-review',
            $recurringScheduleCriteriaTransfer,
        );

        return $recurringScheduleReviewResponseTransfer;
    }

    /**
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller\GatewayController::approveScheduleReviewAction()
     */
    public function approveScheduleReview(
        RecurringScheduleEventRequestTransfer $requestTransfer,
    ): RecurringScheduleEventResponseTransfer {
        /** @var \Generated\Shared\Transfer\RecurringScheduleEventResponseTransfer $responseTransfer */
        $responseTransfer = $this->zedRequestClient->call(
            '/order-experience-management/gateway/approve-schedule-review',
            $requestTransfer,
        );

        return $responseTransfer;
    }
}
