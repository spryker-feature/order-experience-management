<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper;

use Generated\Api\Backend\OrdersBackendResource;
use Generated\Api\Backend\OrdersItem;
use Generated\Api\Backend\OrdersTotals;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;

class OrderIntakeResponseMapper implements OrderIntakeResponseMapperInterface
{
    public function mapOrderIntakeResponseTransferToOrdersBackendResource(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        OrdersBackendResource $resource,
    ): OrdersBackendResource {
        $resource->orderReference = $orderIntakeResponseTransfer->getOrderReference();
        $resource->customerReference = $orderIntakeResponseTransfer->getCustomerReference();
        $resource->totals = $this->mapTotals($orderIntakeResponseTransfer);
        $resource->items = $this->mapResolvedItems($orderIntakeResponseTransfer);
        $resource->itemsCount = count($resource->items);

        return $resource;
    }

    public function mapPlacedOrdersBackendResourceToOrdersBackendResource(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        OrdersBackendResource $placedOrderResource,
        OrdersBackendResource $resource,
    ): OrdersBackendResource {
        $resource->orderReference = $orderIntakeResponseTransfer->getOrderReference();
        $resource->customerReference = $orderIntakeResponseTransfer->getCustomerReference();
        $resource->createdAt = $placedOrderResource->createdAt;
        $resource->companyUuid = $placedOrderResource->companyUuid;
        $resource->customer = $placedOrderResource->customer;
        $resource->locale = $placedOrderResource->locale;
        $resource->totals = $placedOrderResource->totals;
        $resource->items = $placedOrderResource->items;
        $resource->itemsCount = $placedOrderResource->itemsCount;
        $resource->itemStates = $placedOrderResource->itemStates;
        $resource->availableEvents = $placedOrderResource->availableEvents;
        $resource->expenses = $placedOrderResource->expenses;
        $resource->calculatedDiscounts = $placedOrderResource->calculatedDiscounts;
        $resource->payments = $placedOrderResource->payments;

        return $resource;
    }

    /**
     * @return array<int, string>
     */
    public function mapOrderIntakeResponseTransferToValidationMessages(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): array {
        $messages = [];

        foreach ($orderIntakeResponseTransfer->getValidationIssues() as $validationIssueTransfer) {
            $messages[] = $this->describeIssue($validationIssueTransfer);
        }

        return $messages;
    }

    protected function mapTotals(OrderIntakeResponseTransfer $orderIntakeResponseTransfer): ?OrdersTotals
    {
        $totalsTransfer = $orderIntakeResponseTransfer->getTotals();

        if ($totalsTransfer === null) {
            return null;
        }

        return (new OrdersTotals())
            ->setSubtotal($totalsTransfer->getSubtotal())
            ->setExpenseTotal($totalsTransfer->getExpenseTotal())
            ->setDiscountTotal($totalsTransfer->getDiscountTotal())
            ->setTaxTotal($totalsTransfer->getTaxTotal()?->getAmount())
            ->setGrandTotal($totalsTransfer->getGrandTotal())
            ->setCanceledTotal($totalsTransfer->getCanceledTotal())
            ->setRefundableTotal($totalsTransfer->getRefundTotal())
            ->setRemunerationTotal($totalsTransfer->getRemunerationTotal());
    }

    /**
     * @return array<int, \Generated\Api\Backend\OrdersItem>
     */
    protected function mapResolvedItems(OrderIntakeResponseTransfer $orderIntakeResponseTransfer): array
    {
        $items = [];

        foreach ($orderIntakeResponseTransfer->getItems() as $orderIntakeItemTransfer) {
            $items[] = (new OrdersItem())
                ->setUuid($orderIntakeItemTransfer->getUuid())
                ->setMerchantReference($orderIntakeItemTransfer->getMerchantReference())
                ->setSku($orderIntakeItemTransfer->getSku())
                ->setQuantity($orderIntakeItemTransfer->getQuantity());
        }

        return $items;
    }

    /**
     * The format `field => message`
     * {@see \Spryker\ApiPlatform\Serializer\TranslatingConstraintViolationListNormalizer::enrichError()}
     */
    protected function describeIssue(OrderIntakeValidationIssueTransfer $orderIntakeValidationIssueTransfer): string
    {
        $message = (string)$orderIntakeValidationIssueTransfer->getMessage();
        $field = (string)$orderIntakeValidationIssueTransfer->getField();

        if ($field === '' || str_contains($message, $field)) {
            return $message;
        }

        return sprintf('%s => %s', $field, $message);
    }
}
