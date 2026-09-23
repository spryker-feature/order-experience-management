<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Reader;

use ArrayObject;
use Generated\Api\Backend\OrdersBackendResource;
use Generated\Api\Backend\OrdersComment;
use Generated\Shared\Transfer\ItemCollectionTransfer;
use Generated\Shared\Transfer\OrderConditionsTransfer;
use Generated\Shared\Transfer\OrderCriteriaTransfer;
use Generated\Shared\Transfer\OrderItemFilterTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Spryker\Service\Container\Attributes\Plugins;
use Spryker\Zed\Sales\Business\SalesFacadeInterface;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper\OrderResourceMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface;

/**
 * Reads one sales order and maps it onto the Backend API `orders` resource — the single place both
 * `GET /orders/{orderReference}` and `POST /orders` build that view from.
 */
class OrderResourceReader implements OrderResourceReaderInterface
{
    /**
     * @param array<\SprykerFeature\Glue\OrderExperienceManagement\Dependency\Plugin\OrderResourceExpanderPluginInterface> $orderResourceExpanderPlugins
     */
    public function __construct(
        protected readonly SalesFacadeInterface $salesFacade,
        protected readonly OrderExperienceManagementFacadeInterface $orderExperienceManagementFacade,
        protected readonly OrderCommentReaderInterface $orderCommentReader,
        protected readonly OrderResourceMapperInterface $orderResourceMapper,
        #[Plugins(dependencyProviderMethod: 'getOrderResourceExpanderPlugins')]
        protected readonly array $orderResourceExpanderPlugins = [],
    ) {
    }

    public function findOrderByReference(string $orderReference): ?OrderTransfer
    {
        if ($orderReference === '') {
            return null;
        }

        $orderCriteriaTransfer = (new OrderCriteriaTransfer())
            ->setOrderConditions(
                (new OrderConditionsTransfer())
                    ->addOrderReference($orderReference)
                    ->setWithOrderExpanderPlugins(true),
            );

        $orderTransfer = $this->salesFacade->getOrderCollection($orderCriteriaTransfer)
            ->getOrders()
            ->getIterator()
            ->current();

        if ($orderTransfer === null) {
            return null;
        }

        $this->replaceItemsWithExpandedItems($orderTransfer, $orderReference);

        return $orderTransfer;
    }

    public function readOrderByReference(string $orderReference): ?OrdersBackendResource
    {
        $orderTransfer = $this->findOrderByReference($orderReference);

        if ($orderTransfer === null) {
            return null;
        }

        return $this->mapOrderTransferToResource(
            $orderTransfer,
            $this->getAvailableTransitionsByOrderItemId([$orderTransfer]),
            true,
        );
    }

    protected function replaceItemsWithExpandedItems(OrderTransfer $orderTransfer, string $orderReference): void
    {
        $itemCollectionTransfer = $this->salesFacade->getOrderItems(
            (new OrderItemFilterTransfer())->addOrderReference($orderReference),
        );

        $expandedItemTransfersByIdSalesOrderItem = [];

        foreach ($itemCollectionTransfer->getItems() as $itemTransfer) {
            $expandedItemTransfersByIdSalesOrderItem[$itemTransfer->getIdSalesOrderItem()] = $itemTransfer;
        }

        if ($expandedItemTransfersByIdSalesOrderItem === []) {
            return;
        }

        $itemTransfers = new ArrayObject();

        foreach ($orderTransfer->getItems() as $itemTransfer) {
            $expandedItemTransfer = $expandedItemTransfersByIdSalesOrderItem[$itemTransfer->getIdSalesOrderItem()] ?? null;

            if ($expandedItemTransfer === null) {
                $itemTransfers->append($itemTransfer);

                continue;
            }

            $itemTransfers->append(
                $expandedItemTransfer->setCalculatedDiscounts($itemTransfer->getCalculatedDiscounts()),
            );
        }

        $orderTransfer->setItems($itemTransfers);
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\OrderTransfer> $orderTransfers
     *
     * @return array<int, array<int, string>> Keyed by idSalesOrderItem.
     */
    public function getAvailableTransitionsByOrderItemId(array $orderTransfers): array
    {
        $itemCollectionTransfer = new ItemCollectionTransfer();

        foreach ($orderTransfers as $orderTransfer) {
            foreach ($orderTransfer->getItems() as $itemTransfer) {
                $itemCollectionTransfer->addItem($itemTransfer);
            }
        }

        if ($itemCollectionTransfer->getItems()->count() === 0) {
            return [];
        }

        return $this->orderExperienceManagementFacade->getAvailableOrderItemTransitions($itemCollectionTransfer);
    }

    /**
     * @param array<int, array<string>> $availableTransitionsByOrderItemId
     */
    public function mapOrderTransferToResource(
        OrderTransfer $orderTransfer,
        array $availableTransitionsByOrderItemId,
        bool $isItemRoute,
    ): OrdersBackendResource {
        $resource = $this->orderResourceMapper->mapOrderTransferToResource(
            $orderTransfer,
            $availableTransitionsByOrderItemId,
            $isItemRoute,
        );
        $resource->comments = $isItemRoute ? $this->mapComments($orderTransfer) : null;

        return $this->expandResource($resource, $orderTransfer);
    }

    public function expandResource(
        OrdersBackendResource $ordersBackendResource,
        OrderTransfer $orderTransfer,
    ): OrdersBackendResource {
        foreach ($this->orderResourceExpanderPlugins as $orderResourceExpanderPlugin) {
            $ordersBackendResource = $orderResourceExpanderPlugin->expand($ordersBackendResource, $orderTransfer);
        }

        return $ordersBackendResource;
    }

    /**
     * @return array<int, \Generated\Api\Backend\OrdersComment>
     */
    protected function mapComments(OrderTransfer $orderTransfer): array
    {
        $idSalesOrder = $orderTransfer->getIdSalesOrder();

        if ($idSalesOrder === null) {
            return [];
        }

        $comments = [];

        foreach ($this->orderCommentReader->getCommentsByIdSalesOrder($idSalesOrder) as $commentTransfer) {
            $comments[] = (new OrdersComment())
                ->setMessage($commentTransfer->getMessage())
                ->setUsername($commentTransfer->getUsername())
                ->setCreatedAt($commentTransfer->getCreatedAt())
                ->setUpdatedAt($commentTransfer->getUpdatedAt());
        }

        return $comments;
    }
}
