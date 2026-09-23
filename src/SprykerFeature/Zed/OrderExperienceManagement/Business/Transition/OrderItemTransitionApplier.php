<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Transition;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\OmsEventTriggerResponseTransfer;
use Generated\Shared\Transfer\OrderConditionsTransfer;
use Generated\Shared\Transfer\OrderCriteriaTransfer;
use Generated\Shared\Transfer\OrderItemFilterTransfer;
use Generated\Shared\Transfer\OrderItemTransitionRequestTransfer;
use Generated\Shared\Transfer\OrderItemTransitionResponseTransfer;
use Spryker\Zed\Oms\Business\Exception\LockException;
use Spryker\Zed\Oms\Business\OmsFacadeInterface;
use Spryker\Zed\Oms\OmsConfig;
use Spryker\Zed\Sales\Business\SalesFacadeInterface;

/**
 * Fires one OMS event over a set of a placed order's line items.
 */
class OrderItemTransitionApplier implements OrderItemTransitionApplierInterface
{
    public const string RESULT_APPLIED = 'applied';

    public const string RESULT_PARTIALLY_APPLIED = 'partiallyApplied';

    public const string RESULT_NOT_APPLIED = 'notApplied';

    public const string RESULT_UNKNOWN_ITEMS = 'unknownItems';

    public const string RESULT_INELIGIBLE = 'ineligible';

    public const string RESULT_NO_ELIGIBLE_ITEMS = 'noEligibleItems';

    public const string RESULT_LOCKED = 'locked';

    public const string RESULT_INTERNAL_FAILURE = 'internalFailure';

    public const string RESULT_ORDER_NOT_FOUND = 'orderNotFound';

    public const string RESULT_INVALID_REQUEST = 'invalidRequest';

    protected const string ERROR_ORDER_NOT_FOUND = 'No order exists with the given order reference.';

    protected const string ERROR_UNKNOWN_ITEMS = 'One or more of the given order item uuids is not an item of this order.';

    protected const string ERROR_INELIGIBLE = 'The event is not currently available for every given order item. Read items[].availableEvents from GET /orders/{orderReference} for what each one currently accepts.';

    protected const string ERROR_NO_ELIGIBLE_ITEMS = 'The event is not currently available for any item of this order.';

    protected const string ERROR_LOCKED = 'A concurrent state change is running for one of these items. Retry.';

    protected const string ERROR_INTERNAL_FAILURE = 'The order management system reported an internal failure; the resulting item states are indeterminate.';

    protected const string ERROR_NOT_APPLIED = 'The event was fired but advanced no item.';

    protected const string ERROR_ORDER_REFERENCE_REQUIRED = 'An order reference is required.';

    protected const string ERROR_EVENT_REQUIRED = 'An event name is required.';

    public function __construct(
        protected readonly SalesFacadeInterface $salesFacade,
        protected readonly OmsFacadeInterface $omsFacade,
        protected readonly AvailableOrderItemTransitionReaderInterface $availableOrderItemTransitionReader,
        protected readonly OrderItemTransitionOutcomeMapperInterface $orderItemTransitionOutcomeMapper,
    ) {
    }

    public function applyTransition(
        OrderItemTransitionRequestTransfer $orderItemTransitionRequestTransfer
    ): OrderItemTransitionResponseTransfer {
        $responseTransfer = $this->createResponse($orderItemTransitionRequestTransfer);

        $orderReference = $orderItemTransitionRequestTransfer->getOrderReference();
        $event = $orderItemTransitionRequestTransfer->getEvent();

        if ($orderReference === null || $orderReference === '') {
            return $this->reject($responseTransfer, static::RESULT_INVALID_REQUEST, static::ERROR_ORDER_REFERENCE_REQUIRED);
        }

        if ($event === null || $event === '') {
            return $this->reject($responseTransfer, static::RESULT_INVALID_REQUEST, static::ERROR_EVENT_REQUIRED);
        }

        if (!$this->hasOrder($orderReference)) {
            return $this->reject($responseTransfer, static::RESULT_ORDER_NOT_FOUND, static::ERROR_ORDER_NOT_FOUND);
        }

        $requestedUuids = array_values(array_unique($orderItemTransitionRequestTransfer->getItemUuids()));
        $itemTransfers = $this->readItems($orderReference, $requestedUuids);
        $unknownUuids = $this->orderItemTransitionOutcomeMapper->findUnknownUuids($requestedUuids, $itemTransfers);

        if ($unknownUuids !== []) {
            $responseTransfer->setUnknownItemUuids($unknownUuids);

            return $this->reject($responseTransfer, static::RESULT_UNKNOWN_ITEMS, static::ERROR_UNKNOWN_ITEMS);
        }

        return $this->applyToItems($responseTransfer, $orderReference, $event, $requestedUuids, $itemTransfers);
    }

