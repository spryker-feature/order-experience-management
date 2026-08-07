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

    public function repriceQuoteItems(QuoteTransfer $quoteTransfer): CartChangeTransfer
    {
        $itemTransfers = $this->createRepricedItems($quoteTransfer->getItems());

        foreach ($this->createRepricedItems($quoteTransfer->getBundleItems()) as $repricedBundleItemTransfer) {
            $itemTransfers->append($repricedBundleItemTransfer);
        }

        if ($itemTransfers->count() === 0) {
            return new CartChangeTransfer();
        }

        return $this->priceCartChange($this->createContextQuoteWithoutItems($quoteTransfer), $itemTransfers);
    }

    protected function createContextQuoteWithoutItems(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        $itemTransfers = $quoteTransfer->getItems();
        $quoteTransfer->setItems(new ArrayObject());

        $contextQuoteTransfer = (new QuoteTransfer())->fromArray($quoteTransfer->toArray(), true);

        $quoteTransfer->setItems($itemTransfers);

        return $contextQuoteTransfer;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     */
    protected function priceCartChange(QuoteTransfer $quoteTransfer, ArrayObject $itemTransfers): CartChangeTransfer
    {
        $cartChangeTransfer = (new CartChangeTransfer())
            ->setQuote($quoteTransfer)
            ->setItems($itemTransfers);

        /** @var \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer */
        $cartChangeTransfer = $this->priceCartConnectorFacade->addPriceToItems($cartChangeTransfer, null, true);

        return $this->applyAmountAwareUnitPrice($cartChangeTransfer);
    }

    /**
     * @param iterable<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return \ArrayObject<int, \Generated\Shared\Transfer\ItemTransfer>
     */
    protected function createRepricedItems(iterable $itemTransfers): ArrayObject
    {
        $repricedItemTransfers = new ArrayObject();

        foreach ($itemTransfers as $itemTransfer) {
            $repricedItemTransfer = (new ItemTransfer())->fromArray($itemTransfer->toArray(), true);
            $this->clearItemPrices($repricedItemTransfer);
            $repricedItemTransfers->append($repricedItemTransfer);
        }

        return $repricedItemTransfers;
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
