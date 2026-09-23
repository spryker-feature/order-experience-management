<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper;

use Generated\Api\Backend\OrderTransitionsBackendResource;
use Generated\Shared\Transfer\OrderItemTransitionRequestTransfer;
use Generated\Shared\Transfer\OrderItemTransitionResponseTransfer;

interface OrderTransitionsResourceMapperInterface
{
    public function mapOrderTransitionsBackendResourceToOrderItemTransitionRequestTransfer(
        ?OrderTransitionsBackendResource $resource,
        ?string $orderReference,
    ): OrderItemTransitionRequestTransfer;

    public function mapOrderItemTransitionResponseTransferToOrderTransitionsBackendResource(
        OrderItemTransitionResponseTransfer $orderItemTransitionResponseTransfer,
    ): OrderTransitionsBackendResource;

    /**
     * Returns null when the response carries nothing to report.
     */
    public function mapOrderItemTransitionResponseTransferToErrorMessage(
        OrderItemTransitionResponseTransfer $orderItemTransitionResponseTransfer,
    ): ?string;
}
