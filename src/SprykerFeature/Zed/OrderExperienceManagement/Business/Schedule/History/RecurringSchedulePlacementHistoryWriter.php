<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\History;

use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleHistoryTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;

class RecurringSchedulePlacementHistoryWriter implements RecurringSchedulePlacementHistoryWriterInterface
{
    protected const string DETAIL_KEY_MESSAGE = 'message';

    protected const string DETAIL_KEY_PARAMETERS = 'parameters';

    public function __construct(
        protected readonly OrderExperienceManagementEntityManagerInterface $entityManager,
    ) {
    }

    public function writeHistory(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        CheckoutResponseTransfer $checkoutResponseTransfer
    ): void {
        $recurringScheduleHistoryTransfer = (new RecurringScheduleHistoryTransfer())
            ->setIdRecurringSchedule($recurringScheduleTransfer->getIdRecurringScheduleOrFail());

        if ($checkoutResponseTransfer->getIsSuccess()) {
            $recurringScheduleHistoryTransfer
                ->setEventType(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_PLACED)
                ->setIdSalesOrder($checkoutResponseTransfer->getSaveOrder()?->getIdSalesOrder());

            $this->entityManager->createRecurringScheduleHistory($recurringScheduleHistoryTransfer);

            return;
        }

        $recurringScheduleHistoryTransfer
            ->setEventType(SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_FAILED)
            ->setDetail(json_encode($this->mapErrorsToDetail($checkoutResponseTransfer), JSON_THROW_ON_ERROR));

        $this->entityManager->createRecurringScheduleHistory($recurringScheduleHistoryTransfer);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function mapErrorsToDetail(CheckoutResponseTransfer $checkoutResponseTransfer): array
    {
        $errors = [];

        foreach ($checkoutResponseTransfer->getErrors() as $checkoutErrorTransfer) {
            $errors[] = [
                static::DETAIL_KEY_MESSAGE => $checkoutErrorTransfer->getMessage(),
                static::DETAIL_KEY_PARAMETERS => $checkoutErrorTransfer->getParameters(),
            ];
        }

        return $errors;
    }
}
