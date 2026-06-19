<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule;

use DateTimeImmutable;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class ScheduleAdvancer implements ScheduleAdvancerInterface
{
    protected const string DATE_FORMAT = 'Y-m-d';

    public function __construct(
        protected OrderExperienceManagementRepositoryInterface $subscriptionRepository,
        protected OrderExperienceManagementEntityManagerInterface $entityManager,
        protected CadenceResolverInterface $cadenceResolver,
    ) {
    }

    public function advance(int $idRecurringSchedule): void
    {
        $scheduleTransfer = $this->subscriptionRepository->findRecurringScheduleById($idRecurringSchedule);

        if ($scheduleTransfer === null) {
            return;
        }

        $baseDate = $scheduleTransfer->getNextTriggerDate() !== null
            ? new DateTimeImmutable($scheduleTransfer->getNextTriggerDate())
            : new DateTimeImmutable();

        $nextTriggerDate = $this->cadenceResolver->resolveNextTriggerDateFromBase(
            $scheduleTransfer->getCadenceTypeOrFail(),
            $scheduleTransfer->getCadenceValue(),
            $baseDate,
        );

        $this->entityManager->updateScheduleNextTriggerDate($idRecurringSchedule, $nextTriggerDate->format(static::DATE_FORMAT));
    }
}
