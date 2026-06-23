<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator;

use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use Spryker\Shared\Log\LoggerTrait;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\RecurringOrderBuyerMailNotificationSenderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;
use Throwable;

class RecurringSchedulePrePlacementValidator implements RecurringSchedulePrePlacementValidatorInterface
{
    use LoggerTrait;

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

        try {
            $this->recurringOrderBuyerMailNotificationSender->notifyValidationFailed($idRecurringSchedule);
        } catch (Throwable $throwable) {
            $this->getLogger()->error(
                sprintf('Validation failure notification email could not be sent for schedule ID %d: %s', $idRecurringSchedule, $throwable->getMessage()),
                ['exception' => $throwable],
            );
        }

        return false;
    }
}
