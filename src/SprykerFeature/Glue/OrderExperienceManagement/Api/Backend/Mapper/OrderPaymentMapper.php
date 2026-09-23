<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper;

use Generated\Api\Backend\Orders\OrdersPaymentsBackendObject;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Spryker\Shared\Kernel\Transfer\TransferInterface;

class OrderPaymentMapper implements OrderPaymentMapperInterface
{
    /**
     * @return array<int, \Generated\Api\Backend\Orders\OrdersPaymentsBackendObject>
     */
    public function mapOrderTransferToOrdersPayments(OrderTransfer $orderTransfer): array
    {
        $payments = [];

        foreach ($orderTransfer->getPayments() as $paymentTransfer) {
            $payments[] = (new OrdersPaymentsBackendObject())
                ->setPaymentProvider($paymentTransfer->getPaymentProvider())
                ->setPaymentMethod($paymentTransfer->getPaymentMethod())
                ->setAmount($paymentTransfer->getAmount())
                ->setMeta($this->mapPaymentMeta($paymentTransfer));
        }

        return $payments;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function mapPaymentMeta(PaymentTransfer $paymentTransfer): array
    {
        $meta = [];

        foreach ($paymentTransfer->toArray(true, true) as $propertyName => $propertyValue) {
            if ($propertyValue === null) {
                continue;
            }

            $getter = sprintf('get%s', ucfirst((string)$propertyName));

            if (!method_exists($paymentTransfer, $getter) || !($paymentTransfer->$getter() instanceof TransferInterface)) {
                continue;
            }

            $meta[$propertyName] = $propertyValue;
        }

        return $meta;
    }
}
