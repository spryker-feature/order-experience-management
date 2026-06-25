<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator;

use ArrayObject;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\PriceCartConnector\Business\PriceCartConnectorFacadeInterface;
use Spryker\Zed\ProductPackagingUnit\Business\ProductPackagingUnitFacadeInterface;

class ScheduleItemRepricer implements ScheduleItemRepricerInterface
{
    public function __construct(
        protected readonly PriceCartConnectorFacadeInterface $priceCartConnectorFacade,
        protected readonly ProductPackagingUnitFacadeInterface $productPackagingUnitFacade,
    ) {
    }

    public function repriceItems(QuoteTransfer $quoteTransfer): CartChangeTransfer
    {
        // important: deep copy, not a clone $quoteTransfer
        $updatedQuoteTransfer = (new QuoteTransfer())->fromArray($quoteTransfer->toArray(), true);

        foreach ($updatedQuoteTransfer->getItems() as $itemTransfer) {
            $this->clearItemPrices($itemTransfer);
        }

        // Use a context quote without items so volume-price quantity counting does not double-count the items
        $contextQuoteTransfer = (new QuoteTransfer())->fromArray($updatedQuoteTransfer->toArray(), true);
        $contextQuoteTransfer->setItems(new ArrayObject());

        $cartChangeTransfer = (new CartChangeTransfer())
            ->setQuote($contextQuoteTransfer)
            ->setItems($updatedQuoteTransfer->getItems());

        /** @var \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer */
        $cartChangeTransfer = $this->priceCartConnectorFacade->addPriceToItems($cartChangeTransfer, null, true);
        $cartChangeTransfer = $this->applyAmountAwareUnitPrice($cartChangeTransfer);

        return $cartChangeTransfer;
    }

    /**
     * @return array<string, \Generated\Shared\Transfer\ItemTransfer>
     */
    public function repriceBundleItems(QuoteTransfer $quoteTransfer): array
    {
        $bundleItemTransfers = new ArrayObject();

        foreach ($quoteTransfer->getBundleItems() as $bundleItemTransfer) {
            $repricedBundleItemTransfer = (new ItemTransfer())->fromArray($bundleItemTransfer->toArray(), true);
            $this->clearItemPrices($repricedBundleItemTransfer);
            $bundleItemTransfers->append($repricedBundleItemTransfer);
        }

        if ($bundleItemTransfers->count() === 0) {
            return [];
        }

        $cartChangeTransfer = (new CartChangeTransfer())
            ->setQuote($quoteTransfer)
            ->setItems($bundleItemTransfers);

        /** @var \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer */
        $cartChangeTransfer = $this->priceCartConnectorFacade->addPriceToItems($cartChangeTransfer, null, true);
        $cartChangeTransfer = $this->applyAmountAwareUnitPrice($cartChangeTransfer);

        $repricedBundleItemsByBundleIdentifier = [];

        foreach ($cartChangeTransfer->getItems() as $repricedBundleItemTransfer) {
            $bundleItemIdentifier = $repricedBundleItemTransfer->getBundleItemIdentifier();

            if ($bundleItemIdentifier === null) {
                continue;
            }

            $repricedBundleItemsByBundleIdentifier[$bundleItemIdentifier] = $repricedBundleItemTransfer;
        }

        return $repricedBundleItemsByBundleIdentifier;
    }

    protected function applyAmountAwareUnitPrice(CartChangeTransfer $cartChangeTransfer): CartChangeTransfer
    {
        $quantitiesByItem = [];

        foreach ($cartChangeTransfer->getItems() as $itemTransfer) {
            if ($itemTransfer->getAmount() === null) {
                continue;
            }

            $quantitiesByItem[] = [$itemTransfer, $itemTransfer->getQuantity()];
            $itemTransfer->setQuantity(1);
        }

        $cartChangeTransfer = $this->productPackagingUnitFacade->setCustomAmountPrice($cartChangeTransfer);

        foreach ($quantitiesByItem as [$itemTransfer, $quantity]) {
            $itemTransfer->setQuantity($quantity);
        }

        return $cartChangeTransfer;
    }

    protected function clearItemPrices(ItemTransfer $itemTransfer): void
    {
        $itemTransfer
            ->setUnitPrice(null)
            ->setSumPrice(null)
            ->setUnitGrossPrice(null)
            ->setSumGrossPrice(null)
            ->setUnitNetPrice(null)
            ->setSumNetPrice(null);
    }
}
