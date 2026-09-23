<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Processor;

use Generated\Api\Backend\OrderTransitionsBackendResource;
use Generated\Shared\Transfer\OrderItemTransitionResponseTransfer;
use Spryker\ApiPlatform\State\Processor\AbstractBackendProcessor;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper\OrderTransitionsResourceMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Transition\OrderItemTransitionApplier;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * `POST /orders/{orderReference}/transitions` — fires one OMS event over a set of the order's lines.
 */
class OrderTransitionsBackendProcessor extends AbstractBackendProcessor
{
    protected const array STATUS_BY_RESULT = [
        OrderItemTransitionApplier::RESULT_ORDER_NOT_FOUND => Response::HTTP_NOT_FOUND,
        OrderItemTransitionApplier::RESULT_LOCKED => Response::HTTP_CONFLICT,
        OrderItemTransitionApplier::RESULT_INTERNAL_FAILURE => Response::HTTP_INTERNAL_SERVER_ERROR,
        OrderItemTransitionApplier::RESULT_UNKNOWN_ITEMS => Response::HTTP_UNPROCESSABLE_ENTITY,
        OrderItemTransitionApplier::RESULT_INELIGIBLE => Response::HTTP_UNPROCESSABLE_ENTITY,
        OrderItemTransitionApplier::RESULT_NO_ELIGIBLE_ITEMS => Response::HTTP_UNPROCESSABLE_ENTITY,
        OrderItemTransitionApplier::RESULT_NOT_APPLIED => Response::HTTP_UNPROCESSABLE_ENTITY,
        OrderItemTransitionApplier::RESULT_INVALID_REQUEST => Response::HTTP_UNPROCESSABLE_ENTITY,
    ];

    protected const int STATUS_UNMAPPED_RESULT = Response::HTTP_INTERNAL_SERVER_ERROR;

    protected const string ERROR_UNMAPPED_RESULT = 'The transition produced an unrecognised result.';

    protected const string URI_VARIABLE_ORDER_REFERENCE = 'orderReference';

    protected const string ATTRIBUTE_ITEM_UUIDS = 'itemUuids';

    protected const string ERROR_EMPTY_ITEM_UUIDS = 'itemUuids was given as an empty list. Omit the key entirely to target every item of the order.';

    public function __construct(
        protected readonly OrderExperienceManagementFacadeInterface $orderExperienceManagementFacade,
        protected readonly OrderTransitionsResourceMapperInterface $orderTransitionsResourceMapper,
    ) {
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function processPost(mixed $data): OrderTransitionsBackendResource
    {
        $this->assertItemUuidsNotEmptyWhenGiven();

        $orderItemTransitionResponseTransfer = $this->orderExperienceManagementFacade->applyOrderItemTransition(
            $this->orderTransitionsResourceMapper->mapOrderTransitionsBackendResourceToOrderItemTransitionRequestTransfer(
                $data instanceof OrderTransitionsBackendResource ? $data : null,
                $this->findOrderReference(),
            ),
        );

        if ($orderItemTransitionResponseTransfer->getIsSuccessful()) {
            return $this->orderTransitionsResourceMapper->mapOrderItemTransitionResponseTransferToOrderTransitionsBackendResource(
                $orderItemTransitionResponseTransfer,
            );
        }

        throw new HttpException(
            $this->resolveStatus($orderItemTransitionResponseTransfer),
            $this->orderTransitionsResourceMapper->mapOrderItemTransitionResponseTransferToErrorMessage($orderItemTransitionResponseTransfer)
                ?? static::ERROR_UNMAPPED_RESULT,
        );
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function assertItemUuidsNotEmptyWhenGiven(): void
    {
        if (!$this->hasRequest()) {
            return;
        }

        $attributes = $this->decodeRequestAttributes();

        if (!array_key_exists(static::ATTRIBUTE_ITEM_UUIDS, $attributes)) {
            return;
        }

        if ($attributes[static::ATTRIBUTE_ITEM_UUIDS] !== []) {
            return;
        }

        throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, static::ERROR_EMPTY_ITEM_UUIDS);
    }

    /**
     * @return array<string, mixed> The JSON:API `data.attributes` object as submitted, or empty when
     *                              the body is absent or not decodable.
     */
    protected function decodeRequestAttributes(): array
    {
        $decodedContent = json_decode((string)$this->getRequest()->getContent(), true);

        if (!is_array($decodedContent)) {
            return [];
        }

        $attributes = $decodedContent['data']['attributes'] ?? null;

        return is_array($attributes) ? $attributes : [];
    }

    protected function findOrderReference(): ?string
    {
        $orderReference = $this->findUriVariable(static::URI_VARIABLE_ORDER_REFERENCE);

        if (is_string($orderReference) && $orderReference !== '') {
            return $orderReference;
        }

        if (!$this->hasRequest()) {
            return null;
        }

        $orderReference = $this->getRequest()->attributes->get(static::URI_VARIABLE_ORDER_REFERENCE);

        return is_string($orderReference) && $orderReference !== '' ? $orderReference : null;
    }

    protected function resolveStatus(OrderItemTransitionResponseTransfer $orderItemTransitionResponseTransfer): int
    {
        $result = $orderItemTransitionResponseTransfer->getResult();

        return static::STATUS_BY_RESULT[$result] ?? static::STATUS_UNMAPPED_RESULT;
    }
}
