<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper;

use Generated\Api\Backend\OrderTransitions\OrderTransitionsMessagesBackendObject;
use Generated\Api\Backend\OrderTransitionsBackendResource;
use Generated\Api\Backend\OrderTransitionsItem;
use Generated\Shared\Transfer\OrderItemTransitionRequestTransfer;
use Generated\Shared\Transfer\OrderItemTransitionResponseTransfer;

class OrderTransitionsResourceMapper implements OrderTransitionsResourceMapperInterface
{
    public function mapOrderTransitionsBackendResourceToOrderItemTransitionRequestTransfer(
        ?OrderTransitionsBackendResource $resource,
        ?string $orderReference,
    ): OrderItemTransitionRequestTransfer {
        return (new OrderItemTransitionRequestTransfer())
            ->setOrderReference($orderReference)
            ->setEvent($resource?->event)
            ->setItemUuids($resource !== null ? $resource->itemUuids : []);
    }

    public function mapOrderItemTransitionResponseTransferToOrderTransitionsBackendResource(
        OrderItemTransitionResponseTransfer $orderItemTransitionResponseTransfer,
    ): OrderTransitionsBackendResource {
        $resource = new OrderTransitionsBackendResource();

        $resource->orderReference = $orderItemTransitionResponseTransfer->getOrderReference();
        $resource->event = $orderItemTransitionResponseTransfer->getEvent();
        $resource->items = $this->mapItemOutcomes($orderItemTransitionResponseTransfer);
        $resource->messages = $this->mapMessages($orderItemTransitionResponseTransfer);

        return $resource;
    }

    public function mapOrderItemTransitionResponseTransferToErrorMessage(
        OrderItemTransitionResponseTransfer $orderItemTransitionResponseTransfer,
    ): ?string {
        $messages = [];

        foreach ($orderItemTransitionResponseTransfer->getErrors() as $errorTransfer) {
            if ($errorTransfer->getMessage() !== null) {
                $messages[] = $errorTransfer->getMessage();
            }
        }

        $unknownUuids = $orderItemTransitionResponseTransfer->getUnknownItemUuids();

        if ($unknownUuids !== []) {
            $messages[] = sprintf('Not items of this order: %s.', implode(', ', $unknownUuids));
        }

        $itemStateMessage = $this->buildItemStateMessage($orderItemTransitionResponseTransfer);

        if ($itemStateMessage !== null) {
            $messages[] = $itemStateMessage;
        }

        foreach ($orderItemTransitionResponseTransfer->getMessages() as $messageTransfer) {
            if ($messageTransfer->getValue() !== null) {
                $messages[] = $messageTransfer->getValue();
            }
        }

        if ($messages === []) {
            return null;
        }

        return implode(' ', $messages);
    }

    /**
     * @phpstan-return array<int, \Generated\Api\Backend\OrderTransitionsItem>
     */
    protected function mapItemOutcomes(
        OrderItemTransitionResponseTransfer $orderItemTransitionResponseTransfer,
    ): array {
        $items = [];

        foreach ($orderItemTransitionResponseTransfer->getItemOutcomes() as $outcomeTransfer) {
            $items[] = (new OrderTransitionsItem())
                ->setUuid($outcomeTransfer->getUuid())
                ->setOutcome($outcomeTransfer->getOutcome())
                ->setStateBefore($outcomeTransfer->getStateBefore())
                ->setState($outcomeTransfer->getState());
        }

        return $items;
    }

    /**
     * @return array<int, \Generated\Api\Backend\OrderTransitions\OrderTransitionsMessagesBackendObject>
     */
    protected function mapMessages(
        OrderItemTransitionResponseTransfer $orderItemTransitionResponseTransfer,
    ): array {
        $messages = [];

        foreach ($orderItemTransitionResponseTransfer->getMessages() as $messageTransfer) {
            $messages[] = (new OrderTransitionsMessagesBackendObject())
                ->setType($messageTransfer->getType())
                ->setValue($messageTransfer->getValue());
        }

        return $messages;
    }

    protected function buildItemStateMessage(
        OrderItemTransitionResponseTransfer $orderItemTransitionResponseTransfer,
    ): ?string {
        $itemDescriptions = [];

        foreach ($orderItemTransitionResponseTransfer->getItemOutcomes() as $outcomeTransfer) {
            $itemDescriptions[] = sprintf('%s (currently %s)', $outcomeTransfer->getUuid(), $outcomeTransfer->getState());
        }

        if ($itemDescriptions === []) {
            return null;
        }

        return sprintf('Item states: %s.', implode(', ', $itemDescriptions));
    }
}
