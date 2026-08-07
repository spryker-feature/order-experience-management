<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer;

use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventResponseTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Reader\RecurringScheduleReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\ScheduleEventTriggerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;

class ScheduleResumeWriter implements ScheduleResumeWriterInterface
{
    public function __construct(
        protected RecurringScheduleReaderInterface $recurringScheduleReader,
        protected OrderExperienceManagementEntityManagerInterface $entityManager,
        protected ScheduleEventTriggerInterface $scheduleEventTrigger,
    ) {
    }

    public function resumeWithDate(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
    ): RecurringScheduleEventResponseTransfer {
        $responseTransfer = new RecurringScheduleEventResponseTransfer();

        $customerTransfer = $recurringScheduleEventRequestTransfer->getCustomer();
        $scheduleTransfer = $this->recurringScheduleReader->findRecurringScheduleByUuid(
            $recurringScheduleEventRequestTransfer->getUuidOrFail(),
            $recurringScheduleEventRequestTransfer->getIdCustomerOrFail(),
            $customerTransfer,
        );

        if ($scheduleTransfer === null) {
            return $responseTransfer->setIsSuccessful(false);
        }

        if ($scheduleTransfer->getStatus() !== SharedOrderExperienceManagementConfig::STATUS_PAUSED) {
            return $responseTransfer->setIsSuccessful(false);
        }

        $this->entityManager->updateScheduleNextTriggerDate(
            $scheduleTransfer->getIdRecurringScheduleOrFail(),
            $recurringScheduleEventRequestTransfer->getNextExecutionDateOrFail(),
        );

        $isSuccessful = $this->scheduleEventTrigger->triggerEventForRecurringSchedule(
            $scheduleTransfer,
            SharedOrderExperienceManagementConfig::SM_EVENT_RESUME,
        );

        return $responseTransfer->setIsSuccessful($isSuccessful);
    }
}
