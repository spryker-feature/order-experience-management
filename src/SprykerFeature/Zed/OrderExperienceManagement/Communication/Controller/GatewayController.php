<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller;

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
use Spryker\Zed\Kernel\Communication\Controller\AbstractGatewayController;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Communication\OrderExperienceManagementCommunicationFactory getFactory()
 */
class GatewayController extends AbstractGatewayController
{
    public function updateRecurringOrderSettingsOnQuoteAction(
        RecurringOrderQuoteUpdateRequestTransfer $recurringOrderQuoteUpdateRequestTransfer
    ): RecurringOrderQuoteUpdateResponseTransfer {
        return $this->getFacade()->updateRecurringOrderSettingsOnQuote($recurringOrderQuoteUpdateRequestTransfer);
    }

    public function triggerManualEventForScheduleAction(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
    ): RecurringScheduleEventResponseTransfer {
        return $this->getFacade()->triggerManualEventForSchedule($recurringScheduleEventRequestTransfer);
    }

    public function getRecurringScheduleCollectionAction(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
    ): RecurringScheduleCollectionTransfer {
        return $this->getFacade()->getRecurringScheduleCollection($recurringScheduleCriteriaTransfer);
    }

    public function getRecurringScheduleStatusCountCollectionAction(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
    ): RecurringScheduleStatusCountCollectionTransfer {
        return $this->getFacade()->getRecurringScheduleStatusCountCollection($recurringScheduleCriteriaTransfer);
    }

    public function resumeScheduleWithDateAction(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
    ): RecurringScheduleEventResponseTransfer {
        return $this->getFacade()->resumeScheduleWithDate($recurringScheduleEventRequestTransfer);
    }

    public function updateRecurringScheduleCollectionAction(
        RecurringScheduleCollectionRequestTransfer $recurringScheduleCollectionRequestTransfer,
    ): RecurringScheduleCollectionResponseTransfer {
        return $this->getFacade()->updateRecurringScheduleCollection($recurringScheduleCollectionRequestTransfer);
    }

    public function getRecurringScheduleReviewAction(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleReviewResponseTransfer {
        return $this->getFacade()->getRecurringScheduleReview($recurringScheduleCriteriaTransfer);
    }

    public function approveScheduleReviewAction(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
    ): RecurringScheduleEventResponseTransfer {
        return $this->getFacade()->approveScheduleReview($recurringScheduleEventRequestTransfer);
    }
}
