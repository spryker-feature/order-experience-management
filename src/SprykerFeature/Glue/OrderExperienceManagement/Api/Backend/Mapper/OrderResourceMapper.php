<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper;

use Generated\Api\Backend\Orders\OrdersBillingAddressBackendObject;
use Generated\Api\Backend\Orders\OrdersCustomerBackendObject;
use Generated\Api\Backend\OrdersBackendResource;
use Generated\Api\Backend\OrdersCalculatedDiscount;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\OrderTransfer;

class OrderResourceMapper implements OrderResourceMapperInterface
{
    public function __construct(
        protected readonly OrderItemMapperInterface $orderItemMapper,
        protected readonly OrderTotalsMapperInterface $orderTotalsMapper,
        protected readonly OrderPaymentMapperInterface $orderPaymentMapper,
        protected readonly OrderExpenseMapperInterface $orderExpenseMapper,
        protected readonly OrderAddressMapperInterface $orderAddressMapper,
    ) {
    }

    /**
     * @param array<int, array<string>> $availableTransitionsByOrderItemId
     */
    public function mapOrderTransferToResource(
        OrderTransfer $orderTransfer,
        array $availableTransitionsByOrderItemId,
        bool $isItemRoute,
    ): OrdersBackendResource {
        $resource = new OrdersBackendResource();

        $resource->orderReference = $orderTransfer->getOrderReference();
        $resource->customerReference = $orderTransfer->getCustomerReference();
        $resource->store = $orderTransfer->getStore();
        $resource->currency = $orderTransfer->getCurrencyIsoCode();
        $resource->orderCustomReference = $orderTransfer->getOrderCustomReference();
        $resource->createdAt = $orderTransfer->getCreatedAt();
        $resource->totals = $this->orderTotalsMapper->mapOrderTransferToOrdersTotals($orderTransfer);
        $resource->expenses = $this->orderExpenseMapper->mapOrderTransferToOrdersExpenses($orderTransfer);
        $resource->calculatedDiscounts = $this->mapCalculatedDiscounts($orderTransfer);
        $resource->payments = $this->orderPaymentMapper->mapOrderTransferToOrdersPayments($orderTransfer);
        $resource->items = $isItemRoute
            ? $this->orderItemMapper->mapOrderTransferToOrdersItems($orderTransfer, $availableTransitionsByOrderItemId)
            : null;
        $resource->itemsCount = $orderTransfer->getItems()->count();
        $resource->itemStates = $this->extractDistinctItemStates($orderTransfer);
        $resource->availableEvents = $this->extractAvailableEvents($orderTransfer, $availableTransitionsByOrderItemId);
        $resource->customer = $this->mapCustomer($orderTransfer);
        $resource->billingAddress = $this->mapBillingAddress($orderTransfer->getBillingAddress());
        $resource->companyBusinessUnitUuid = $orderTransfer->getCompanyBusinessUnitUuid();
        $resource->companyUuid = $orderTransfer->getCompanyUuid();
        $resource->locale = $orderTransfer->getLocale()?->getLocaleName();

        return $resource;
    }

    protected function mapCustomer(OrderTransfer $orderTransfer): OrdersCustomerBackendObject
    {
        return (new OrdersCustomerBackendObject())
            ->setEmail($orderTransfer->getEmail())
            ->setSalutation($orderTransfer->getSalutation())
            ->setFirstName($orderTransfer->getFirstName())
            ->setLastName($orderTransfer->getLastName());
    }

    protected function mapBillingAddress(?AddressTransfer $addressTransfer): ?OrdersBillingAddressBackendObject
    {
        if ($addressTransfer === null) {
            return null;
        }

        return OrdersBillingAddressBackendObject::fromArray($this->orderAddressMapper->mapAddressTransferToArray($addressTransfer));
    }

    /**
     * @return array<int, \Generated\Api\Backend\OrdersCalculatedDiscount>
     */
    protected function mapCalculatedDiscounts(OrderTransfer $orderTransfer): array
    {
        $calculatedDiscounts = [];

        foreach ($orderTransfer->getCalculatedDiscounts() as $calculatedDiscountTransfer) {
            $calculatedDiscounts[] = (new OrdersCalculatedDiscount())
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
     * @return array<int, string>
     */
    protected function extractDistinctItemStates(OrderTransfer $orderTransfer): array
    {
        $itemStates = [];

        foreach ($orderTransfer->getItems() as $itemTransfer) {
            $stateName = $itemTransfer->getState()?->getName();

            if ($stateName !== null && !in_array($stateName, $itemStates, true)) {
                $itemStates[] = $stateName;
            }
        }

        return $itemStates;
    }

    /**
     * @param array<int, array<string>> $availableTransitionsByOrderItemId
     *
     * @return array<int, string>
     */
    protected function extractAvailableEvents(
        OrderTransfer $orderTransfer,
        array $availableTransitionsByOrderItemId,
    ): array {
        $availableTransitions = [];

        foreach ($orderTransfer->getItems() as $itemTransfer) {
            $idSalesOrderItem = $itemTransfer->getIdSalesOrderItem();

            foreach ($availableTransitionsByOrderItemId[$idSalesOrderItem] ?? [] as $manualEvent) {
                if (!in_array($manualEvent, $availableTransitions, true)) {
                    $availableTransitions[] = $manualEvent;
                }
            }
        }

        return $availableTransitions;
    }
}
