<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Reader;

use Generated\Shared\Transfer\CommentTransfer;
use Generated\Shared\Transfer\OrderConditionsTransfer;
use Generated\Shared\Transfer\OrderCriteriaTransfer;
use Spryker\Zed\Sales\Business\SalesFacadeInterface;

class OrderCommentReader implements OrderCommentReaderInterface
{
    public function __construct(protected readonly SalesFacadeInterface $salesFacade)
    {
    }

    /**
     * @see \SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Provider\OrdersBackendProvider::provideItem()
     */
    public function findIdSalesOrderByOrderReference(string $orderReference): ?int
    {
        if ($orderReference === '') {
            return null;
        }

        $orderCollectionTransfer = $this->salesFacade->getOrderCollection(
            (new OrderCriteriaTransfer())->setOrderConditions(
                (new OrderConditionsTransfer())->addOrderReference($orderReference),
            ),
        );

        foreach ($orderCollectionTransfer->getOrders() as $orderTransfer) {
            return $orderTransfer->getIdSalesOrderOrFail();
        }

        return null;
    }

    /**
     * @return list<\Generated\Shared\Transfer\CommentTransfer>
     */
    public function getCommentsByIdSalesOrder(int $idSalesOrder): array
    {
        $commentTransfers = iterator_to_array(
            $this->salesFacade->getOrderCommentsByIdSalesOrder($idSalesOrder)->getComments(),
        );

        usort(
            $commentTransfers,
            static fn (CommentTransfer $left, CommentTransfer $right): int => $left->getIdSalesOrderCommentOrFail()
                <=> $right->getIdSalesOrderCommentOrFail(),
        );

        return $commentTransfers;
    }
}
