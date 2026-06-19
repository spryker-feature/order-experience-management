<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander;

use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface;

class RecurringScheduleItemExpander extends AbstractRecurringScheduleExpander implements RecurringScheduleItemExpanderInterface
{
     /**
      * @see \Spryker\Shared\Price\PriceConfig::PRICE_MODE_NET
      */
    protected const string PRICE_MODE_NET = 'NET_MODE';

    public function __construct(protected OrderExperienceManagementRepositoryInterface $repository)
    {
    }

    public function expandWithItems(
        RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer,
    ): RecurringScheduleCollectionTransfer {
        $scheduleIds = $this->extractScheduleIds($recurringScheduleCollectionTransfer);

        if ($scheduleIds === []) {
            return $recurringScheduleCollectionTransfer;
        }

        $recurringScheduleItemTransfers = $this->repository->findScheduleItemsByScheduleIds($scheduleIds);
        $recurringScheduleItemTransfersByScheduleId = $this->groupItemsByScheduleId($recurringScheduleItemTransfers);

        return $this->applyItemsAndTotals($recurringScheduleCollectionTransfer, $recurringScheduleItemTransfersByScheduleId);
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $recurringScheduleItemTransfers
     *
     * @return array<int, list<\Generated\Shared\Transfer\RecurringScheduleItemTransfer>>
     */
    protected function groupItemsByScheduleId(array $recurringScheduleItemTransfers): array
    {
        $recurringScheduleItemTransfersByScheduleId = [];

        foreach ($recurringScheduleItemTransfers as $recurringScheduleItemTransfer) {
            $recurringScheduleItemTransfersByScheduleId[$recurringScheduleItemTransfer->getIdRecurringScheduleOrFail()][] = $recurringScheduleItemTransfer;
        }

        return $recurringScheduleItemTransfersByScheduleId;
    }

    /**
     * @param array<int, list<\Generated\Shared\Transfer\RecurringScheduleItemTransfer>> $recurringScheduleItemTransfersByScheduleId
     */
    protected function applyItemsAndTotals(
        RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer,
        array $recurringScheduleItemTransfersByScheduleId,
    ): RecurringScheduleCollectionTransfer {
        foreach ($recurringScheduleCollectionTransfer->getRecurringSchedules() as $recurringScheduleTransfer) {
            $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();
            $scheduleItems = $recurringScheduleItemTransfersByScheduleId[$idRecurringSchedule] ?? [];

            $this->applyItemsToSchedule($recurringScheduleTransfer, $scheduleItems);
        }

        return $recurringScheduleCollectionTransfer;
    }

    /**
     * @param list<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $recurringScheduleItemTransfers
     */
    protected function applyItemsToSchedule(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        array $recurringScheduleItemTransfers,
    ): void {
        $estimatedTotal = 0;

        foreach ($recurringScheduleItemTransfers as $recurringScheduleItemTransfer) {
            $itemTotal = $this->calculateItemTotal($recurringScheduleTransfer, $recurringScheduleItemTransfer);
            $recurringScheduleItemTransfer->setItemTotal($itemTotal);
            $recurringScheduleTransfer->addItem($recurringScheduleItemTransfer);
            $estimatedTotal += $itemTotal;
        }

        $recurringScheduleTransfer->setEstimatedTotal($estimatedTotal);
    }

    protected function calculateItemTotal(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        RecurringScheduleItemTransfer $recurringScheduleItemTransfer,
    ): int {
        $unitPrice = $recurringScheduleTransfer->getPriceMode() === static::PRICE_MODE_NET
            ? (int)$recurringScheduleItemTransfer->getReferenceNetPrice()
            : (int)$recurringScheduleItemTransfer->getReferenceGrossPrice();

        return (int)$recurringScheduleItemTransfer->getQuantity() * $unitPrice;
    }
}
