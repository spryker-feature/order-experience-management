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

class OccurrenceScheduleReviewScopeStrategy implements ScheduleReviewScopeStrategyInterface
{
    protected const int OCCURRENCE_BASE_QUANTITY = 0;

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
        $this->scheduleReviewItemRemover->skipForNextOrder($recurringScheduleItemReviewTransfer, $idRecurringSchedule);
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

        $recurringScheduleItemTransfers = $this->scheduleReviewItemUpdatePlanMerger->merge(
            $recurringScheduleItemTransfers,
            $this->scheduleReviewQuantityApplier->applyOccurrenceQuantities(
                $acceptedQuantitiesByGroupKey,
                $groupKeysByIdRecurringScheduleItem,
            ),
        );

        $this->entityManager->updateRecurringScheduleItemCollection($recurringScheduleItemTransfers);
    }

    public function applyAddedItemScope(RecurringScheduleItemTransfer $recurringScheduleItemTransfer): void
    {
        $recurringScheduleItemTransfer
            ->setNextDeliveryQuantity($recurringScheduleItemTransfer->getQuantityOrFail())
            ->setQuantity(static::OCCURRENCE_BASE_QUANTITY);
    }
}
