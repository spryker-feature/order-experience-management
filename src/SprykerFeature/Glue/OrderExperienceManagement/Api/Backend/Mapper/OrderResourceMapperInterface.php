<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper;

use Generated\Api\Backend\OrdersBackendResource;
use Generated\Shared\Transfer\OrderTransfer;

interface OrderResourceMapperInterface
{
    /**
     * @param array<int, array<string>> $availableTransitionsByOrderItemId
     */
    public function mapOrderTransferToResource(
        OrderTransfer $orderTransfer,
        array $availableTransitionsByOrderItemId,
        bool $isItemRoute,
    ): OrdersBackendResource;
}
