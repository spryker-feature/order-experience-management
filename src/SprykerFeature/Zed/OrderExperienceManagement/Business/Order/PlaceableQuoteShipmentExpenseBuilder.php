<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order;

use Generated\Shared\Transfer\ExpenseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Spryker\Service\Shipment\ShipmentServiceInterface;

class PlaceableQuoteShipmentExpenseBuilder implements PlaceableQuoteShipmentExpenseBuilderInterface
{
    /**
     * @see \Spryker\Shared\Shipment\ShipmentConfig::SHIPMENT_EXPENSE_TYPE
     */
    protected const string SHIPMENT_EXPENSE_TYPE = 'SHIPMENT_EXPENSE_TYPE';

    /**
     * @see \Spryker\Shared\Price\PriceConfig::PRICE_MODE_NET
     */
    protected const string PRICE_MODE_NET = 'NET_MODE';

    public function __construct(protected readonly ShipmentServiceInterface $shipmentService)
    {
    }

    /**
     * Shipment groups introduced on the review page (added items) have no serialized expense in the schedule's
     * quote data because the checkout shipment step that normally creates expenses is bypassed. Without the
     * expense, the placed order carries a shipment without a delivery method cost.
     */
    public function appendMissingShipmentExpenses(
        QuoteTransfer $quoteTransfer,
        RecurringScheduleTransfer $recurringScheduleTransfer,
    ): QuoteTransfer {
        $scheduleItemsByShipmentMethodId = $this->indexScheduleItemsByShipmentMethodId($recurringScheduleTransfer);
        $coveredShipmentHashKeys = $this->indexExpenseShipmentHashKeys($quoteTransfer);

        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $shipmentTransfer = $itemTransfer->getShipment();
            $idShipmentMethod = $shipmentTransfer?->getMethod()?->getIdShipmentMethod();

            if ($shipmentTransfer === null || $idShipmentMethod === null) {
                continue;
            }

            $shipmentHashKey = $this->shipmentService->getShipmentHashKey($shipmentTransfer);

            if (isset($coveredShipmentHashKeys[$shipmentHashKey])) {
                continue;
            }

            $coveredShipmentHashKeys[$shipmentHashKey] = true;
            $quoteTransfer->addExpense($this->createShipmentExpense(
                $quoteTransfer,
                $shipmentTransfer,
                $scheduleItemsByShipmentMethodId[$idShipmentMethod] ?? null,
            ));
        }

        return $quoteTransfer;
    }

    /**
     * @return array<int, \Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    protected function indexScheduleItemsByShipmentMethodId(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        $scheduleItemsByShipmentMethodId = [];

        foreach ($recurringScheduleTransfer->getItems() as $recurringScheduleItemTransfer) {
            $idShipmentMethod = $recurringScheduleItemTransfer->getIdShipmentMethod();

            if ($idShipmentMethod === null) {
                continue;
            }

            $scheduleItemsByShipmentMethodId[$idShipmentMethod] ??= $recurringScheduleItemTransfer;
        }

        return $scheduleItemsByShipmentMethodId;
    }

    /**
     * @return array<string, bool>
     */
    protected function indexExpenseShipmentHashKeys(QuoteTransfer $quoteTransfer): array
    {
        $coveredShipmentHashKeys = [];

        foreach ($quoteTransfer->getExpenses() as $expenseTransfer) {
            $expenseShipmentTransfer = $expenseTransfer->getShipment();

            if ($expenseShipmentTransfer === null) {
                continue;
            }

            $coveredShipmentHashKeys[$this->shipmentService->getShipmentHashKey($expenseShipmentTransfer)] = true;
        }

        return $coveredShipmentHashKeys;
    }

    protected function createShipmentExpense(
        QuoteTransfer $quoteTransfer,
        ShipmentTransfer $shipmentTransfer,
        ?RecurringScheduleItemTransfer $recurringScheduleItemTransfer,
    ): ExpenseTransfer {
        $isNetMode = $quoteTransfer->getPriceMode() === static::PRICE_MODE_NET;
        $shipmentPrice = $this->resolveShipmentPrice($shipmentTransfer, $recurringScheduleItemTransfer, $isNetMode);

        $expenseTransfer = (new ExpenseTransfer())
            ->setType(static::SHIPMENT_EXPENSE_TYPE)
            ->setName($shipmentTransfer->getMethod()?->getName())
            ->setShipment($shipmentTransfer)
            ->setQuantity(1)
            ->setUnitGrossPrice($isNetMode ? 0 : $shipmentPrice)
            ->setUnitNetPrice($isNetMode ? $shipmentPrice : 0);

        return $expenseTransfer
            ->setSumGrossPrice($expenseTransfer->getUnitGrossPrice())
            ->setSumNetPrice($expenseTransfer->getUnitNetPrice());
    }

    protected function resolveShipmentPrice(
        ShipmentTransfer $shipmentTransfer,
        ?RecurringScheduleItemTransfer $recurringScheduleItemTransfer,
        bool $isNetMode,
    ): int {
        $storedShipmentPrice = $isNetMode
            ? $recurringScheduleItemTransfer?->getShipmentUnitNetPrice()
            : $recurringScheduleItemTransfer?->getShipmentUnitGrossPrice();

        return $storedShipmentPrice ?? (int)($shipmentTransfer->getMethod()?->getStoreCurrencyPrice() ?? 0);
    }
}
