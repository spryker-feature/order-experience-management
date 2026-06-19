<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

class ScheduleReviewSummaryCalculator implements ScheduleReviewSummaryCalculatorInterface
{
    /**
     * @see \Spryker\Shared\Price\PriceConfig::PRICE_MODE_NET
     */
    protected const string PRICE_MODE_NET = 'NET_MODE';

    public function calculate(RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer): RecurringScheduleReviewResponseTransfer
    {
        return $recurringScheduleReviewResponseTransfer
            ->setOriginalTotal($this->calculateOriginalTotal($recurringScheduleReviewResponseTransfer))
            ->setUpdatedTotal($this->calculateUpdatedTotal($recurringScheduleReviewResponseTransfer))
            ->setRemovedItemCount($this->countUnpurchasableItems($recurringScheduleReviewResponseTransfer))
            ->setPriceChangeCount($this->countByReason($recurringScheduleReviewResponseTransfer, SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED))
            ->setSubstitutedCount($this->countByReason($recurringScheduleReviewResponseTransfer, SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_SUBSTITUTED))
            ->setUnavailableCount($this->countByReason($recurringScheduleReviewResponseTransfer, SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_UNAVAILABLE));
    }

    protected function calculateOriginalTotal(RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer): int
    {
        $isNetMode = $this->isNetMode($recurringScheduleReviewResponseTransfer);
        $originalTotal = 0;

        foreach ($recurringScheduleReviewResponseTransfer->getUnchangedItems() as $recurringScheduleItemTransfer) {
            $originalTotal += $this->calculateReferenceItemTotal($recurringScheduleItemTransfer, $isNetMode);
        }

        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            $originalTotal += $this->calculateReferenceItemTotal($recurringScheduleItemReviewTransfer->getRecurringScheduleItemOrFail(), $isNetMode);
        }

        return $originalTotal;
    }

    protected function calculateUpdatedTotal(RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer): int
    {
        $isNetMode = $this->isNetMode($recurringScheduleReviewResponseTransfer);
        $updatedTotal = 0;

        foreach ($recurringScheduleReviewResponseTransfer->getUnchangedItems() as $recurringScheduleItemTransfer) {
            $updatedTotal += $this->calculateReferenceItemTotal($recurringScheduleItemTransfer, $isNetMode);
        }

        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            if ($recurringScheduleItemReviewTransfer->getIsPurchasable() === false) {
                continue;
            }

            $updatedTotal += $this->calculateCurrentItemTotal($recurringScheduleItemReviewTransfer);
        }

        return $updatedTotal;
    }

    protected function calculateReferenceItemTotal(RecurringScheduleItemTransfer $recurringScheduleItemTransfer, bool $isNetMode): int
    {
        $unitPrice = $isNetMode
            ? (int)$recurringScheduleItemTransfer->getReferenceNetPrice()
            : (int)$recurringScheduleItemTransfer->getReferenceGrossPrice();

        return (int)$recurringScheduleItemTransfer->getQuantity() * $unitPrice;
    }

    protected function calculateCurrentItemTotal(RecurringScheduleItemReviewTransfer $recurringScheduleItemReviewTransfer): int
    {
        $recurringScheduleItemTransfer = $recurringScheduleItemReviewTransfer->getRecurringScheduleItemOrFail();
        $currentPrice = $recurringScheduleItemReviewTransfer->getCurrentPrice();

        if ($currentPrice === null) {
            return (int)$recurringScheduleItemTransfer->getItemTotal();
        }

        return (int)$recurringScheduleItemTransfer->getQuantity() * $currentPrice;
    }

    protected function countByReason(RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer, string $reviewReason): int
    {
        $count = 0;

        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            if (in_array($reviewReason, $recurringScheduleItemReviewTransfer->getReviewReasons(), true)) {
                $count++;
            }
        }

        return $count;
    }

    protected function countUnpurchasableItems(RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer): int
    {
        $count = 0;

        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            if ($recurringScheduleItemReviewTransfer->getIsPurchasable() === false) {
                $count++;
            }
        }

        return $count;
    }

    protected function isNetMode(RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer): bool
    {
        return $recurringScheduleReviewResponseTransfer->getRecurringScheduleOrFail()->getPriceMode() === static::PRICE_MODE_NET;
    }
}
