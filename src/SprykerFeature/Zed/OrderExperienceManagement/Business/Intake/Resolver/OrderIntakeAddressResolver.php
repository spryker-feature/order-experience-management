<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;

/**
 * Swaps an address that names a `uuid` for the customer's stored address of that uuid.
 */
class OrderIntakeAddressResolver implements OrderIntakeAddressResolverInterface
{
    protected const string FIELD_BILLING_ADDRESS_UUID = 'billingAddress.uuid';

    protected const string FIELD_SHIPPING_ADDRESS_UUID = 'shipment.shippingAddress.uuid';

    protected const string FIELD_ITEM_SHIPPING_ADDRESS_UUID_PATTERN = 'items[%d].shipment.shippingAddress.uuid';

    protected const string MESSAGE_ADDRESS_UNKNOWN = 'Address "%uuid%" is not one of customer "%customerReference%" addresses.';

    public function resolveAddresses(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): OrderIntakeRequestTransfer {
        $customerTransfer = $orderIntakeRequestTransfer->getCustomer();

        if ($customerTransfer === null) {
            return $orderIntakeRequestTransfer;
        }

        $addressTransfersByUuid = $this->indexCustomerAddressesByUuid($customerTransfer);

        $orderIntakeRequestTransfer->setBillingAddress($this->resolveAddress(
            $orderIntakeRequestTransfer->getBillingAddress(),
            $addressTransfersByUuid,
            $customerTransfer,
            $orderIntakeResponseTransfer,
            static::FIELD_BILLING_ADDRESS_UUID,
        ));

        $orderIntakeRequestTransfer->setShippingAddress($this->resolveAddress(
            $orderIntakeRequestTransfer->getShippingAddress(),
            $addressTransfersByUuid,
            $customerTransfer,
            $orderIntakeResponseTransfer,
            static::FIELD_SHIPPING_ADDRESS_UUID,
        ));

        foreach ($orderIntakeRequestTransfer->getItems() as $index => $orderIntakeItemTransfer) {
            $orderIntakeItemTransfer->setShippingAddress($this->resolveAddress(
                $orderIntakeItemTransfer->getShippingAddress(),
                $addressTransfersByUuid,
                $customerTransfer,
                $orderIntakeResponseTransfer,
                sprintf(static::FIELD_ITEM_SHIPPING_ADDRESS_UUID_PATTERN, $index),
            ));
        }

        return $orderIntakeRequestTransfer;
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\AddressTransfer> $addressTransfersByUuid
     */
    protected function resolveAddress(
        ?AddressTransfer $addressTransfer,
        array $addressTransfersByUuid,
        CustomerTransfer $customerTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        string $field,
    ): ?AddressTransfer {
        $uuid = $addressTransfer?->getUuid();

        if ($uuid === null || $uuid === '') {
            return $addressTransfer;
        }

        $storedAddressTransfer = $addressTransfersByUuid[$uuid] ?? null;

        if ($storedAddressTransfer === null) {
            $orderIntakeResponseTransfer->addValidationIssue(
                (new OrderIntakeValidationIssueTransfer())
                    ->setField($field)
                    ->setMessage(static::MESSAGE_ADDRESS_UNKNOWN)
                    ->setParameters([
                        '%uuid%' => $uuid,
                        '%customerReference%' => (string)$customerTransfer->getCustomerReference(),
                    ]),
            );

            return $addressTransfer;
        }

        return (clone $storedAddressTransfer)->setIsAddressSavingSkipped(true);
    }

    /**
     * @return array<string, \Generated\Shared\Transfer\AddressTransfer>
     */
    protected function indexCustomerAddressesByUuid(CustomerTransfer $customerTransfer): array
    {
        $addressTransfersByUuid = [];
        $addressesTransfer = $customerTransfer->getAddresses();

        if ($addressesTransfer === null) {
            return $addressTransfersByUuid;
        }

        foreach ($addressesTransfer->getAddresses() as $addressTransfer) {
            $uuid = $addressTransfer->getUuid();

            if ($uuid === null || $uuid === '') {
                continue;
            }

            $addressTransfersByUuid[$uuid] = $addressTransfer;
        }

        return $addressTransfersByUuid;
    }
}