    /**
     * @param array<int, string> $requestedUuids Empty when the caller omitted the list (whole order).
     * @param array<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     */
    protected function applyToItems(
        OrderItemTransitionResponseTransfer $responseTransfer,
        string $orderReference,
        string $event,
        array $requestedUuids,
        array $itemTransfers
    ): OrderItemTransitionResponseTransfer {
        [$eligibleItemTransfers, $ineligibleItemTransfers] = $this->partitionByEligibility($itemTransfers, $event);

        if ($requestedUuids !== [] && $ineligibleItemTransfers !== []) {
            $responseTransfer->setItemOutcomes($this->orderItemTransitionOutcomeMapper->createOutcomes($itemTransfers, [], []));

            return $this->reject($responseTransfer, static::RESULT_INELIGIBLE, static::ERROR_INELIGIBLE);
        }

        if ($eligibleItemTransfers === []) {
            $responseTransfer->setItemOutcomes($this->orderItemTransitionOutcomeMapper->createOutcomes($itemTransfers, [], []));

            return $this->reject($responseTransfer, static::RESULT_NO_ELIGIBLE_ITEMS, static::ERROR_NO_ELIGIBLE_ITEMS);
        }

        $stateNamesBeforeByOrderItemId = $this->orderItemTransitionOutcomeMapper->extractStateNamesByOrderItemId($eligibleItemTransfers);

        try {
            $triggerReturnData = $this->omsFacade->triggerEventForOrderItems(
                $event,
                array_keys($stateNamesBeforeByOrderItemId),
            );
        } catch (LockException $lockException) {
            return $this->reject($responseTransfer, static::RESULT_LOCKED, static::ERROR_LOCKED);
        }

        if ($triggerReturnData === null) {
            return $this->reject($responseTransfer, static::RESULT_INTERNAL_FAILURE, static::ERROR_INTERNAL_FAILURE);
        }

        $this->copyOmsMessages($responseTransfer, $triggerReturnData);

        return $this->reportOutcomes($responseTransfer, $orderReference, $itemTransfers, $stateNamesBeforeByOrderItemId);
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     * @param array<int, string> $stateNamesBeforeByOrderItemId
     */
    protected function reportOutcomes(
        OrderItemTransitionResponseTransfer $responseTransfer,
        string $orderReference,
        array $itemTransfers,
        array $stateNamesBeforeByOrderItemId
    ): OrderItemTransitionResponseTransfer {
        $reReadItemTransfers = $this->readItems(
            $orderReference,
            $this->orderItemTransitionOutcomeMapper->extractUuids($itemTransfers),
        );

        $outcomeTransfers = $this->orderItemTransitionOutcomeMapper->createOutcomes(
            $reReadItemTransfers === [] ? $itemTransfers : $reReadItemTransfers,
            $stateNamesBeforeByOrderItemId,
            $stateNamesBeforeByOrderItemId,
        );

        $responseTransfer->setItemOutcomes($outcomeTransfers);

        $transitionedCount = 0;

        foreach ($outcomeTransfers as $outcomeTransfer) {
            if ($outcomeTransfer->getOutcome() === OrderItemTransitionOutcomeMapper::OUTCOME_TRANSITIONED) {
                $transitionedCount++;
            }
        }

        if ($transitionedCount === 0) {
            return $this->reject($responseTransfer, static::RESULT_NOT_APPLIED, static::ERROR_NOT_APPLIED);
        }

        return $responseTransfer
            ->setIsSuccessful(true)
            ->setResult(
                $transitionedCount === count($outcomeTransfers)
                    ? static::RESULT_APPLIED
                    : static::RESULT_PARTIALLY_APPLIED,
            );
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array{0: array<int, \Generated\Shared\Transfer\ItemTransfer>, 1: array<int, \Generated\Shared\Transfer\ItemTransfer>}
     */
    protected function partitionByEligibility(array $itemTransfers, string $event): array
    {
        $eligibleItemTransfers = [];
        $ineligibleItemTransfers = [];

        foreach ($itemTransfers as $itemTransfer) {
            $availableTransitions = $this->availableOrderItemTransitionReader->getAvailableTransitions($itemTransfer);

            if (in_array($event, $availableTransitions, true)) {
                $eligibleItemTransfers[] = $itemTransfer;

                continue;
            }

            $ineligibleItemTransfers[] = $itemTransfer;
        }

        return [$eligibleItemTransfers, $ineligibleItemTransfers];
    }

    protected function hasOrder(string $orderReference): bool
    {
        $orderCriteriaTransfer = (new OrderCriteriaTransfer())
            ->setOrderConditions((new OrderConditionsTransfer())->addOrderReference($orderReference));

        return $this->salesFacade->getOrderCollection($orderCriteriaTransfer)->getOrders()->count() > 0;
    }

    /**
     * @param array<int, string> $requestedUuids Empty targets every item of the order.
     *
     * @return array<int, \Generated\Shared\Transfer\ItemTransfer>
     */
    protected function readItems(string $orderReference, array $requestedUuids): array
    {
        $orderItemFilterTransfer = (new OrderItemFilterTransfer())->addOrderReference($orderReference);

        if ($requestedUuids !== []) {
            $orderItemFilterTransfer->setSalesOrderItemUuids($requestedUuids);
        }

        return iterator_to_array($this->salesFacade->getOrderItems($orderItemFilterTransfer)->getItems());
    }

    /**
     * @param array<mixed> $triggerReturnData
     */
    protected function copyOmsMessages(
        OrderItemTransitionResponseTransfer $responseTransfer,
        array $triggerReturnData
    ): void {
        $omsEventTriggerResponseTransfer = $triggerReturnData[OmsConfig::OMS_EVENT_TRIGGER_RESPONSE] ?? null;

        if (!$omsEventTriggerResponseTransfer instanceof OmsEventTriggerResponseTransfer) {
            return;
        }

        $responseTransfer->setMessages($omsEventTriggerResponseTransfer->getMessages());
    }

    protected function createResponse(
        OrderItemTransitionRequestTransfer $orderItemTransitionRequestTransfer
    ): OrderItemTransitionResponseTransfer {
        return (new OrderItemTransitionResponseTransfer())
            ->setIsSuccessful(false)
            ->setOrderReference($orderItemTransitionRequestTransfer->getOrderReference())
            ->setEvent($orderItemTransitionRequestTransfer->getEvent());
    }

    protected function reject(
        OrderItemTransitionResponseTransfer $responseTransfer,
        string $result,
        string $errorMessage
    ): OrderItemTransitionResponseTransfer {
        return $responseTransfer
            ->setIsSuccessful(false)
            ->setResult($result)
            ->addError((new ErrorTransfer())->setMessage($errorMessage));
    }
}
