<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper;

use ArrayObject;
use Generated\Api\Backend\Orders\OrdersBillingAddressBackendObject;
use Generated\Api\Backend\Orders\OrdersShipmentShippingAddressBackendObject;
use Generated\Api\Backend\OrdersBackendResource;
use Generated\Api\Backend\OrdersItem;
use Generated\Api\Backend\OrdersItemShipmentShippingAddressBackendObject;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\OrderIntakeItemTransfer;
use Generated\Shared\Transfer\OrderIntakePackagingAmountTransfer;
use Generated\Shared\Transfer\OrderIntakeProductOptionTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeSalesUnitTransfer;

class OrderIntakeRequestMapper implements OrderIntakeRequestMapperInterface
{
    public function mapOrdersBackendResourceToOrderIntakeRequestTransfer(
        OrdersBackendResource $resource,
        ?string $localeName,
    ): OrderIntakeRequestTransfer {
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())->fromArray(
            array_filter($resource->toArray(), static fn (mixed $value): bool => is_scalar($value)),
            true,
        );

        return $orderIntakeRequestTransfer
            ->setStoreName($resource->store)
            ->setCurrencyCode($resource->currency)
            ->setShipmentMethodName($resource->shipment?->getShipmentMethod())
            ->setRequestedDeliveryDate($resource->shipment?->getRequestedDeliveryDate())
            ->setPaymentMethodName($resource->paymentMethod)
            ->setCustomer($this->mapCustomer($resource))
            ->setBillingAddress($this->mapTypedAddress($resource->billingAddress))
            ->setShippingAddress($this->mapTypedAddress($resource->shipment?->getShippingAddress()))
            ->setItems($this->mapItems($resource))
            ->setCartCodes($resource->cartCodes)
            ->setOrderLocaleName($resource->locale)
            ->setLocaleName($localeName);
    }

    protected function mapCustomer(OrdersBackendResource $resource): CustomerTransfer
    {
        return (new CustomerTransfer())->setCustomerReference($resource->customerReference);
    }

    protected function mapTypedAddress(
        OrdersBillingAddressBackendObject|OrdersShipmentShippingAddressBackendObject|OrdersItemShipmentShippingAddressBackendObject|null $address
    ): ?AddressTransfer {
        $addressData = $address?->toArray();

        if ($addressData === null || $addressData === []) {
            return null;
        }

        return (new AddressTransfer())->fromArray($addressData, true);
    }

    /**
     * @return \ArrayObject<int, \Generated\Shared\Transfer\OrderIntakeItemTransfer>
     */
    protected function mapItems(OrdersBackendResource $resource): ArrayObject
    {
        $orderIntakeItemTransfers = new ArrayObject();

        foreach ($resource->items ?? [] as $item) {
            $shipment = $item->getShipment();

            $orderIntakeItemTransfers->append(
                (new OrderIntakeItemTransfer())
                    ->setMerchantReference($item->getMerchantReference())
                    ->setProductOfferReference($item->getProductOfferReference())
                    ->setSku($item->getSku())
                    ->setQuantity($item->getQuantity())
                    ->setUnitCustomPrice($item->getUnitCustomPrice())
                    ->setCartNote($item->getCartNote())
                    ->setShipmentMethodName($shipment?->getShipmentMethod())
                    ->setShippingAddress($this->mapTypedAddress($shipment?->getShippingAddress()))
                    ->setRequestedDeliveryDate($shipment?->getRequestedDeliveryDate())
                    ->setProductOptions($this->mapProductOptions($item))
                    ->setSalesUnit($this->mapSalesUnit($item))
                    ->setPackagingAmount($this->mapPackagingAmount($item)),
            );
        }

        return $orderIntakeItemTransfers;
    }

    /**
     * @see \SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeProductOptionExpander
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\OrderIntakeProductOptionTransfer>
     */
    protected function mapProductOptions(OrdersItem $item): ArrayObject
    {
        $orderIntakeProductOptionTransfers = new ArrayObject();

        foreach ($item->getProductOptions() ?? [] as $productOption) {
            $orderIntakeProductOptionTransfers->append(
                (new OrderIntakeProductOptionTransfer())->setSku($productOption->getSku()),
            );
        }

        return $orderIntakeProductOptionTransfers;
    }

    protected function mapSalesUnit(OrdersItem $item): ?OrderIntakeSalesUnitTransfer
    {
        $salesUnit = $item->getSalesUnit();

        if ($salesUnit === null) {
            return null;
        }

        return (new OrderIntakeSalesUnitTransfer())
            ->setCode($salesUnit->getCode())
            ->setAmount($salesUnit->getAmount());
    }

    protected function mapPackagingAmount(OrdersItem $item): ?OrderIntakePackagingAmountTransfer
    {
        $packagingAmount = $item->getPackagingAmount();

        if ($packagingAmount === null) {
            return null;
        }

        return (new OrderIntakePackagingAmountTransfer())
            ->setAmount($packagingAmount->getAmount())
            ->setSalesUnitCode($packagingAmount->getSalesUnitCode());
    }
}
