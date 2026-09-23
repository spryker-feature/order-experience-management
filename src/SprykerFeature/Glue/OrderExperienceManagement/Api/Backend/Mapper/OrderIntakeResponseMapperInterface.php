<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper;

use Generated\Api\Backend\OrdersBackendResource;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;

interface OrderIntakeResponseMapperInterface
{
    public function mapOrderIntakeResponseTransferToOrdersBackendResource(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        OrdersBackendResource $resource,
    ): OrdersBackendResource;

    public function mapPlacedOrdersBackendResourceToOrdersBackendResource(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        OrdersBackendResource $placedOrderResource,
        OrdersBackendResource $resource,
    ): OrdersBackendResource;

    /**
     * @return array<int, string>
     */
    public function mapOrderIntakeResponseTransferToValidationMessages(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): array;
}
