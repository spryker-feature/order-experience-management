<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper;

use Generated\Api\Backend\OrdersTotals;
use Generated\Api\Backend\OrdersTotalsTaxBreakdownsBackendObject;
use Generated\Shared\Transfer\OrderTransfer;

class OrderTotalsMapper implements OrderTotalsMapperInterface
{
    public function mapOrderTransferToOrdersTotals(OrderTransfer $orderTransfer): ?OrdersTotals
    {
        $totalsTransfer = $orderTransfer->getTotals();

        if ($totalsTransfer === null) {
            return null;
        }

        return (new OrdersTotals())
            ->setSubtotal($totalsTransfer->getSubtotal())
            ->setExpenseTotal($totalsTransfer->getExpenseTotal())
            ->setDiscountTotal($totalsTransfer->getDiscountTotal())
            ->setTaxTotal($totalsTransfer->getTaxTotal()?->getAmount())
            ->setTaxBreakdown($this->mapTaxBreakdown($orderTransfer))
            ->setGrandTotal($totalsTransfer->getGrandTotal())
            ->setCanceledTotal($totalsTransfer->getCanceledTotal())
            ->setRefundableTotal($totalsTransfer->getRefundTotal())
            ->setRemunerationTotal($totalsTransfer->getRemunerationTotal());
    }

    /**
     * @return array<int, \Generated\Api\Backend\OrdersTotalsTaxBreakdownsBackendObject>
     */
    protected function mapTaxBreakdown(OrderTransfer $orderTransfer): array
    {
        /** @var array<array-key, int> $taxAmountsByRate */
        $taxAmountsByRate = [];

        foreach ($orderTransfer->getItems() as $itemTransfer) {
            $taxAmountsByRate = $this->addTaxAmount($taxAmountsByRate, $itemTransfer->getTaxRate(), $itemTransfer->getSumTaxAmount());
        }

        foreach ($orderTransfer->getExpenses() as $expenseTransfer) {
            $taxAmountsByRate = $this->addTaxAmount($taxAmountsByRate, $expenseTransfer->getTaxRate(), $expenseTransfer->getSumTaxAmount());
        }

        ksort($taxAmountsByRate);

        $taxBreakdown = [];

        foreach ($taxAmountsByRate as $taxRate => $taxAmount) {
            $taxBreakdown[] = (new OrdersTotalsTaxBreakdownsBackendObject())
                ->setTaxRate((float)$taxRate)
                ->setTaxAmount($taxAmount);
        }

        return $taxBreakdown;
    }

    /**
     * @param array<array-key, int> $taxAmountsByRate
     *
     * @return array<array-key, int>
     */
    protected function addTaxAmount(array $taxAmountsByRate, mixed $taxRate, ?int $taxAmount): array
    {
        if ($taxRate === null) {
            return $taxAmountsByRate;
        }

        $key = (string)(float)$taxRate;
        $taxAmountsByRate[$key] = ($taxAmountsByRate[$key] ?? 0) + (int)$taxAmount;

        return $taxAmountsByRate;
    }
}
