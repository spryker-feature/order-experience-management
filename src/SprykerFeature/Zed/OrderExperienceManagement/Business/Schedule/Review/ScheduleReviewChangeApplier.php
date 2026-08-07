<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\AcceptedItemReviewMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\ScheduleReviewItemAdderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Scope\ScheduleReviewScopeStrategyInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Scope\ScheduleReviewScopeStrategyResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class ScheduleReviewChangeApplier implements ScheduleReviewChangeApplierInterface
{
    /**
     * @see \Spryker\Shared\Price\PriceConfig::PRICE_MODE_NET
     */
    protected const string PRICE_MODE_NET = 'NET_MODE';

    public function __construct(
        protected readonly ScheduleReviewScopeStrategyResolverInterface $scheduleReviewScopeStrategyResolver,
        protected readonly ScheduleReviewItemAdderInterface $scheduleReviewItemAdder,
        protected readonly AcceptedItemReviewMapperInterface $acceptedItemReviewMapper,
        protected readonly OrderExperienceManagementRepositoryInterface $repository,
    ) {
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     */
    public function applyApprovedChanges(
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
        array $acceptedItemReviewTransfers,
        ?string $scope,
    ): void {
        $recurringScheduleTransfer = $recurringScheduleReviewResponseTransfer->getRecurringScheduleOrFail();
        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();
        $isNetMode = $recurringScheduleTransfer->getPriceMode() === static::PRICE_MODE_NET;
        $scheduleReviewScopeStrategy = $this->scheduleReviewScopeStrategyResolver->resolve($scope);

        $this->removeUnpurchasableFlaggedItems($recurringScheduleReviewResponseTransfer, $idRecurringSchedule, $scheduleReviewScopeStrategy);
        $this->removeAcceptedItems($acceptedItemReviewTransfers, $idRecurringSchedule, $scheduleReviewScopeStrategy);
        $this->applyAcceptedItems($acceptedItemReviewTransfers, $idRecurringSchedule, $isNetMode, $scheduleReviewScopeStrategy);
        $this->scheduleReviewItemAdder->addItems(
            $recurringScheduleReviewResponseTransfer->getResolvedAddedItems(),
            $recurringScheduleTransfer,
            $scheduleReviewScopeStrategy,
        );
    }

    protected function removeUnpurchasableFlaggedItems(
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
        int $idRecurringSchedule,
        ScheduleReviewScopeStrategyInterface $scheduleReviewScopeStrategy,
    ): void {
        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            if ($recurringScheduleItemReviewTransfer->getIsPurchasable() === false) {
                $scheduleReviewScopeStrategy->applyRemoval($recurringScheduleItemReviewTransfer, $idRecurringSchedule);
            }
        }
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     */
    protected function removeAcceptedItems(
        array $acceptedItemReviewTransfers,
        int $idRecurringSchedule,
        ScheduleReviewScopeStrategyInterface $scheduleReviewScopeStrategy,
    ): void {
        foreach ($acceptedItemReviewTransfers as $recurringScheduleItemReviewTransfer) {
            if ($recurringScheduleItemReviewTransfer->getIsRemoved() === true) {
                $scheduleReviewScopeStrategy->applyRemoval($recurringScheduleItemReviewTransfer, $idRecurringSchedule);
            }
        }
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     */
    protected function applyAcceptedItems(
        array $acceptedItemReviewTransfers,
        int $idRecurringSchedule,
        bool $isNetMode,
        ScheduleReviewScopeStrategyInterface $scheduleReviewScopeStrategy,
    ): void {
        $retainedItemReviewTransfers = $this->filterRetainedItemReviews($acceptedItemReviewTransfers);

        if ($retainedItemReviewTransfers === []) {
            return;
        }

        $groupKeysByIdRecurringScheduleItem = $this->repository->getRecurringScheduleItemGroupKeysByScheduleId($idRecurringSchedule);

        $scheduleReviewScopeStrategy->applyAcceptedItems(
            $this->acceptedItemReviewMapper->mapAcceptedPricesByGroupKey($retainedItemReviewTransfers),
            $this->acceptedItemReviewMapper->mapAcceptedQuantitiesByGroupKey($retainedItemReviewTransfers),
            $groupKeysByIdRecurringScheduleItem,
            $isNetMode,
        );
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     *
     * @return array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer>
     */
    protected function filterRetainedItemReviews(array $acceptedItemReviewTransfers): array
    {
        return array_values(array_filter(
            $acceptedItemReviewTransfers,
            static fn (RecurringScheduleItemReviewTransfer $recurringScheduleItemReviewTransfer): bool => $recurringScheduleItemReviewTransfer->getIsRemoved() !== true,
        ));
    }
}
