<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Updater;

use DateTimeInterface;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventResponseTransfer;
use SprykerFeature\Client\OrderExperienceManagement\OrderExperienceManagementClientInterface;

class RecurringOrderScheduleResumeUpdater implements RecurringOrderScheduleResumeUpdaterInterface
{
    protected const string DATE_FORMAT = 'Y-m-d';

    public function __construct(protected OrderExperienceManagementClientInterface $subscriptionClient)
    {
    }

    public function resumeWithDate(
        string $uuid,
        CustomerTransfer $customerTransfer,
        DateTimeInterface $nextExecutionDate,
    ): RecurringScheduleEventResponseTransfer {
        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($customerTransfer->getIdCustomerOrFail())
            ->setCustomer($customerTransfer)
            ->setNextExecutionDate($nextExecutionDate->format(static::DATE_FORMAT));

        return $this->subscriptionClient->resumeScheduleWithDate($requestTransfer);
    }
}
