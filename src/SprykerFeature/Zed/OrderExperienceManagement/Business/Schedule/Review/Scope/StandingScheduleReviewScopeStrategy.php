<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Scope;

use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Spryker\DecimalObject\Decimal;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Plan\ScheduleReviewItemUpdatePlanMergerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\ScheduleReviewItemRemoverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\ScheduleReviewPriceApplierInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\ScheduleReviewQuantityApplierInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class StandingScheduleReviewScopeStrategy implements ScheduleReviewScopeStrategyInterface
{
    public function __construct(
        protected readonly ScheduleReviewItemRemoverInterface $scheduleReviewItemRemover,
        protected readonly ScheduleReviewPriceApplierInterface $scheduleReviewPriceApplier,
        protected readonly ScheduleReviewQuantityApplierInterface $scheduleReviewQuantityApplier,
        protected readonly ScheduleReviewItemUpdatePlanMergerInterface $scheduleReviewItemUpdatePlanMerger,
        protected readonly OrderExperienceManagementEntityManagerInterface $entityManager,
        protected readonly OrderExperienceManagementRepositoryInterface $repository,
    ) {
    }

    public function applyRemoval(
        RecurringScheduleItemReviewTransfer $recurringScheduleItemReviewTransfer,
        int $idRecurringSchedule,
    ): void {
        $this->scheduleReviewItemRemover->remove($recurringScheduleItemReviewTransfer, $idRecurringSchedule);
    }

    /**
     * @param array<string, int> $acceptedPricesByGroupKey
     * @param array<string, int> $acceptedQuantitiesByGroupKey
     * @param array<int, string> $groupKeysByIdRecurringScheduleItem
     */
    public function applyAcceptedItems(
        array $acceptedPricesByGroupKey,
        array $acceptedQuantitiesByGroupKey,
        array $groupKeysByIdRecurringScheduleItem,
        bool $isNetMode,
    ): void {
        $recurringScheduleItemTransfers = $this->scheduleReviewPriceApplier->reBaselineAcceptedPrices(
            $acceptedPricesByGroupKey,
            $groupKeysByIdRecurringScheduleItem,
            $isNetMode,
        );

        [$quantityRecurringScheduleItemTransfers, $collapsedRecurringScheduleItemIds] = $this->scheduleReviewQuantityApplier
            ->applyStandingQuantities($acceptedQuantitiesByGroupKey, $groupKeysByIdRecurringScheduleItem);

        $recurringScheduleItemTransfers = $this->scheduleReviewItemUpdatePlanMerger->merge(
            $recurringScheduleItemTransfers,
            $quantityRecurringScheduleItemTransfers,
        );

        $recurringScheduleItemTransfers = $this->carryHighestPriceTolerance(
            $recurringScheduleItemTransfers,
            array_keys($acceptedQuantitiesByGroupKey),
            $groupKeysByIdRecurringScheduleItem,
            $collapsedRecurringScheduleItemIds,
        );

        $recurringScheduleItemTransfers = array_diff_key(
            $recurringScheduleItemTransfers,
            array_flip($collapsedRecurringScheduleItemIds),
        );

        $this->entityManager->updateRecurringScheduleItemCollection($recurringScheduleItemTransfers);
        $this->entityManager->deleteRecurringScheduleItemsByIds($collapsedRecurringScheduleItemIds);
    }

    /**
     * Rows of one group are merged into the first one, so the survivor takes the highest tolerance of the group: taking
     * the lowest, or the first row's, could make the order stricter than the buyer last set it. A group with no
     * tolerance on any row stays not set.
     *
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemTransfer> $recurringScheduleItemTransfers
     * @param array<string|int> $mergedGroupKeys
     * @param array<int, string> $groupKeysByIdRecurringScheduleItem
     * @param array<int> $collapsedRecurringScheduleItemIds
     *
     * @return array<int, \Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    protected function carryHighestPriceTolerance(
        array $recurringScheduleItemTransfers,
        array $mergedGroupKeys,
        array $groupKeysByIdRecurringScheduleItem,
        array $collapsedRecurringScheduleItemIds,
    ): array {
        if ($collapsedRecurringScheduleItemIds === []) {
            return $recurringScheduleItemTransfers;
        }

        $priceTolerancePercentsById = $this->repository->getRecurringScheduleItemPriceTolerancePercentsByIds(
            array_keys($groupKeysByIdRecurringScheduleItem),
        );

        foreach ($mergedGroupKeys as $groupKey) {
            $idsOfGroup = array_keys($groupKeysByIdRecurringScheduleItem, (string)$groupKey, true);
            $idSurvivor = $idsOfGroup[0] ?? null;

            if ($idSurvivor === null || !isset($recurringScheduleItemTransfers[$idSurvivor])) {
                continue;
            }

            $highestPriceTolerancePercent = $this->findHighestPriceTolerancePercent($idsOfGroup, $priceTolerancePercentsById);

            if ($highestPriceTolerancePercent !== null) {
                $recurringScheduleItemTransfers[$idSurvivor]->setPriceTolerancePercent($highestPriceTolerancePercent);
            }
        }

        return $recurringScheduleItemTransfers;
    }

    /**
     * @param array<int> $idsOfGroup
     * @param array<int, \Spryker\DecimalObject\Decimal> $priceTolerancePercentsById
     */
    protected function findHighestPriceTolerancePercent(array $idsOfGroup, array $priceTolerancePercentsById): ?Decimal
    {
        $highestPriceTolerancePercent = null;

        foreach ($idsOfGroup as $idRecurringScheduleItem) {
            $priceTolerancePercent = $priceTolerancePercentsById[$idRecurringScheduleItem] ?? null;

            if ($priceTolerancePercent !== null && ($highestPriceTolerancePercent === null || $priceTolerancePercent->greaterThan($highestPriceTolerancePercent))) {
                $highestPriceTolerancePercent = $priceTolerancePercent;
            }
        }

        return $highestPriceTolerancePercent;
    }

    public function applyAddedItemScope(RecurringScheduleItemTransfer $recurringScheduleItemTransfer): void
    {
    }
}
