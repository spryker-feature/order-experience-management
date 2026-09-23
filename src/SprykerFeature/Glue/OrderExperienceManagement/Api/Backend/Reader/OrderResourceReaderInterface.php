<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Reader;

use Generated\Api\Backend\OrdersBackendResource;
use Generated\Shared\Transfer\OrderTransfer;

interface OrderResourceReaderInterface
{
    public function findOrderByReference(string $orderReference): ?OrderTransfer;

    public function readOrderByReference(string $orderReference): ?OrdersBackendResource;

    public function expandResource(
        OrdersBackendResource $ordersBackendResource,
        OrderTransfer $orderTransfer,
    ): OrdersBackendResource;

    /**
     * @param array<int, \Generated\Shared\Transfer\OrderTransfer> $orderTransfers
     *
     * @return array<int, array<int, string>> Keyed by idSalesOrderItem.
     */
    public function getAvailableTransitionsByOrderItemId(array $orderTransfers): array;

    /**
     * @param array<int, array<string>> $availableTransitionsByOrderItemId
     */
    public function mapOrderTransferToResource(
        OrderTransfer $orderTransfer,
        array $availableTransitionsByOrderItemId,
        bool $isItemRoute,
    ): OrdersBackendResource;
}
