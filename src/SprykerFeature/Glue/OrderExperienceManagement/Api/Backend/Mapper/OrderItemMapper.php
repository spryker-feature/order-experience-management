<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper;

use Generated\Api\Backend\OrdersItem;
use Generated\Api\Backend\OrdersItemCalculatedDiscountsBackendObject;
use Generated\Api\Backend\OrdersItemPackagingAmountBackendObject;
use Generated\Api\Backend\OrdersItemProductOptionsBackendObject;
use Generated\Api\Backend\OrdersItemSalesUnitBackendObject;
use Generated\Api\Backend\OrdersItemShipmentBackendObject;
use Generated\Api\Backend\OrdersItemShipmentShippingAddressBackendObject;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderTransfer;

class OrderItemMapper implements OrderItemMapperInterface
{
    /**
     * Mirrors the scale the packaging-unit models divide amounts at, so a figure reported here matches the one the platform computed.
     */
    protected const int AMOUNT_DIVISION_SCALE = 10;

    public function __construct(protected readonly OrderAddressMapperInterface $orderAddressMapper)
    {
    }

    /**
     * @param array<int, array<string>> $availableTransitionsByOrderItemId
     *
     * @return array<int, \Generated\Api\Backend\OrdersItem>
     */
    public function mapOrderTransferToOrdersItems(OrderTransfer $orderTransfer, array $availableTransitionsByOrderItemId): array
    {
        $items = [];

        foreach ($orderTransfer->getItems() as $itemTransfer) {
            $items[] = (new OrdersItem())
                ->setUuid($itemTransfer->getUuid())
                ->setMerchantReference($itemTransfer->getMerchantReference())
                ->setProductOfferReference($itemTransfer->getProductOfferReference())
                ->setCartNote($itemTransfer->getCartNote())
                ->setSku($itemTransfer->getSku())
                ->setName($itemTransfer->getName())
                ->setQuantity($itemTransfer->getQuantity())
                ->setUnitPrice($itemTransfer->getSumPrice())
                ->setSumPrice($itemTransfer->getSumPrice())
                ->setSumSubtotalAggregation($itemTransfer->getSumSubtotalAggregation())
                ->setSumDiscountAmountFullAggregation($itemTransfer->getSumDiscountAmountFullAggregation())
                ->setSumPriceToPayAggregation($itemTransfer->getSumPriceToPayAggregation())
                ->setShipment($this->mapItemShipment($itemTransfer))
                ->setTaxRate($this->toFloat($itemTransfer->getTaxRate()))
                ->setSumTaxAmount($itemTransfer->getSumTaxAmount())
                ->setRefundableAmount($itemTransfer->getRefundableAmount())
                ->setCanceledAmount($itemTransfer->getCanceledAmount())
                ->setCalculatedDiscounts($this->mapItemCalculatedDiscounts($itemTransfer))
                ->setProductOptions($this->mapItemProductOptions($itemTransfer))
                ->setSalesUnit($this->mapItemSalesUnit($itemTransfer))
                ->setPackagingAmount($this->mapItemPackagingAmount($itemTransfer))
                ->setState($itemTransfer->getState()?->getName())
                ->setAvailableEvents($availableTransitionsByOrderItemId[$itemTransfer->getIdSalesOrderItem()] ?? []);
        }

        return $items;
    }

    protected function mapItemShipment(ItemTransfer $itemTransfer): ?OrdersItemShipmentBackendObject
    {
        $shipmentTransfer = $itemTransfer->getShipment();

        if ($shipmentTransfer === null) {
            return null;
        }

        $shippingAddress = $this->orderAddressMapper->mapAddressTransferToArray($shipmentTransfer->getShippingAddress());

        return (new OrdersItemShipmentBackendObject())
            ->setShipmentMethod($shipmentTransfer->getMethod()?->getName())
            ->setRequestedDeliveryDate($shipmentTransfer->getRequestedDeliveryDate())
            ->setShippingAddress(
                $shippingAddress === []
                    ? null
                    : OrdersItemShipmentShippingAddressBackendObject::fromArray($shippingAddress),
            );
    }

