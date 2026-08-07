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
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

class ScheduleReviewSummaryCalculator implements ScheduleReviewSummaryCalculatorInterface
{
    /**
     * @see \Spryker\Shared\Price\PriceConfig::PRICE_MODE_NET
     */
    protected const string PRICE_MODE_NET = 'NET_MODE';

    public function __construct(protected OrderExperienceManagementConfig $config)
    {
    }

    public function calculate(RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer): RecurringScheduleReviewResponseTransfer
    {
        return $recurringScheduleReviewResponseTransfer
            ->setOriginalTotal($this->calculateOriginalTotal($recurringScheduleReviewResponseTransfer))
            ->setUpdatedTotal($this->calculateUpdatedTotal($recurringScheduleReviewResponseTransfer))
            ->setRemovedItemCount($this->countUnpurchasableItems($recurringScheduleReviewResponseTransfer))
            ->setPriceChangeCount($this->countByReasons($recurringScheduleReviewResponseTransfer, $this->config->getPriceChangeReviewReasons()))
            ->setSubstitutedCount($this->countByReasons($recurringScheduleReviewResponseTransfer, $this->config->getSubstitutableReviewReasons()))
            ->setUnavailableCount($this->countByReasons($recurringScheduleReviewResponseTransfer, $this->config->getUnavailableReviewReasons()));
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

    /**
     * @param array<string> $reviewReasons
     */
    protected function countByReasons(RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer, array $reviewReasons): int
    {
        $count = 0;

        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            if (array_intersect($reviewReasons, $recurringScheduleItemReviewTransfer->getReviewReasons()) !== []) {
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
