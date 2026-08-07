<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition;

use Generated\Shared\Transfer\ExpenseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper\RecurringScheduleItemMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Scope\ScheduleReviewScopeStrategyInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;

class ScheduleReviewItemAdder implements ScheduleReviewItemAdderInterface
{
    public function __construct(
        protected readonly RecurringScheduleItemMapperInterface $recurringScheduleItemMapper,
        protected readonly OrderExperienceManagementEntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<int, array<\Generated\Shared\Transfer\ItemTransfer>> $itemTransfersByAdditionIndex
     */
    public function addItems(
        array $itemTransfersByAdditionIndex,
        RecurringScheduleTransfer $recurringScheduleTransfer,
        ScheduleReviewScopeStrategyInterface $scheduleReviewScopeStrategy,
    ): void {
        if ($itemTransfersByAdditionIndex === []) {
            return;
        }

        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();
        $itemTransfers = array_merge([], ...$itemTransfersByAdditionIndex);

        $recurringScheduleItemTransfers = [];

        foreach ($itemTransfers as $itemTransfer) {
            $recurringScheduleItemTransfers[] = $this->createRecurringScheduleItemTransfer(
                $itemTransfer,
                $idRecurringSchedule,
                $scheduleReviewScopeStrategy,
            );
        }

        $this->entityManager->createRecurringScheduleItemCollection($recurringScheduleItemTransfers);
    }

    protected function createRecurringScheduleItemTransfer(
        ItemTransfer $itemTransfer,
        int $idRecurringSchedule,
        ScheduleReviewScopeStrategyInterface $scheduleReviewScopeStrategy,
    ): RecurringScheduleItemTransfer {
        $recurringScheduleItemTransfer = $this->recurringScheduleItemMapper->mapItemToRecurringScheduleItem(
            $itemTransfer,
            $idRecurringSchedule,
            $this->buildShipmentData($itemTransfer),
        );

        $scheduleReviewScopeStrategy->applyAddedItemScope($recurringScheduleItemTransfer);

        return $recurringScheduleItemTransfer;
    }

    /**
     * @return array<string, array{idShipmentMethod: int, unitGrossPrice: int, unitNetPrice: int}>
     */
    protected function buildShipmentData(ItemTransfer $itemTransfer): array
    {
        $shipmentMethodTransfer = $itemTransfer->getShipment()?->getMethod();

        if ($shipmentMethodTransfer?->getIdShipmentMethod() === null) {
            return [];
        }

        $shipmentTypeUuid = $itemTransfer->getShipment()->getShipmentTypeUuid() ?? '';
        $shipmentPrice = (int)$shipmentMethodTransfer->getStoreCurrencyPrice();

        return [
            $shipmentTypeUuid => [
                ShipmentMethodTransfer::ID_SHIPMENT_METHOD => $shipmentMethodTransfer->getIdShipmentMethod(),
                ExpenseTransfer::UNIT_GROSS_PRICE => $shipmentPrice,
                ExpenseTransfer::UNIT_NET_PRICE => $shipmentPrice,
            ],
        ];
    }
}
