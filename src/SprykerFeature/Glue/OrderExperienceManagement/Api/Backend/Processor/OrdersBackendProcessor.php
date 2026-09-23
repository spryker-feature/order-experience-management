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
    /**
     * @var list<string>
     */
    protected const array OBJECT_PATHS = ['billingAddress', 'customer', 'shipment', 'shipment.shippingAddress'];

    /**
     * @var list<string>
     */
    protected const array ITEM_OBJECT_PATHS = ['shipment', 'shipment.shippingAddress'];

    protected const string COLLECTION_PATH = 'items';

    protected const string ITEM_QUANTITY_FIELD = 'quantity';

    protected const string GLOSSARY_KEY_MUST_BE_OBJECT = '"%s" must be an object.';

    protected const string GLOSSARY_KEY_MUST_BE_COLLECTION = '"%s" must be an array of objects.';

    protected const string GLOSSARY_KEY_QUANTITY_MUST_BE_AN_INTEGER = '"%s" must be an integer.';

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
        $this->assertPayloadShape();

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

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     */
    protected function assertPayloadShape(): void
    {
        $attributes = $this->findRequestAttributes();

        if ($attributes === null) {
            return;
        }

        $issues = [];

        foreach (static::OBJECT_PATHS as $path) {
            $issues = array_merge($issues, $this->collectObjectIssues($attributes, $path, $path));
        }

        $issues = array_merge($issues, $this->collectCollectionIssues($attributes));

        if ($issues === []) {
            return;
        }

        throw $this->ordersBackendExceptionFactory->createValidationException($issues);
    }

    protected function findRequestAttributes(): ?object
    {
        if (!$this->hasRequest()) {
            return null;
        }

        $request = $this->getRequest();
        $content = $request->getContent();

        if (trim($content) === '' || !str_contains((string)$request->headers->get('Content-Type'), 'json')) {
            return null;
        }

        $decoded = json_decode($content);

        if (!is_object($decoded)) {
            return null;
        }

        // JSON:API wraps the fields in `data.attributes`; the plain JSON body carries them at the root.
        $attributes = $decoded->data->attributes ?? $decoded;

        return is_object($attributes) ? $attributes : null;
    }

    /**
     * @return list<string>
     */
    protected function collectObjectIssues(object $attributes, string $path, string $reportedPath): array
    {
        $value = $this->findByPath($attributes, $path);

        if ($value === null || $this->isJsonObject($value)) {
            return [];
        }

        return [sprintf(static::GLOSSARY_KEY_MUST_BE_OBJECT, $reportedPath)];
    }

    /**
     * @return list<string>
     */
    protected function collectCollectionIssues(object $attributes): array
    {
        $items = $attributes->{static::COLLECTION_PATH} ?? null;

        if ($items === null) {
            return [];
        }

        if (!is_array($items) || !array_is_list($items)) {
            return [sprintf(static::GLOSSARY_KEY_MUST_BE_COLLECTION, static::COLLECTION_PATH)];
        }

        $issues = [];

        foreach ($items as $index => $item) {
            $itemPath = sprintf('%s[%d]', static::COLLECTION_PATH, $index);

            if (!$this->isJsonObject($item)) {
                $issues[] = sprintf(static::GLOSSARY_KEY_MUST_BE_OBJECT, $itemPath);

                continue;
            }

            if (!is_object($item)) {
                continue;
            }

            foreach (static::ITEM_OBJECT_PATHS as $path) {
                $issues = array_merge(
                    $issues,
                    $this->collectObjectIssues($item, $path, sprintf('%s.%s', $itemPath, $path)),
                );
            }

            $issues = array_merge($issues, $this->collectItemQuantityIssues($item, $itemPath));
        }

        return $issues;
    }

    /**
     * @return list<string>
     */
    protected function collectItemQuantityIssues(object $item, string $itemPath): array
    {
        $quantity = $item->{static::ITEM_QUANTITY_FIELD} ?? null;

        if ($quantity === null || is_int($quantity)) {
            return [];
        }

        return [sprintf(static::GLOSSARY_KEY_QUANTITY_MUST_BE_AN_INTEGER, sprintf('%s.%s', $itemPath, static::ITEM_QUANTITY_FIELD))];
    }

    protected function isJsonObject(mixed $value): bool
    {
        return is_object($value) || $value === [];
    }

    protected function findByPath(object $attributes, string $path): mixed
    {
        $value = $attributes;

        foreach (explode('.', $path) as $segment) {
            if (!is_object($value) || !property_exists($value, $segment)) {
                return null;
            }

            $value = $value->{$segment};
        }

        return $value;
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
