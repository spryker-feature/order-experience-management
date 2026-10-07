<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item;

use Generated\Shared\Transfer\RecurringScheduleItemTransfer;

class ScheduleReviewPriceApplier implements ScheduleReviewPriceApplierInterface
{
    /**
     * @param array<string, int> $acceptedPricesByGroupKey
     * @param array<int, string> $groupKeysByIdRecurringScheduleItem
     *
     * @return array<int, \Generated\Shared\Transfer\RecurringScheduleItemTransfer> Reference price changes keyed by recurring schedule item ID.
     */
    public function reBaselineAcceptedPrices(
        array $acceptedPricesByGroupKey,
        array $groupKeysByIdRecurringScheduleItem,
        bool $isNetMode,
    ): array {
        return $this->createPriceChanges(
            $acceptedPricesByGroupKey,
            $groupKeysByIdRecurringScheduleItem,
            fn (RecurringScheduleItemTransfer $recurringScheduleItemTransfer, int $acceptedPrice): RecurringScheduleItemTransfer => $this->applyReferencePrice($recurringScheduleItemTransfer, $acceptedPrice, $isNetMode),
        );
    }

    /**
     * @param array<string, int> $acceptedPricesByGroupKey
     * @param array<int, string> $groupKeysByIdRecurringScheduleItem
     *
     * @return array<int, \Generated\Shared\Transfer\RecurringScheduleItemTransfer> Next-delivery price changes keyed by recurring schedule item ID.
     */
    public function applyOccurrencePrices(
        array $acceptedPricesByGroupKey,
        array $groupKeysByIdRecurringScheduleItem,
        bool $isNetMode,
    ): array {
        return $this->createPriceChanges(
            $acceptedPricesByGroupKey,
            $groupKeysByIdRecurringScheduleItem,
            fn (RecurringScheduleItemTransfer $recurringScheduleItemTransfer, int $acceptedPrice): RecurringScheduleItemTransfer => $this->applyNextDeliveryPrice($recurringScheduleItemTransfer, $acceptedPrice, $isNetMode),
        );
    }

    /**
     * @param array<string, int> $acceptedPricesByGroupKey
     * @param array<int, string> $groupKeysByIdRecurringScheduleItem
     * @param callable(\Generated\Shared\Transfer\RecurringScheduleItemTransfer, int): \Generated\Shared\Transfer\RecurringScheduleItemTransfer $priceApplier
     *
     * @return array<int, \Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    protected function createPriceChanges(
        array $acceptedPricesByGroupKey,
        array $groupKeysByIdRecurringScheduleItem,
        callable $priceApplier,
    ): array {
        $recurringScheduleItemTransfers = [];

        foreach ($groupKeysByIdRecurringScheduleItem as $idRecurringScheduleItem => $groupKey) {
            $acceptedPrice = $acceptedPricesByGroupKey[$groupKey] ?? null;

            if ($acceptedPrice === null) {
                continue;
            }

            $recurringScheduleItemTransfers[$idRecurringScheduleItem] = $priceApplier(
                (new RecurringScheduleItemTransfer())->setIdRecurringScheduleItem($idRecurringScheduleItem),
                $acceptedPrice,
            );
        }

        return $recurringScheduleItemTransfers;
    }

    protected function applyReferencePrice(
        RecurringScheduleItemTransfer $recurringScheduleItemTransfer,
        int $acceptedPrice,
        bool $isNetMode,
    ): RecurringScheduleItemTransfer {
        $isNetMode
            ? $recurringScheduleItemTransfer->setReferenceNetPrice($acceptedPrice)
            : $recurringScheduleItemTransfer->setReferenceGrossPrice($acceptedPrice);

        // A standing acceptance supersedes an occurrence acceptance taken earlier in the same window;
        // leaving the one-time price behind would shadow the baseline just agreed.
        return $recurringScheduleItemTransfer
            ->setNextDeliveryNetPrice(null)
            ->setNextDeliveryGrossPrice(null);
    }

    protected function applyNextDeliveryPrice(
        RecurringScheduleItemTransfer $recurringScheduleItemTransfer,
        int $acceptedPrice,
        bool $isNetMode,
    ): RecurringScheduleItemTransfer {
        $isNetMode
            ? $recurringScheduleItemTransfer->setNextDeliveryNetPrice($acceptedPrice)
            : $recurringScheduleItemTransfer->setNextDeliveryGrossPrice($acceptedPrice);

        return $recurringScheduleItemTransfer;
    }
}
