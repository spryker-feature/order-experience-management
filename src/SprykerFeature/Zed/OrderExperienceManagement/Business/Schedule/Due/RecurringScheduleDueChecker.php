<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Due;

use DateTimeImmutable;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class RecurringScheduleDueChecker implements RecurringScheduleDueCheckerInterface
{
    public function __construct(
        protected readonly OrderExperienceManagementRepositoryInterface $repository,
        protected readonly OrderExperienceManagementConfig $config,
    ) {
    }

    public function isOrderPlaced(int $idRecurringSchedule): bool
    {
        $recurringScheduleHistoryTransfer = $this->repository->findLatestHistoryByScheduleId($idRecurringSchedule);

        if ($recurringScheduleHistoryTransfer === null) {
            return false;
        }

        return $recurringScheduleHistoryTransfer->getEventType() === SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_PLACED;
    }

    public function isPlacementDue(int $idRecurringSchedule): bool
    {
        $recurringScheduleDueDataTransfer = $this->repository->findRecurringScheduleDueData($idRecurringSchedule);

        if ($recurringScheduleDueDataTransfer === null) {
            return false;
        }

        $triggerDate = new DateTimeImmutable($recurringScheduleDueDataTransfer->getNextTriggerDateOrFail());

        return $triggerDate <= new DateTimeImmutable('now');
    }

    public function isScheduleDue(int $idRecurringSchedule): bool
    {
        $recurringScheduleDueDataTransfer = $this->repository->findRecurringScheduleDueData($idRecurringSchedule);

        if ($recurringScheduleDueDataTransfer === null) {
            return false;
        }

        $windowHours = $recurringScheduleDueDataTransfer->getNotificationWindowHours() ?? $this->config->getDefaultNotificationWindowHours();
        $triggerDate = new DateTimeImmutable($recurringScheduleDueDataTransfer->getNextTriggerDateOrFail());
        $notifyFrom = $triggerDate->modify(sprintf('-%d hours', $windowHours));

        return $notifyFrom <= new DateTimeImmutable('now');
    }
}
