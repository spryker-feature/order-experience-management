<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\StateMachineItemTransfer;
use Spryker\Zed\StateMachine\Business\StateMachineFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Reader\AccessibleRecurringScheduleReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class ScheduleEventTrigger implements ScheduleEventTriggerInterface
{
    public function __construct(
        protected OrderExperienceManagementRepositoryInterface $subscriptionRepository,
        protected StateMachineFacadeInterface $stateMachineFacade,
        protected OrderExperienceManagementConfig $subscriptionConfig,
        protected AccessibleRecurringScheduleReaderInterface $accessibleRecurringScheduleReader,
    ) {
    }

    public function triggerEvent(string $uuid, string $event, int $idCustomer, ?CustomerTransfer $customerTransfer = null): bool
    {
        $scheduleTransfer = $this->accessibleRecurringScheduleReader->findAccessibleScheduleByUuid($uuid, $idCustomer, $customerTransfer);

        if ($scheduleTransfer === null) {
            return false;
        }

        $idRecurringSchedule = $scheduleTransfer->getIdRecurringScheduleOrFail();
        $idSmState = $this->subscriptionRepository->findCurrentSmStateIdForSchedule(
            $idRecurringSchedule,
            $this->subscriptionConfig->getStateMachineName(),
        );

        if ($idSmState === null) {
            return false;
        }

        return $this->dispatchEvent($event, $idRecurringSchedule, $idSmState);
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
