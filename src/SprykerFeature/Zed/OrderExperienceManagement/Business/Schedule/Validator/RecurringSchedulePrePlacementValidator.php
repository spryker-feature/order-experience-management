<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator;

use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\RecurringOrderBuyerMailNotificationSenderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class RecurringSchedulePrePlacementValidator implements RecurringSchedulePrePlacementValidatorInterface
{
    /**
     * @param array<\SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\ScheduleValidatorPluginInterface> $scheduleValidatorPlugins
     */
    public function __construct(
        protected readonly OrderExperienceManagementRepositoryInterface $subscriptionRepository,
        protected readonly OrderExperienceManagementEntityManagerInterface $subscriptionEntityManager,
        protected readonly RecurringOrderBuyerMailNotificationSenderInterface $recurringOrderBuyerMailNotificationSender,
        protected readonly array $scheduleValidatorPlugins,
        protected readonly RecurringScheduleValidationResultExpanderInterface $recurringScheduleValidationResultExpander,
    ) {
    }

    public function validateRecurringSchedule(RecurringScheduleTransfer $recurringScheduleTransfer): RecurringScheduleValidationResultTransfer
    {
        $recurringScheduleValidationResultTransfer = (new RecurringScheduleValidationResultTransfer())->setIsValid(true);

        foreach ($this->scheduleValidatorPlugins as $scheduleValidatorPlugin) {
            $recurringScheduleValidationResultTransfer = $scheduleValidatorPlugin->validate(
                $recurringScheduleTransfer,
                $recurringScheduleValidationResultTransfer,
            );
        }

        return $this->recurringScheduleValidationResultExpander->expand($recurringScheduleValidationResultTransfer);
    }

    public function isRecurringScheduleValid(int $idRecurringSchedule): bool
    {
        $recurringScheduleTransfer = $this->subscriptionRepository->findRecurringScheduleById($idRecurringSchedule);

        if ($recurringScheduleTransfer === null) {
            return false;
        }

        $recurringScheduleValidationResultTransfer = $this->validateRecurringSchedule($recurringScheduleTransfer);

        if ($recurringScheduleValidationResultTransfer->getIsValid()) {
            return true;
        }

        $this->recurringOrderBuyerMailNotificationSender->notifyValidationFailed($idRecurringSchedule);

        return false;
    }
}
