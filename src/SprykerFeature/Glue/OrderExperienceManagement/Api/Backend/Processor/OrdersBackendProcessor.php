<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Processor;

use Generated\Api\Backend\OrdersBackendResource;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Spryker\ApiPlatform\State\Processor\AbstractBackendProcessor;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Exception\OrdersBackendExceptionFactoryInterface;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper\OrderIntakeRequestMapperInterface;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper\OrderIntakeResponseMapperInterface;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Reader\OrderResourceReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface;

/**
 * Order intake, exposed as POST /orders.
 */
class OrdersBackendProcessor extends AbstractBackendProcessor
{
    public function __construct(
        protected readonly OrderExperienceManagementFacadeInterface $orderExperienceManagementFacade,
        protected readonly OrdersBackendExceptionFactoryInterface $ordersBackendExceptionFactory,
        protected readonly OrderResourceReaderInterface $orderResourceReader,
        protected readonly OrderIntakeRequestMapperInterface $orderIntakeRequestMapper,
        protected readonly OrderIntakeResponseMapperInterface $orderIntakeResponseMapper,
    ) {
    }

    protected function processPost(mixed $data): OrdersBackendResource
    {
        $orderIntakeResponseTransfer = $this->orderExperienceManagementFacade->createOrderFromIntake(
            $this->orderIntakeRequestMapper->mapOrdersBackendResourceToOrderIntakeRequestTransfer($data, $this->findLocaleName()),
        );

        if ($orderIntakeResponseTransfer->getIsSuccessful() !== true) {
            throw $this->ordersBackendExceptionFactory->createValidationException(
                $this->orderIntakeResponseMapper->mapOrderIntakeResponseTransferToValidationMessages($orderIntakeResponseTransfer),
            );
        }

        return $this->mapOrderIntakeResponseToResource($orderIntakeResponseTransfer, $data);
    }

    protected function mapOrderIntakeResponseToResource(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        OrdersBackendResource $resource,
    ): OrdersBackendResource {
        $orderReference = $orderIntakeResponseTransfer->getOrderReference();
        $orderTransfer = $orderReference !== null ? $this->orderResourceReader->findOrderByReference($orderReference) : null;

        if ($orderTransfer === null) {
            return $this->orderIntakeResponseMapper->mapOrderIntakeResponseTransferToOrdersBackendResource(
                $orderIntakeResponseTransfer,
                $resource,
            );
        }

        $placedOrderResource = $this->orderResourceReader->mapOrderTransferToResource(
            $orderTransfer,
            $this->orderResourceReader->getAvailableTransitionsByOrderItemId([$orderTransfer]),
            true,
        );

        $resource = $this->orderIntakeResponseMapper->mapPlacedOrdersBackendResourceToOrdersBackendResource(
            $orderIntakeResponseTransfer,
            $placedOrderResource,
            $resource,
        );

        return $this->orderResourceReader->expandResource($resource, $orderTransfer);
    }
}
