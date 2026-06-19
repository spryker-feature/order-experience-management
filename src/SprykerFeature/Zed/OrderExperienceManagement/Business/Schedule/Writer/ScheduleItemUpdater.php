<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionResponseTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class ScheduleItemUpdater implements ScheduleItemUpdaterInterface
{
    protected const string ERROR_SCHEDULE_NOT_FOUND = 'Recurring schedule not found or access denied.';

    public function __construct(
        protected OrderExperienceManagementRepositoryInterface $subscriptionRepository,
        protected OrderExperienceManagementEntityManagerInterface $entityManager,
    ) {
    }

    public function updateItemQuantities(
        RecurringScheduleCollectionRequestTransfer $requestTransfer,
    ): RecurringScheduleCollectionResponseTransfer {
        $responseTransfer = new RecurringScheduleCollectionResponseTransfer();

        $scheduleTransfers = $requestTransfer->getRecurringSchedules();

        if ($scheduleTransfers->count() === 0) {
            return $responseTransfer;
        }

        $requestedScheduleTransfer = $scheduleTransfers->offsetGet(0);
        $idCustomer = $requestTransfer->getCustomerOrFail()->getIdCustomerOrFail();

        $existingScheduleTransfer = $this->subscriptionRepository->findRecurringScheduleByUuid(
            $requestedScheduleTransfer->getUuidOrFail(),
        );

        if ($existingScheduleTransfer === null || $existingScheduleTransfer->getIdCustomer() !== $idCustomer) {
            $responseTransfer->addError(
                (new ErrorTransfer())->setMessage(static::ERROR_SCHEDULE_NOT_FOUND),
            );

            return $responseTransfer;
        }

        foreach ($requestedScheduleTransfer->getItems() as $recurringScheduleItemTransfer) {
            $this->entityManager->updateRecurringScheduleItem($recurringScheduleItemTransfer);
        }

        $responseTransfer->addRecurringSchedule($existingScheduleTransfer);

        return $responseTransfer;
    }
}