    /**
     * @return array<int, \Generated\Api\Backend\OrdersItemCalculatedDiscountsBackendObject>
     */
    protected function mapItemCalculatedDiscounts(ItemTransfer $itemTransfer): array
    {
        $calculatedDiscounts = [];

        foreach ($itemTransfer->getCalculatedDiscounts() as $calculatedDiscountTransfer) {
            $calculatedDiscounts[] = (new OrdersItemCalculatedDiscountsBackendObject())
                ->setDisplayName($calculatedDiscountTransfer->getDisplayName())
                ->setDescription($calculatedDiscountTransfer->getDescription())
                ->setVoucherCode($calculatedDiscountTransfer->getVoucherCode())
                ->setQuantity($calculatedDiscountTransfer->getQuantity())
                ->setUnitAmount($calculatedDiscountTransfer->getUnitAmount())
                ->setSumAmount($calculatedDiscountTransfer->getSumAmount());
        }

        return $calculatedDiscounts;
    }

    /**
     * @see \Spryker\Zed\ProductPackagingUnit\Business\Model\OrderItem\SplittableOrderItemTransformer
     */
    protected function mapItemPackagingAmount(ItemTransfer $itemTransfer): ?OrdersItemPackagingAmountBackendObject
    {
        $amount = $itemTransfer->getAmount();

        if ($amount === null) {
            return null;
        }

        $quantity = $itemTransfer->getQuantity();
        $productMeasurementSalesUnitTransfer = $itemTransfer->getAmountSalesUnit();

        return (new OrdersItemPackagingAmountBackendObject())
            ->setAmount($quantity ? $amount->divide($quantity, static::AMOUNT_DIVISION_SCALE)->toFloat() : null)
            ->setSalesUnitCode($productMeasurementSalesUnitTransfer?->getProductMeasurementUnit()?->getCode())
            ->setSalesUnitName($productMeasurementSalesUnitTransfer?->getProductMeasurementUnit()?->getName())
            ->setLeadProductSku($itemTransfer->getAmountLeadProduct()?->getSku());
    }

    protected function mapItemSalesUnit(ItemTransfer $itemTransfer): ?OrdersItemSalesUnitBackendObject
    {
        $productMeasurementSalesUnitTransfer = $itemTransfer->getQuantitySalesUnit();

        if ($productMeasurementSalesUnitTransfer === null) {
            return null;
        }

        $conversion = $productMeasurementSalesUnitTransfer->getConversion();

        return (new OrdersItemSalesUnitBackendObject())
            ->setCode($productMeasurementSalesUnitTransfer->getProductMeasurementUnit()?->getCode())
            ->setName($productMeasurementSalesUnitTransfer->getProductMeasurementUnit()?->getName())
            ->setBaseUnitName(
                $productMeasurementSalesUnitTransfer->getProductMeasurementBaseUnit()?->getProductMeasurementUnit()?->getName(),
            )
            ->setConversion($conversion === null ? null : (float)$conversion)
            ->setPrecision($productMeasurementSalesUnitTransfer->getPrecision())
            ->setAmount($this->deriveSalesUnitAmount($itemTransfer, $conversion));
    }

    protected function deriveSalesUnitAmount(ItemTransfer $itemTransfer, ?float $conversion): ?float
    {
        $quantity = $itemTransfer->getQuantity();

        if ($quantity === null || $conversion === null || $conversion <= 0.0) {
            return null;
        }

        return $quantity / $conversion;
    }

    /**
     * @return array<int, \Generated\Api\Backend\OrdersItemProductOptionsBackendObject>
     */
    protected function mapItemProductOptions(ItemTransfer $itemTransfer): array
    {
        $productOptions = [];

        foreach ($itemTransfer->getProductOptions() as $productOptionTransfer) {
            $productOptions[] = (new OrdersItemProductOptionsBackendObject())
                ->setSku($productOptionTransfer->getSku())
                ->setGroupName($productOptionTransfer->getGroupName())
                ->setValue($productOptionTransfer->getValue())
                ->setUnitPrice($productOptionTransfer->getUnitPrice())
                ->setSumPrice($productOptionTransfer->getSumPrice())
                ->setTaxRate($this->toFloat($productOptionTransfer->getTaxRate()));
        }

        return $productOptions;
    }

    protected function toFloat(mixed $value): ?float
    {
        return $value === null ? null : (float)$value;
    }
}
