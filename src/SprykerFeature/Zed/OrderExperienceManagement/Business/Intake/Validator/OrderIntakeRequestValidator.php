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
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakePackagingAmountExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeProductOptionExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeSalesUnitExpander;

/**
 * Holds the structural rules `orders.validation.yml` cannot express.
 */
class OrderIntakeRequestValidator implements OrderIntakeRequestValidatorInterface
{
    protected const string FIELD_BILLING_ADDRESS = 'billingAddress';

    protected const string FIELD_SHIPPING_ADDRESS = 'shipment.shippingAddress';

    public const string FIELD_SHIPMENT_METHOD = 'shipment.shipmentMethod';

    public const string FIELD_ITEM_SHIPMENT_METHOD_PATTERN = 'items[%d].shipment.shipmentMethod';

    protected const string FIELD_ITEM_PATTERN = 'items[%d].%s';

    protected const string FIELD_ITEM_SHIPPING_ADDRESS_PATTERN = 'items[%d].shipment.shippingAddress';

    protected const string MESSAGE_REQUIRED = '"%field%" is required.';

    protected const string MESSAGE_QUANTITY_TOO_LOW = '"%field%" must be at least 1.';

    protected const string MESSAGE_AMOUNT_TOO_LOW = '"%field%" must be greater than 0.';

    protected const string MESSAGE_UNIT_PRICE_NEGATIVE = '"%field%" cannot be negative.';

    protected const int MINIMUM_QUANTITY = 1;

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
            $this->validateItem($orderIntakeResponseTransfer, $orderIntakeItemTransfer, $index);
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

    protected function validateItem(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        OrderIntakeItemTransfer $orderIntakeItemTransfer,
        int $index,
    ): void {
        $skuField = sprintf(static::FIELD_ITEM_PATTERN, $index, 'sku');
        $sku = $orderIntakeItemTransfer->getSku();

        if ($sku === null || trim($sku) === '') {
            $this->addIssue($orderIntakeResponseTransfer, $skuField, static::MESSAGE_REQUIRED);
        }

        $quantityField = sprintf(static::FIELD_ITEM_PATTERN, $index, 'quantity');
        $quantity = $orderIntakeItemTransfer->getQuantity();

        if ($quantity === null && $orderIntakeItemTransfer->getSalesUnit()?->getAmount() === null) {
            $this->addIssue($orderIntakeResponseTransfer, $quantityField, static::MESSAGE_REQUIRED);
        } elseif ($quantity !== null && $quantity < static::MINIMUM_QUANTITY) {
            $this->addIssue($orderIntakeResponseTransfer, $quantityField, static::MESSAGE_QUANTITY_TOO_LOW);
        }

        // Optional: an omitted price is resolved from the catalogue by OrderIntakePriceResolver, and
        // only a line the catalogue cannot price is rejected — there. A supplied one still wins.
        $unitCustomPriceField = sprintf(static::FIELD_ITEM_PATTERN, $index, 'unitCustomPrice');
        $unitCustomPrice = $orderIntakeItemTransfer->getUnitCustomPrice();

        if ($unitCustomPrice !== null && $unitCustomPrice < 0) {
            $this->addIssue($orderIntakeResponseTransfer, $unitCustomPriceField, static::MESSAGE_UNIT_PRICE_NEGATIVE);
        }

        $this->validateItemProductOptions($orderIntakeResponseTransfer, $orderIntakeItemTransfer, $index);
        $this->validateItemSalesUnit($orderIntakeResponseTransfer, $orderIntakeItemTransfer, $index);
        $this->validateItemPackagingAmount($orderIntakeResponseTransfer, $orderIntakeItemTransfer, $index);
    }

    /**
     * Only the shape is checked here. Whether the amount fits the package's configured minimum,
     * maximum and step needs that package, which is resolved later.
     *
     * @see \SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakePackagingAmountExpander
     */
    protected function validateItemPackagingAmount(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        OrderIntakeItemTransfer $orderIntakeItemTransfer,
        int $index,
    ): void {
        $amount = $orderIntakeItemTransfer->getPackagingAmount()?->getAmount();

        if ($amount === null || $amount > 0) {
            return;
        }

        $field = sprintf(OrderIntakePackagingAmountExpander::FIELD_PACKAGING_AMOUNT_VALUE_PATTERN, $index);

        $this->addIssue($orderIntakeResponseTransfer, $field, static::MESSAGE_AMOUNT_TOO_LOW);
    }

    /**
     * `code` is what selects the sales unit, so a `salesUnit` object without one names nothing. The
     * amount itself is checked against the resolved conversion later, where that conversion is known.
     *
     * @see \SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeSalesUnitExpander
     */
    protected function validateItemSalesUnit(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        OrderIntakeItemTransfer $orderIntakeItemTransfer,
        int $index,
    ): void {
        $orderIntakeSalesUnitTransfer = $orderIntakeItemTransfer->getSalesUnit();

        if ($orderIntakeSalesUnitTransfer === null) {
            return;
        }

        $code = $orderIntakeSalesUnitTransfer->getCode();

        if ($code === null || trim($code) === '') {
            $field = sprintf(OrderIntakeSalesUnitExpander::FIELD_SALES_UNIT_CODE_PATTERN, $index);

            $this->addIssue($orderIntakeResponseTransfer, $field, static::MESSAGE_REQUIRED);
        }

        $amount = $orderIntakeSalesUnitTransfer->getAmount();

        if ($amount !== null && $amount <= 0) {
            $field = sprintf(OrderIntakeSalesUnitExpander::FIELD_SALES_UNIT_AMOUNT_PATTERN, $index);

            $this->addIssue($orderIntakeResponseTransfer, $field, static::MESSAGE_AMOUNT_TOO_LOW);
        }
    }

    protected function validateItemProductOptions(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        OrderIntakeItemTransfer $orderIntakeItemTransfer,
        int $index,
    ): void {
        foreach ($orderIntakeItemTransfer->getProductOptions() as $productOptionIndex => $orderIntakeProductOptionTransfer) {
            $sku = $orderIntakeProductOptionTransfer->getSku();

            if ($sku !== null && trim($sku) !== '') {
                continue;
            }

            $field = sprintf(OrderIntakeProductOptionExpander::FIELD_PRODUCT_OPTION_SKU_PATTERN, $index, $productOptionIndex);

            $this->addIssue($orderIntakeResponseTransfer, $field, static::MESSAGE_REQUIRED);
        }
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
            $this->addIssue($orderIntakeResponseTransfer, $field, static::MESSAGE_REQUIRED);

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

            $this->addIssue($orderIntakeResponseTransfer, $addressFieldPath, static::MESSAGE_REQUIRED);
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
            static::MESSAGE_REQUIRED,
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

        $this->addIssue($orderIntakeResponseTransfer, $field, static::MESSAGE_REQUIRED);
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
