<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\OrderIntakeItemTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Spryker\Zed\Shipment\Business\ShipmentFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeRequestValidator;

/**
 * Resolves the delivery of every line: the order-level shipment method, address and requested date
 * apply to each item unless the item overrides them.
 *
 * Methods are looked up once per distinct name, not once per item, and shipment expenses are left to
 * `ShipmentFacade::expandQuoteWithShipmentGroups()` rather than recomputed here.
 */
class OrderIntakeShipmentExpander implements OrderIntakeShipmentExpanderInterface
{
    protected const string MESSAGE_SHIPMENT_METHOD_REQUIRED = 'A shipment method name is required to place an order.';

    protected const string MESSAGE_SHIPMENT_METHOD_NOT_FOUND = 'Shipment method "%shipmentMethodName%" was not found.';

    protected const string MESSAGE_SHIPMENT_METHOD_INACTIVE = 'Shipment method "%shipmentMethodName%" is not active.';

    public function __construct(
        protected readonly ShipmentFacadeInterface $shipmentFacade,
    ) {
    }

    public function expandShipment(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        QuoteTransfer $quoteTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): QuoteTransfer {
        $orderIntakeItemTransfers = array_values(iterator_to_array($orderIntakeRequestTransfer->getItems()));
        $shipmentMethodTransfersByName = $this->resolveShipmentMethods(
            $orderIntakeRequestTransfer,
            $orderIntakeItemTransfers,
            $orderIntakeResponseTransfer,
        );

        if ($shipmentMethodTransfersByName === []) {
            return $quoteTransfer;
        }

        foreach ($quoteTransfer->getItems() as $index => $itemTransfer) {
            $orderIntakeItemTransfer = $orderIntakeItemTransfers[$index] ?? null;
            $shipmentMethodName = $this->resolveShipmentMethodName($orderIntakeRequestTransfer, $orderIntakeItemTransfer);

            if (!isset($shipmentMethodTransfersByName[$shipmentMethodName])) {
                continue;
            }

            $itemTransfer->setShipment($this->buildItemShipment(
                $orderIntakeRequestTransfer,
                $orderIntakeItemTransfer,
                $quoteTransfer,
                $itemTransfer->getShipment(),
                $shipmentMethodTransfersByName[$shipmentMethodName],
            ));
        }

        if ($orderIntakeResponseTransfer->getValidationIssues()->count() > 0) {
            return $quoteTransfer;
        }

        return $this->shipmentFacade->expandQuoteWithShipmentGroups($quoteTransfer);
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\OrderIntakeItemTransfer> $orderIntakeItemTransfers
     *
     * @return array<string, \Generated\Shared\Transfer\ShipmentMethodTransfer>
     */
    protected function resolveShipmentMethods(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        array $orderIntakeItemTransfers,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): array {
        $fieldsByShipmentMethodName = [];

        foreach ($orderIntakeItemTransfers as $index => $orderIntakeItemTransfer) {
            $shipmentMethodName = $this->resolveShipmentMethodName($orderIntakeRequestTransfer, $orderIntakeItemTransfer);

            if ($shipmentMethodName === null) {
                $orderIntakeResponseTransfer->addValidationIssue(
                    (new OrderIntakeValidationIssueTransfer())
                        ->setField(OrderIntakeRequestValidator::FIELD_SHIPMENT_METHOD)
                        ->setMessage(static::MESSAGE_SHIPMENT_METHOD_REQUIRED),
                );

                return [];
            }

            $fieldsByShipmentMethodName[$shipmentMethodName] ??= $orderIntakeItemTransfer->getShipmentMethodName() !== null
                ? sprintf(OrderIntakeRequestValidator::FIELD_ITEM_SHIPMENT_METHOD_PATTERN, $index)
                : OrderIntakeRequestValidator::FIELD_SHIPMENT_METHOD;
        }

        $shipmentMethodTransfersByName = [];
        $isResolvable = true;

        foreach ($fieldsByShipmentMethodName as $shipmentMethodName => $field) {
            $shipmentMethodTransfer = $this->findActiveShipmentMethod((string)$shipmentMethodName, $field, $orderIntakeResponseTransfer);

            if ($shipmentMethodTransfer === null) {
                $isResolvable = false;

                continue;
            }

            $shipmentMethodTransfersByName[$shipmentMethodName] = $shipmentMethodTransfer;
        }

        return $isResolvable ? $shipmentMethodTransfersByName : [];
    }

    protected function findActiveShipmentMethod(
        string $shipmentMethodName,
        string $field,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): ?ShipmentMethodTransfer {
        $shipmentMethodTransfer = $this->shipmentFacade->findShipmentMethodByName($shipmentMethodName);

        if ($shipmentMethodTransfer === null || $shipmentMethodTransfer->getIdShipmentMethod() === null) {
            return $this->reject($orderIntakeResponseTransfer, $field, static::MESSAGE_SHIPMENT_METHOD_NOT_FOUND, $shipmentMethodName);
        }

        if (!$this->shipmentFacade->isShipmentMethodActive($shipmentMethodTransfer->getIdShipmentMethod())) {
            return $this->reject($orderIntakeResponseTransfer, $field, static::MESSAGE_SHIPMENT_METHOD_INACTIVE, $shipmentMethodName);
        }

        return $shipmentMethodTransfer;
    }

    protected function resolveShipmentMethodName(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        ?OrderIntakeItemTransfer $orderIntakeItemTransfer,
    ): ?string {
        $shipmentMethodName = $orderIntakeItemTransfer?->getShipmentMethodName() ?? $orderIntakeRequestTransfer->getShipmentMethodName();

        return $shipmentMethodName === '' ? null : $shipmentMethodName;
    }

    protected function buildItemShipment(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        ?OrderIntakeItemTransfer $orderIntakeItemTransfer,
        QuoteTransfer $quoteTransfer,
        ?ShipmentTransfer $shipmentTransfer,
        ShipmentMethodTransfer $shipmentMethodTransfer,
    ): ShipmentTransfer {
        $shipmentTransfer ??= new ShipmentTransfer();

        return $shipmentTransfer
            ->setMethod($shipmentMethodTransfer)
            ->setShipmentSelection((string)$shipmentMethodTransfer->getIdShipmentMethod())
            ->setShippingAddress($this->resolveShippingAddress($orderIntakeItemTransfer, $quoteTransfer))
            ->setRequestedDeliveryDate(
                $orderIntakeItemTransfer?->getRequestedDeliveryDate() ?? $orderIntakeRequestTransfer->getRequestedDeliveryDate(),
            );
    }

    protected function resolveShippingAddress(
        ?OrderIntakeItemTransfer $orderIntakeItemTransfer,
        QuoteTransfer $quoteTransfer,
    ): ?AddressTransfer {
        $addressTransfer = $orderIntakeItemTransfer?->getShippingAddress();

        if ($addressTransfer === null) {
            return $quoteTransfer->getShippingAddress();
        }

        return $addressTransfer->setIsAddressSavingSkipped(true);
    }

    protected function reject(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        string $field,
        string $message,
        string $shipmentMethodName,
    ): ?ShipmentMethodTransfer {
        $orderIntakeResponseTransfer->addValidationIssue(
            (new OrderIntakeValidationIssueTransfer())
                ->setField($field)
                ->setMessage($message)
                ->setParameters(['%shipmentMethodName%' => $shipmentMethodName]),
        );

        return null;
    }
}
