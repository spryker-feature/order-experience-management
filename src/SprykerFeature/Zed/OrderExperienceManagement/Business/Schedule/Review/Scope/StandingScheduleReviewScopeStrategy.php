<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Scope;

use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Plan\ScheduleReviewItemUpdatePlanMergerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\ScheduleReviewItemRemoverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\ScheduleReviewPriceApplierInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\ScheduleReviewQuantityApplierInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;

class StandingScheduleReviewScopeStrategy implements ScheduleReviewScopeStrategyInterface
{
    public function __construct(
        protected readonly ScheduleReviewItemRemoverInterface $scheduleReviewItemRemover,
        protected readonly ScheduleReviewPriceApplierInterface $scheduleReviewPriceApplier,
        protected readonly ScheduleReviewQuantityApplierInterface $scheduleReviewQuantityApplier,
        protected readonly ScheduleReviewItemUpdatePlanMergerInterface $scheduleReviewItemUpdatePlanMerger,
        protected readonly OrderExperienceManagementEntityManagerInterface $entityManager,
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

        $recurringScheduleItemTransfers = array_diff_key(
            $recurringScheduleItemTransfers,
            array_flip($collapsedRecurringScheduleItemIds),
        );

        $this->entityManager->updateRecurringScheduleItemCollection($recurringScheduleItemTransfers);
        $this->entityManager->deleteRecurringScheduleItemsByIds($collapsedRecurringScheduleItemIds);
    }

    public function applyAddedItemScope(RecurringScheduleItemTransfer $recurringScheduleItemTransfer): void
    {
    }
}
