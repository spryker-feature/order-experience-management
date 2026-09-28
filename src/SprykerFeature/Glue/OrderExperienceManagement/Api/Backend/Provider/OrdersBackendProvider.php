<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use Generated\Shared\Transfer\OrderConditionsTransfer;
use Generated\Shared\Transfer\OrderCriteriaTransfer;
use Generated\Shared\Transfer\SortTransfer;
use Spryker\ApiPlatform\Exception\GlueApiException;
use Spryker\ApiPlatform\State\Provider\AbstractBackendProvider;
use Spryker\Zed\Sales\Business\SalesFacadeInterface;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Exception\OrdersBackendExceptionFactoryInterface;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Reader\OrderResourceReaderInterface;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Validator\OrdersPayloadShapeValidatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backend API read provider for sales orders.
 */
class OrdersBackendProvider extends AbstractBackendProvider
{
    protected const string URI_VARIABLE_ORDER_REFERENCE = 'orderReference';

    protected const string QUERY_PARAM_FILTER = 'filter';

    protected const string QUERY_PARAM_SORT = 'sort';

    protected const string FILTER_KEY_PREFIX = 'orders.';

    protected const string FILTER_ORDER_REFERENCE = 'orderReference';

    protected const string FILTER_CUSTOMER_REFERENCE = 'customerReference';

    protected const string FILTER_STORE_NAME = 'storeName';

    protected const string FILTER_ITEM_STATE = 'itemState';

    protected const string FILTER_CREATED_AT_FROM = 'createdAtFrom';

    protected const string FILTER_CREATED_AT_TO = 'createdAtTo';

    protected const array FILTERABLE_FIELDS = [
        self::FILTER_ORDER_REFERENCE,
        self::FILTER_CUSTOMER_REFERENCE,
        self::FILTER_STORE_NAME,
        self::FILTER_ITEM_STATE,
        self::FILTER_CREATED_AT_FROM,
        self::FILTER_CREATED_AT_TO,
    ];

    protected const string SORT_DESC_PREFIX = '-';

    protected const string ERROR_DETAIL_INVALID_DATE_FILTER = 'Filter "%s" must be a valid date/time value.';

    protected const string ERROR_DETAIL_MALFORMED_FILTER_PARAM = 'Query parameter "filter" must be a set of filter[%s<field>] keys.';

    protected const string ERROR_DETAIL_MALFORMED_FILTER_KEY = 'Filter key "%s" must be in filter[%s<field>] form.';

    protected const string ERROR_DETAIL_UNSUPPORTED_FILTER_FIELD = 'Unsupported filter field "%s". Supported fields: %s.';

    protected const string ERROR_DETAIL_NON_SCALAR_FILTER_VALUE = 'Filter "%s" must be a scalar value.';

    /**
     * @phpstan-var non-empty-string
     */
    protected const string MULTI_VALUE_DELIMITER = ',';

    protected const array SORTABLE_FIELDS = [
        'createdAt' => 'created_at',
    ];

