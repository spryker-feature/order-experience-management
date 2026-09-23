<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper;

use Generated\Shared\Transfer\OrderTransfer;

interface OrderItemMapperInterface
{
    /**
     * @param array<int, array<string>> $availableTransitionsByOrderItemId
     *
     * @return array<int, \Generated\Api\Backend\OrdersItem>
     */
    public function mapOrderTransferToOrdersItems(OrderTransfer $orderTransfer, array $availableTransitionsByOrderItemId): array;
}
