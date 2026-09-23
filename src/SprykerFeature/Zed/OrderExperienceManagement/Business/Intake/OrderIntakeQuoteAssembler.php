<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake;

use ArrayObject;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderIntakeItemTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\PaymentTransfer;
use Generated\Shared\Transfer\ProductOptionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Shared\Price\PriceConfig;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

/**
 * Builds a checkout-ready QuoteTransfer from an intake payload.
 */
class OrderIntakeQuoteAssembler implements OrderIntakeQuoteAssemblerInterface
{
    public function __construct(
        protected readonly string $defaultPriceMode,
    ) {
    }

    public function assembleQuote(OrderIntakeRequestTransfer $orderIntakeRequestTransfer): QuoteTransfer
    {
        $quoteTransfer = (new QuoteTransfer())
            ->setOrderCustomReference($orderIntakeRequestTransfer->getOrderCustomReference())
            ->setPriceMode($orderIntakeRequestTransfer->getPriceMode() ?? $this->defaultPriceMode)
            ->setStore((new StoreTransfer())->setName($orderIntakeRequestTransfer->getStoreNameOrFail()))
            ->setCurrency((new CurrencyTransfer())->setCode($orderIntakeRequestTransfer->getCurrencyCodeOrFail()))
            ->setItems(new ArrayObject())
            ->setBundleItems(new ArrayObject())
            ->setSource(OrderExperienceManagementConfig::SOURCE_API);

        $quoteTransfer = $this->assembleCustomer($orderIntakeRequestTransfer, $quoteTransfer);
        $quoteTransfer = $this->assembleAddresses($orderIntakeRequestTransfer, $quoteTransfer);
        $quoteTransfer = $this->assemblePayment($orderIntakeRequestTransfer, $quoteTransfer);

        return $this->assembleItems($orderIntakeRequestTransfer, $quoteTransfer);
    }

    protected function assembleCustomer(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        QuoteTransfer $quoteTransfer,
    ): QuoteTransfer {
        $customerTransfer = $orderIntakeRequestTransfer->getCustomerOrFail();

        return $quoteTransfer
            ->setCustomer($customerTransfer)
            ->setCustomerReference($customerTransfer->getCustomerReference());
    }

    protected function assembleAddresses(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        QuoteTransfer $quoteTransfer,
    ): QuoteTransfer {
        $billingAddressTransfer = $orderIntakeRequestTransfer->getBillingAddressOrFail()
            ->setIsAddressSavingSkipped(true);
        $shippingAddressTransfer = $orderIntakeRequestTransfer->getShippingAddress()
            ?->setIsAddressSavingSkipped(true);

        return $quoteTransfer
            ->setIsAddressSavingSkipped(true)
            ->setBillingAddress($billingAddressTransfer)
            ->setShippingAddress($shippingAddressTransfer);
    }

    protected function assemblePayment(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        QuoteTransfer $quoteTransfer,
    ): QuoteTransfer {
        $paymentMethodKey = $orderIntakeRequestTransfer->getPaymentMethodName();

        $paymentTransfer = (new PaymentTransfer())
            ->setPaymentMethod($paymentMethodKey)
            ->setPaymentMethodName($paymentMethodKey)
            ->setPaymentSelection($paymentMethodKey);

        return $quoteTransfer
            ->setPayment($paymentTransfer)
            ->setPayments(new ArrayObject([$paymentTransfer]));
    }

    /**
     * A submitted price becomes a SOURCE price, the platform's own manual-override slot.
     */
    protected function assembleItems(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        QuoteTransfer $quoteTransfer,
    ): QuoteTransfer {
        $shipmentMethodName = $orderIntakeRequestTransfer->getShipmentMethodName();

        foreach ($orderIntakeRequestTransfer->getItems() as $orderIntakeItemTransfer) {
            $itemTransfer = (new ItemTransfer())
                ->setSku($orderIntakeItemTransfer->getSkuOrFail())
                ->setQuantity($orderIntakeItemTransfer->getQuantity())
                ->setMerchantReference($orderIntakeItemTransfer->getMerchantReference())
                ->setProductOfferReference($orderIntakeItemTransfer->getProductOfferReference())
                ->setCartNote($orderIntakeItemTransfer->getCartNote());

            $unitCustomPrice = $orderIntakeItemTransfer->getUnitCustomPrice();

            if ($unitCustomPrice !== null) {
                $this->applySourceUnitPrice($itemTransfer, $unitCustomPrice, (string)$quoteTransfer->getPriceMode());
            }

            if ($shipmentMethodName !== null) {
                $itemTransfer->setShipment(
                    (new ShipmentTransfer())
                        ->setShippingAddress($quoteTransfer->getShippingAddress())
                        ->setShipmentSelection($shipmentMethodName),
                );
            }

            $this->assembleProductOptions($orderIntakeItemTransfer, $itemTransfer);

            $quoteTransfer->addItem($itemTransfer);
        }

        return $quoteTransfer;
    }

    protected function assembleProductOptions(
        OrderIntakeItemTransfer $orderIntakeItemTransfer,
        ItemTransfer $itemTransfer,
    ): void {
        foreach ($orderIntakeItemTransfer->getProductOptions() as $orderIntakeProductOptionTransfer) {
            $itemTransfer->addProductOption(
                (new ProductOptionTransfer())->setSku($orderIntakeProductOptionTransfer->getSku()),
            );
        }
    }

    protected function applySourceUnitPrice(ItemTransfer $itemTransfer, int $unitPrice, string $priceMode): void
    {
        if ($priceMode === PriceConfig::PRICE_MODE_NET) {
            $itemTransfer
                ->setSourceUnitNetPrice($unitPrice)
                ->setSourceUnitGrossPrice(null);

            return;
        }

        $itemTransfer
            ->setSourceUnitGrossPrice($unitPrice)
            ->setSourceUnitNetPrice(null);
    }
}
