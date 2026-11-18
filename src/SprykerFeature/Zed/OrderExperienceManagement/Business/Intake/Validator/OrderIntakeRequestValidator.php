<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\OrderIntakeItemTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;

/**
 * Holds the structural rules `orders.validation.yml` cannot express.
 */
class OrderIntakeRequestValidator implements OrderIntakeRequestValidatorInterface
{
    protected const string FIELD_BILLING_ADDRESS = 'billingAddress';

    protected const string FIELD_SHIPPING_ADDRESS = 'shipment.shippingAddress';

    public const string FIELD_SHIPMENT_METHOD = 'shipment.shipmentMethod';

    public const string FIELD_ITEM_SHIPMENT_METHOD_PATTERN = 'items[%d].shipment.shipmentMethod';

    protected const string FIELD_ITEM_SHIPPING_ADDRESS_PATTERN = 'items[%d].shipment.shippingAddress';

    public function __construct(protected OrderIntakeItemValidatorInterface $orderIntakeItemValidator)
    {
    }

    public function validate(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): OrderIntakeResponseTransfer {
        $this->validateOptionalAddress(
            $orderIntakeResponseTransfer,
            static::FIELD_BILLING_ADDRESS,
            $orderIntakeRequestTransfer->getBillingAddress(),
        );

        $this->validateShippingAddress($orderIntakeResponseTransfer, $orderIntakeRequestTransfer);

        $this->validateShipmentMethod($orderIntakeResponseTransfer, $orderIntakeRequestTransfer);

        $index = 0;

        foreach ($orderIntakeRequestTransfer->getItems() as $orderIntakeItemTransfer) {
            $this->orderIntakeItemValidator->validate($orderIntakeResponseTransfer, $orderIntakeItemTransfer, $index);
            $this->validateItemShipmentMethod($orderIntakeResponseTransfer, $orderIntakeItemTransfer, $index);
            $this->validateOptionalAddress(
                $orderIntakeResponseTransfer,
                sprintf(static::FIELD_ITEM_SHIPPING_ADDRESS_PATTERN, $index),
                $orderIntakeItemTransfer->getShippingAddress(),
            );
            $index++;
        }

        return $orderIntakeResponseTransfer;
    }

    protected function validateShippingAddress(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
    ): void {
        if ($this->everyItemHasItsOwnShippingAddress($orderIntakeRequestTransfer)) {
            $this->validateOptionalAddress(
                $orderIntakeResponseTransfer,
                static::FIELD_SHIPPING_ADDRESS,
                $orderIntakeRequestTransfer->getShippingAddress(),
            );

            return;
        }

        $this->validateRequiredAddress(
            $orderIntakeResponseTransfer,
            static::FIELD_SHIPPING_ADDRESS,
            $orderIntakeRequestTransfer->getShippingAddress(),
        );
    }

    protected function everyItemHasItsOwnShippingAddress(OrderIntakeRequestTransfer $orderIntakeRequestTransfer): bool
    {
        $orderIntakeItemTransfers = $orderIntakeRequestTransfer->getItems();

        if (count($orderIntakeItemTransfers) === 0) {
            return false;
        }

        foreach ($orderIntakeItemTransfers as $orderIntakeItemTransfer) {
            if ($orderIntakeItemTransfer->getShippingAddress() === null) {
                return false;
            }
        }

        return true;
    }

    protected function validateRequiredAddress(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        string $field,
        ?AddressTransfer $addressTransfer,
    ): void {
        if ($addressTransfer === null) {
            $this->addIssue($orderIntakeResponseTransfer, $field, OrderIntakeItemValidator::MESSAGE_REQUIRED);

            return;
        }

        $this->validateOptionalAddress($orderIntakeResponseTransfer, $field, $addressTransfer);
    }

    protected function validateOptionalAddress(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        string $field,
        ?AddressTransfer $addressTransfer,
    ): void {
        if ($addressTransfer === null) {
            return;
        }

        $uuid = $addressTransfer->getUuid();

        if ($uuid !== null && $uuid !== '') {
            return;
        }

        $values = [
            'firstName' => $addressTransfer->getFirstName(),
            'lastName' => $addressTransfer->getLastName(),
            'zipCode' => $addressTransfer->getZipCode(),
            'city' => $addressTransfer->getCity(),
            'iso2Code' => $addressTransfer->getIso2Code(),
        ];

        foreach ($values as $addressField => $value) {
            if ($value !== null && trim($value) !== '') {
                continue;
            }

            $addressFieldPath = sprintf('%s.%s', $field, $addressField);

            $this->addIssue($orderIntakeResponseTransfer, $addressFieldPath, OrderIntakeItemValidator::MESSAGE_REQUIRED);
        }
    }

    protected function validateShipmentMethod(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
    ): void {
        if ($this->everyItemHasItsOwnShipmentMethod($orderIntakeRequestTransfer)) {
            return;
        }

        $shipmentMethodName = $orderIntakeRequestTransfer->getShipmentMethodName();

        if ($shipmentMethodName !== null && trim($shipmentMethodName) !== '') {
            return;
        }

        $this->addIssue(
            $orderIntakeResponseTransfer,
            static::FIELD_SHIPMENT_METHOD,
            OrderIntakeItemValidator::MESSAGE_REQUIRED,
        );
    }

    protected function everyItemHasItsOwnShipmentMethod(OrderIntakeRequestTransfer $orderIntakeRequestTransfer): bool
    {
        $orderIntakeItemTransfers = $orderIntakeRequestTransfer->getItems();

        if (count($orderIntakeItemTransfers) === 0) {
            return false;
        }

        foreach ($orderIntakeItemTransfers as $orderIntakeItemTransfer) {
            if ($orderIntakeItemTransfer->getShipmentMethodName() === null) {
                return false;
            }
        }

        return true;
    }

    protected function validateItemShipmentMethod(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        OrderIntakeItemTransfer $orderIntakeItemTransfer,
        int $index,
    ): void {
        $shipmentMethodName = $orderIntakeItemTransfer->getShipmentMethodName();

        if ($shipmentMethodName === null || trim($shipmentMethodName) !== '') {
            return;
        }

        $field = sprintf(static::FIELD_ITEM_SHIPMENT_METHOD_PATTERN, $index);

        $this->addIssue($orderIntakeResponseTransfer, $field, OrderIntakeItemValidator::MESSAGE_REQUIRED);
    }

    protected function addIssue(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        string $field,
        string $message,
    ): void {
        $orderIntakeResponseTransfer->addValidationIssue(
            (new OrderIntakeValidationIssueTransfer())
                ->setField($field)
                ->setMessage($message)
                ->setParameters(['%field%' => $field]),
        );
    }
}
