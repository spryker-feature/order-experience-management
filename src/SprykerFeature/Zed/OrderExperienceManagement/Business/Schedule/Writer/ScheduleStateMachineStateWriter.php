<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer;

use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\SmStateStatusResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;

class ScheduleStateMachineStateWriter implements ScheduleStateMachineStateWriterInterface
{
    public function __construct(
        protected OrderExperienceManagementEntityManagerInterface $entityManager,
        protected SmStateStatusResolverInterface $smStateStatusResolver,
    ) {
    }

    public function updateStateMachineState(int $idRecurringSchedule, int $idStateMachineItemState, string $stateName): void
    {
        $status = $this->smStateStatusResolver->resolveStatus($stateName);

        $this->entityManager->updateRecurringScheduleStateMachineState($idRecurringSchedule, $idStateMachineItemState, $status);
    }
}
