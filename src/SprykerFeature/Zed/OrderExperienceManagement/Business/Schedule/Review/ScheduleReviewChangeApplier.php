<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;

class ScheduleReviewChangeApplier implements ScheduleReviewChangeApplierInterface
{
    use TransactionTrait;

    /**
     * @see \Spryker\Shared\Price\PriceConfig::PRICE_MODE_NET
     */
    protected const string PRICE_MODE_NET = 'NET_MODE';

    public function __construct(protected readonly OrderExperienceManagementEntityManagerInterface $subscriptionEntityManager)
    {
    }

    public function applyApprovedChanges(
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
        array $acceptedItemReviewTransfers,
    ): void {
        $this->getTransactionHandler()->handleTransaction(function () use (
            $recurringScheduleReviewResponseTransfer,
            $acceptedItemReviewTransfers,
        ): void {
            $this->executeApplyApprovedChangesTransaction($recurringScheduleReviewResponseTransfer, $acceptedItemReviewTransfers);
        });
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $acceptedItemReviewTransfers
     */
    protected function executeApplyApprovedChangesTransaction(
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
        array $acceptedItemReviewTransfers,
    ): void {
        $recurringScheduleTransfer = $recurringScheduleReviewResponseTransfer->getRecurringScheduleOrFail();
        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();
        $isNetMode = $recurringScheduleTransfer->getPriceMode() === static::PRICE_MODE_NET;

        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            if ($recurringScheduleItemReviewTransfer->getIsPurchasable() === false) {
                $this->removeItem($recurringScheduleItemReviewTransfer, $idRecurringSchedule);
            }
        }

        foreach ($acceptedItemReviewTransfers as $recurringScheduleItemReviewTransfer) {
            $this->persistAcceptedPrice($recurringScheduleItemReviewTransfer, $idRecurringSchedule, $isNetMode);
        }
    }

    protected function removeItem(RecurringScheduleItemReviewTransfer $recurringScheduleItemReviewTransfer, int $idRecurringSchedule): void
    {
        $recurringScheduleItemTransfer = $recurringScheduleItemReviewTransfer->getRecurringScheduleItemOrFail();
        $bundleItemIdentifier = $recurringScheduleItemTransfer->getBundleItemIdentifier();

        if ($bundleItemIdentifier !== null) {
            $this->subscriptionEntityManager->deleteRecurringScheduleItemsByBundleItemIdentifier($idRecurringSchedule, $bundleItemIdentifier);

            return;
        }

        $configuredBundleGroupKey = $recurringScheduleItemTransfer->getConfiguredBundleGroupKey();

        if ($configuredBundleGroupKey !== null) {
            $this->subscriptionEntityManager->deleteRecurringScheduleItemsByConfiguredBundleGroupKey($idRecurringSchedule, $configuredBundleGroupKey);

            return;
        }

        $groupKey = $recurringScheduleItemTransfer->getGroupKey();

        if ($groupKey !== null) {
            $this->subscriptionEntityManager->deleteRecurringScheduleItemsByGroupKey($idRecurringSchedule, $groupKey);

            return;
        }

        $idRecurringScheduleItem = $recurringScheduleItemTransfer->getIdRecurringScheduleItem();

        if ($idRecurringScheduleItem === null) {
            return;
        }

        $this->subscriptionEntityManager->deleteRecurringScheduleItem($idRecurringScheduleItem);
    }

    protected function persistAcceptedPrice(
        RecurringScheduleItemReviewTransfer $recurringScheduleItemReviewTransfer,
        int $idRecurringSchedule,
        bool $isNetMode,
    ): void {
        $groupKey = $recurringScheduleItemReviewTransfer->getRecurringScheduleItemOrFail()->getGroupKey();
        $acceptedPrice = $recurringScheduleItemReviewTransfer->getCurrentPrice();

        if ($groupKey === null || $acceptedPrice === null) {
            return;
        }

        $this->subscriptionEntityManager->updateReferencePricesByGroupKey(
            $idRecurringSchedule,
            $groupKey,
            $isNetMode ? $acceptedPrice : null,
            $isNetMode ? null : $acceptedPrice,
        );
    }
}
