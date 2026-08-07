<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander;

use Generated\Shared\Transfer\PaginationTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\History\RecurringScheduleHistoryFailureReasonEnricherInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class RecurringScheduleHistoryExpander extends AbstractRecurringScheduleExpander implements RecurringScheduleExpanderInterface
{
    public function __construct(
        protected OrderExperienceManagementRepositoryInterface $repository,
        protected RecurringScheduleHistoryFailureReasonEnricherInterface $recurringScheduleHistoryFailureReasonEnricher,
    ) {
    }

    public function isApplicable(RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer): bool
    {
        return (bool)$recurringScheduleCriteriaTransfer->getRecurringScheduleConditions()?->getIsWithHistory();
    }

    public function expand(
        RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer,
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleCollectionTransfer {
        $historyPaginationTransfer = $recurringScheduleCriteriaTransfer->getHistoryPagination();
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
            $recurringScheduleHistoryTransfer = $this->recurringScheduleHistoryFailureReasonEnricher->enrich($recurringScheduleHistoryTransfer);
            $recurringScheduleTransfer->addHistoryItem($recurringScheduleHistoryTransfer);
        }
    }
}
