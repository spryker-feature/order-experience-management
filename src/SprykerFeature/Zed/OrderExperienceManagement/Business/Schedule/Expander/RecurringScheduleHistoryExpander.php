<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander;

use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleErrorTransfer;
use Generated\Shared\Transfer\RecurringScheduleHistoryTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class RecurringScheduleHistoryExpander extends AbstractRecurringScheduleExpander implements RecurringScheduleHistoryExpanderInterface
{
    protected const string DETAIL_KEY_MESSAGE = 'message';

    protected const string DETAIL_KEY_PARAMETERS = 'parameters';

    protected const string MESSAGE_PRODUCT_UNAVAILABLE = 'product.unavailable';

    protected const string PARAMETER_SKU = '%sku%';

    public function __construct(
        protected OrderExperienceManagementRepositoryInterface $repository,
        protected UtilEncodingServiceInterface $utilEncodingService,
    ) {
    }

    public function expandWithHistory(
        RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer,
        ?PaginationTransfer $historyPaginationTransfer = null,
    ): RecurringScheduleCollectionTransfer {
        $scheduleIds = $this->extractScheduleIds($recurringScheduleCollectionTransfer);

        if ($scheduleIds === []) {
            return $recurringScheduleCollectionTransfer;
        }

        $recurringScheduleHistoryTransfers = $this->repository->findScheduleHistoriesByScheduleIds($scheduleIds, $historyPaginationTransfer);
        $recurringScheduleHistoryTransfersByScheduleId = $this->groupHistoriesByScheduleId($recurringScheduleHistoryTransfers);

        return $this->applyHistory(
            $recurringScheduleCollectionTransfer,
            $recurringScheduleHistoryTransfersByScheduleId,
            $historyPaginationTransfer,
        );
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleHistoryTransfer> $recurringScheduleHistoryTransfers
     *
     * @return array<int, list<\Generated\Shared\Transfer\RecurringScheduleHistoryTransfer>>
     */
    protected function groupHistoriesByScheduleId(array $recurringScheduleHistoryTransfers): array
    {
        $recurringScheduleHistoryTransfersByScheduleId = [];

        foreach ($recurringScheduleHistoryTransfers as $recurringScheduleHistoryTransfer) {
            $recurringScheduleHistoryTransfersByScheduleId[$recurringScheduleHistoryTransfer->getIdRecurringScheduleOrFail()][] = $recurringScheduleHistoryTransfer;
        }

        return $recurringScheduleHistoryTransfersByScheduleId;
    }

    /**
     * @param array<int, list<\Generated\Shared\Transfer\RecurringScheduleHistoryTransfer>> $recurringScheduleHistoryTransfersByScheduleId
     */
    protected function applyHistory(
        RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer,
        array $recurringScheduleHistoryTransfersByScheduleId,
        ?PaginationTransfer $historyPaginationTransfer = null,
    ): RecurringScheduleCollectionTransfer {
        foreach ($recurringScheduleCollectionTransfer->getRecurringSchedules() as $recurringScheduleTransfer) {
            $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();
            $scheduleHistories = $recurringScheduleHistoryTransfersByScheduleId[$idRecurringSchedule] ?? [];

            $this->applyHistoryToSchedule($recurringScheduleTransfer, $scheduleHistories);

            if ($historyPaginationTransfer !== null) {
                $recurringScheduleTransfer->setHistoryPagination($historyPaginationTransfer);
            }
        }

        return $recurringScheduleCollectionTransfer;
    }

    /**
     * @param list<\Generated\Shared\Transfer\RecurringScheduleHistoryTransfer> $recurringScheduleHistoryTransfers
     */
    protected function applyHistoryToSchedule(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        array $recurringScheduleHistoryTransfers,
    ): void {
        foreach ($recurringScheduleHistoryTransfers as $recurringScheduleHistoryTransfer) {
            $this->enrichFailureReason($recurringScheduleHistoryTransfer);
            $recurringScheduleTransfer->addHistoryItem($recurringScheduleHistoryTransfer);
        }
    }

    protected function enrichFailureReason(RecurringScheduleHistoryTransfer $recurringScheduleHistoryTransfer): void
    {
        if ($recurringScheduleHistoryTransfer->getEventType() !== SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_FAILED) {
            return;
        }

        $detail = $this->utilEncodingService->decodeJson($recurringScheduleHistoryTransfer->getDetail() ?? '[]', true);

        if (!is_array($detail) || $detail === []) {
            return;
        }

        $failureReason = $this->extractUnavailableSkus($detail);
        $recurringScheduleHistoryTransfer->setFailureReason($failureReason);

        $this->enrichErrors($recurringScheduleHistoryTransfer, $detail);
    }

    /**
     * @param array<int, array<string, mixed>> $errors
     */
    protected function enrichErrors(RecurringScheduleHistoryTransfer $recurringScheduleHistoryTransfer, array $errors): void
    {
        foreach ($errors as $error) {
            $message = $error[static::DETAIL_KEY_MESSAGE] ?? null;

            if ($message === null) {
                continue;
            }

            $recurringScheduleHistoryTransfer->addError(
                (new RecurringScheduleErrorTransfer())
                    ->setMessage($message)
                    ->setParameters($error[static::DETAIL_KEY_PARAMETERS] ?? []),
            );
        }
    }

    /**
     * @param array<int, array<string, mixed>> $errors
     */
    protected function extractUnavailableSkus(array $errors): ?string
    {
        $skus = [];

        foreach ($errors as $error) {
            if (($error[static::DETAIL_KEY_MESSAGE] ?? null) !== static::MESSAGE_PRODUCT_UNAVAILABLE) {
                continue;
            }

            $sku = $error[static::DETAIL_KEY_PARAMETERS][static::PARAMETER_SKU] ?? null;

            if ($sku !== null) {
                $skus[] = (string)$sku;
            }
        }

        if ($skus !== []) {
            return implode(', ', array_unique($skus));
        }

        return ($errors[0][static::DETAIL_KEY_MESSAGE] ?? null) ?: null;
    }
}
