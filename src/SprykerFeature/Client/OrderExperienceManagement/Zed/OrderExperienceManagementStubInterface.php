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

interface OrderExperienceManagementStubInterface
{
    public function updateRecurringOrderSettingsOnQuote(
        RecurringOrderQuoteUpdateRequestTransfer $recurringOrderQuoteUpdateRequestTransfer
    ): RecurringOrderQuoteUpdateResponseTransfer;

    public function triggerManualEventForSchedule(
        RecurringScheduleEventRequestTransfer $requestTransfer,
    ): RecurringScheduleEventResponseTransfer;

    public function getRecurringScheduleCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
    ): RecurringScheduleCollectionTransfer;

    public function getRecurringScheduleStatusCountCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
    ): RecurringScheduleStatusCountCollectionTransfer;

    public function resumeScheduleWithDate(
        RecurringScheduleEventRequestTransfer $requestTransfer,
    ): RecurringScheduleEventResponseTransfer;

    public function updateRecurringScheduleCollection(
        RecurringScheduleCollectionRequestTransfer $requestTransfer,
    ): RecurringScheduleCollectionResponseTransfer;

    public function getRecurringScheduleReview(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleReviewResponseTransfer;

    public function approveScheduleReview(
        RecurringScheduleEventRequestTransfer $requestTransfer,
    ): RecurringScheduleEventResponseTransfer;
}
