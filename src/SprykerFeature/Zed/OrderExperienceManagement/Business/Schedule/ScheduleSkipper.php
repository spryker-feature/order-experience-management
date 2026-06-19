<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule;

use Generated\Shared\Transfer\RecurringScheduleHistoryTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class ScheduleSkipper implements ScheduleSkipperInterface
{
    public function __construct(
        protected OrderExperienceManagementRepositoryInterface $subscriptionRepository,
        protected OrderExperienceManagementEntityManagerInterface $entityManager,
        protected ScheduleAdvancerInterface $scheduleAdvancer,
    ) {
    }

    public function skip(int $idRecurringSchedule): void
    {
        $dueData = $this->subscriptionRepository->findRecurringScheduleDueData($idRecurringSchedule);

        if ($dueData === null) {
            return;
        }

        $this->recordSkippedHistory($idRecurringSchedule, $dueData->getNextTriggerDateOrFail());

        $this->scheduleAdvancer->advance($idRecurringSchedule);
    }

    protected function recordSkippedHistory(int $idRecurringSchedule, string $skippedTriggerDate): void
    {
        $recurringScheduleHistoryTransfer = (new RecurringScheduleHistoryTransfer())
            ->setIdRecurringSchedule($idRecurringSchedule)
            ->setEventType(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_SKIPPED)
            ->setCreatedAt($skippedTriggerDate);

        $this->entityManager->createRecurringScheduleHistory($recurringScheduleHistoryTransfer);
    }
}
