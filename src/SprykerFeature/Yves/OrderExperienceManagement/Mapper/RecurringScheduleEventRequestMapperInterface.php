<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Mapper;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;

interface RecurringScheduleEventRequestMapperInterface
{
    /**
     * @param array<string, mixed> $formData
     */
    public function mapApproveFormDataToRecurringScheduleEventRequest(
        array $formData,
        CustomerTransfer $customerTransfer,
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
    ): RecurringScheduleEventRequestTransfer;

    /**
     * @param array<string, mixed> $formData
     */
    public function mapEditFormDataToRecurringScheduleCollectionRequest(
        array $formData,
        CustomerTransfer $customerTransfer,
        RecurringScheduleCollectionRequestTransfer $recurringScheduleCollectionRequestTransfer
    ): RecurringScheduleCollectionRequestTransfer;
}
