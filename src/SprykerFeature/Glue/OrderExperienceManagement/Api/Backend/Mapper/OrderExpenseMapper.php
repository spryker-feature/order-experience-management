<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper;

use Generated\Api\Backend\OrdersExpense;
use Generated\Shared\Transfer\ExpenseTransfer;
use Generated\Shared\Transfer\OrderTransfer;

class OrderExpenseMapper implements OrderExpenseMapperInterface
{
    /**
     * @return array<int, \Generated\Api\Backend\OrdersExpense>
     */
    public function mapOrderTransferToOrdersExpenses(OrderTransfer $orderTransfer): array
    {
        $expenses = [];

        foreach ($orderTransfer->getExpenses() as $expenseTransfer) {
            $expenses[] = $this->mapExpense($expenseTransfer);
        }

        return $expenses;
    }

    protected function mapExpense(ExpenseTransfer $expenseTransfer): OrdersExpense
    {
        $taxRate = $expenseTransfer->getTaxRate();

        return (new OrdersExpense())
            ->setType($expenseTransfer->getType())
            ->setName($expenseTransfer->getName())
            ->setSumPrice($expenseTransfer->getSumPrice())
            ->setTaxRate($taxRate === null ? null : (float)$taxRate)
            ->setSumTaxAmount($expenseTransfer->getSumTaxAmount())
            ->setSumDiscountAmountAggregation($expenseTransfer->getSumDiscountAmountAggregation())
            ->setSumPriceToPayAggregation($expenseTransfer->getSumPriceToPayAggregation())
            ->setCanceledAmount($expenseTransfer->getCanceledAmount());
    }
}