    public function __construct(
        protected readonly SalesFacadeInterface $salesFacade,
        protected readonly OrderResourceReaderInterface $orderResourceReader,
        protected readonly OrdersPayloadShapeValidatorInterface $ordersPayloadShapeValidator,
        protected readonly OrdersBackendExceptionFactoryInterface $ordersBackendExceptionFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if ($operation instanceof Post) {
            $this->assertPayloadShape($context['request'] ?? null);
        }

        return parent::provide($operation, $uriVariables, $context);
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     */
    protected function assertPayloadShape(mixed $request): void
    {
        if (!$request instanceof Request) {
            return;
        }

        $issues = $this->ordersPayloadShapeValidator->validatePayloadShape($request);

        if ($issues === []) {
            return;
        }

        throw $this->ordersBackendExceptionFactory->createValidationException($issues);
    }

    protected function provideItem(): ?object
    {
        $orderReference = $this->findUriVariable(static::URI_VARIABLE_ORDER_REFERENCE);

        if (!is_string($orderReference) || $orderReference === '') {
            return null;
        }

        return $this->orderResourceReader->readOrderByReference($orderReference);
    }

    /**
     * @return array<\Generated\Api\Backend\OrdersBackendResource>
     */
    protected function provideCollection(): array
    {
        $paginationTransfer = $this->buildPaginationTransfer();

        $orderCriteriaTransfer = (new OrderCriteriaTransfer())
            ->setPagination($paginationTransfer)
            ->setOrderConditions($this->buildConditionsFromRequest());

        foreach ($this->buildSortCollectionFromRequest() as $sortTransfer) {
            $orderCriteriaTransfer->addSort($sortTransfer);
        }

        $orderCollectionTransfer = $this->salesFacade->getOrderCollection($orderCriteriaTransfer);

        if ($paginationTransfer->getNbResults() !== null) {
            $this->setCollectionPagination(
                $paginationTransfer->getOffsetOrFail(),
                $paginationTransfer->getLimitOrFail(),
                $paginationTransfer->getNbResultsOrFail(),
            );
        }

        $orderTransfers = iterator_to_array($orderCollectionTransfer->getOrders());
        $availableTransitionsByOrderItemId = $this->orderResourceReader->getAvailableTransitionsByOrderItemId($orderTransfers);

        $resources = [];

        foreach ($orderTransfers as $orderTransfer) {
            $resources[] = $this->orderResourceReader->mapOrderTransferToResource($orderTransfer, $availableTransitionsByOrderItemId, false);
        }

        return $resources;
    }

    protected function buildConditionsFromRequest(): OrderConditionsTransfer
    {
        $orderConditionsTransfer = (new OrderConditionsTransfer())->setWithOrderExpanderPlugins(true);

        if (!$this->hasRequest()) {
            return $orderConditionsTransfer;
        }

        $filters = $this->extractFilters();

        foreach ($this->extractMultiValueParam($filters[static::FILTER_ORDER_REFERENCE] ?? '') as $orderReference) {
            $orderConditionsTransfer->addOrderReference($orderReference);
        }

        foreach ($this->extractMultiValueParam($filters[static::FILTER_CUSTOMER_REFERENCE] ?? '') as $customerReference) {
            $orderConditionsTransfer->addCustomerReference($customerReference);
        }

        $storeName = $filters[static::FILTER_STORE_NAME] ?? '';

        if ($storeName !== '') {
            $orderConditionsTransfer->addStoreName($storeName);
        }

        $createdAtFrom = $filters[static::FILTER_CREATED_AT_FROM] ?? '';

        if ($createdAtFrom !== '') {
            $orderConditionsTransfer->setCreatedAtFrom(
                $this->validateDateFilter($createdAtFrom, static::FILTER_CREATED_AT_FROM),
            );
        }

        $createdAtTo = $filters[static::FILTER_CREATED_AT_TO] ?? '';

        if ($createdAtTo !== '') {
            $orderConditionsTransfer->setCreatedAtTo(
                $this->validateDateFilter($createdAtTo, static::FILTER_CREATED_AT_TO),
            );
        }

        foreach ($this->extractMultiValueParam($filters[static::FILTER_ITEM_STATE] ?? '') as $itemState) {
            $orderConditionsTransfer->addItemState($itemState);
        }

        return $orderConditionsTransfer;
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     *
     * @return array<string, string>
     */
    protected function extractFilters(): array
    {
        $rawFilters = $this->getRequest()->query->all()[static::QUERY_PARAM_FILTER] ?? [];

        if (!is_array($rawFilters)) {
            throw new GlueApiException(
                Response::HTTP_BAD_REQUEST,
                null,
                sprintf(static::ERROR_DETAIL_MALFORMED_FILTER_PARAM, static::FILTER_KEY_PREFIX),
            );
        }

        $filters = [];

        foreach ($rawFilters as $key => $value) {
            $field = $this->resolveFilterField((string)$key);

            if (!is_scalar($value)) {
                throw new GlueApiException(
                    Response::HTTP_BAD_REQUEST,
                    null,
                    sprintf(static::ERROR_DETAIL_NON_SCALAR_FILTER_VALUE, $field),
                );
            }

            $filters[$field] = (string)$value;
        }

        return $filters;
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     */
    protected function resolveFilterField(string $key): string
    {
        if (!str_starts_with($key, static::FILTER_KEY_PREFIX)) {
            throw new GlueApiException(
                Response::HTTP_BAD_REQUEST,
                null,
                sprintf(static::ERROR_DETAIL_MALFORMED_FILTER_KEY, $key, static::FILTER_KEY_PREFIX),
            );
        }

        $field = substr($key, strlen(static::FILTER_KEY_PREFIX));

        if (!in_array($field, static::FILTERABLE_FIELDS, true)) {
            throw new GlueApiException(
                Response::HTTP_BAD_REQUEST,
                null,
                sprintf(
                    static::ERROR_DETAIL_UNSUPPORTED_FILTER_FIELD,
                    $field,
                    implode(', ', static::FILTERABLE_FIELDS),
                ),
            );
        }

        return $field;
    }

    /**
     * @throws \Spryker\ApiPlatform\Exception\GlueApiException
     */
    protected function validateDateFilter(string $value, string $filterField): string
    {
        if (date_create_immutable($value) === false) {
            throw new GlueApiException(
                Response::HTTP_BAD_REQUEST,
                null,
                sprintf(static::ERROR_DETAIL_INVALID_DATE_FILTER, $filterField),
            );
        }

        return $value;
    }

    /**
     * @return array<int, string>
     */
    protected function extractMultiValueParam(string $rawValue): array
    {
        if ($rawValue === '') {
            return [];
        }

        $values = array_map('trim', explode(static::MULTI_VALUE_DELIMITER, $rawValue));

        return array_values(array_filter($values, static fn (string $value): bool => $value !== ''));
    }

    /**
     * Accepts a comma-separated `sort` parameter using the JSON:API convention, where a leading `-`
     * means descending: `?sort=-createdAt,orderReference`. Unknown fields are ignored.
     *
     * @return array<int, \Generated\Shared\Transfer\SortTransfer>
     */
    protected function buildSortCollectionFromRequest(): array
    {
        if (!$this->hasRequest()) {
            return [];
        }

        $sortParameter = $this->getRequest()->query->getString(static::QUERY_PARAM_SORT);

        if ($sortParameter === '') {
            return [];
        }

        $sortTransfers = [];

        foreach (explode(',', $sortParameter) as $sortExpression) {
            $sortExpression = trim($sortExpression);
            $isAscending = !str_starts_with($sortExpression, static::SORT_DESC_PREFIX);
            $fieldName = ltrim($sortExpression, static::SORT_DESC_PREFIX);

            if (!isset(static::SORTABLE_FIELDS[$fieldName])) {
                continue;
            }

            $sortTransfers[] = (new SortTransfer())
                ->setField(static::SORTABLE_FIELDS[$fieldName])
                ->setIsAscending($isAscending);
        }

        return $sortTransfers;
    }
}
