<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use Spryker\Zed\StateMachine\Business\StateMachineFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Reader\RecurringScheduleReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class ScheduleEventTrigger implements ScheduleEventTriggerInterface
{
    public function __construct(
        protected OrderExperienceManagementRepositoryInterface $repository,
        protected StateMachineFacadeInterface $stateMachineFacade,
        protected OrderExperienceManagementConfig $config,
        protected RecurringScheduleReaderInterface $recurringScheduleReader,
    ) {
    }

    public function triggerEvent(string $uuid, string $event, int $idCustomer, ?CustomerTransfer $customerTransfer = null): bool
    {
        $recurringScheduleTransfer = $this->recurringScheduleReader->findRecurringScheduleByUuid($uuid, $idCustomer, $customerTransfer);

        if ($recurringScheduleTransfer === null) {
            return false;
        }

        return $this->triggerEventForRecurringSchedule($recurringScheduleTransfer, $event);
    }

    public function triggerEventForRecurringSchedule(RecurringScheduleTransfer $recurringScheduleTransfer, string $event): bool
    {
        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();
        $idSmState = $this->repository->findCurrentSmStateIdForSchedule(
            $idRecurringSchedule,
            $this->config->getStateMachineName(),
        );

        if ($idSmState === null) {
            return false;
        }

        return $this->dispatchEvent($event, $idRecurringSchedule, $idSmState);
    }

    public function triggerManualEvent(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
    ): RecurringScheduleEventResponseTransfer {
        $isSuccessful = $this->triggerEvent(
            $recurringScheduleEventRequestTransfer->getUuidOrFail(),
            $recurringScheduleEventRequestTransfer->getEventOrFail(),
            $recurringScheduleEventRequestTransfer->getIdCustomerOrFail(),
            $recurringScheduleEventRequestTransfer->getCustomer(),
        );

        return (new RecurringScheduleEventResponseTransfer())->setIsSuccessful($isSuccessful);
    }

    protected function dispatchEvent(string $event, int $idRecurringSchedule, int $idSmState): bool
    {
        $stateMachineItemTransfer = (new StateMachineItemTransfer())
            ->setIdentifier($idRecurringSchedule)
            ->setIdItemState($idSmState);

        $affected = $this->stateMachineFacade->triggerEvent($event, $stateMachineItemTransfer);

        return $affected > 0;
    }
}
