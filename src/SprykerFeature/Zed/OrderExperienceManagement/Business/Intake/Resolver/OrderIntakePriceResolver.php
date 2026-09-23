<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use Generated\Shared\Transfer\PriceProductFilterTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Service\PriceProduct\PriceProductServiceInterface;
use Spryker\Shared\Price\PriceConfig;
use Spryker\Zed\PriceProduct\Business\PriceProductFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeItemExpander;

/**
 * Plays `PriceManager`'s part for a quote that never passed through a cart.
 *
 * @see \Spryker\Zed\PriceCartConnector\Business\Manager\PriceManager
 */
class OrderIntakePriceResolver implements OrderIntakePriceResolverInterface
{
    protected const string FIELD_ITEM_UNIT_PRICE_PATTERN = 'items[%d].unitCustomPrice';

    protected const string MESSAGE_PRICE_NOT_FOUND = 'No price found for "%sku%" in %store% %currency%. Send a unitCustomPrice to override.';

    public function __construct(
        protected readonly PriceProductFacadeInterface $priceProductFacade,
        protected readonly PriceProductServiceInterface $priceProductService,
    ) {
    }

    public function resolvePrices(
        QuoteTransfer $quoteTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): QuoteTransfer {
        $priceProductFilterTransfersByIndex = $this->buildPriceProductFilters($quoteTransfer, $orderIntakeResponseTransfer);

        if ($priceProductFilterTransfersByIndex === []) {
            return $quoteTransfer;
        }

        $priceProductTransfers = $this->priceProductFacade->getValidPrices(
            array_values($priceProductFilterTransfersByIndex),
        );

        $priceMode = (string)$quoteTransfer->getPriceMode();

        foreach ($priceProductFilterTransfersByIndex as $index => $priceProductFilterTransfer) {
            $itemTransfer = $quoteTransfer->getItems()->offsetGet($index);

            $priceProductTransfer = $this->priceProductService->resolveProductPriceByPriceProductFilter(
                $priceProductTransfers,
                $priceProductFilterTransfer,
            );

            $catalogUnitPrice = $this->extractUnitPrice($priceProductTransfer, $priceMode);

            $this->applyOriginUnitPrice($itemTransfer, $catalogUnitPrice, $priceMode);

            $sourceUnitPrice = $this->findSourceUnitPrice($itemTransfer, $priceMode);

            if ($sourceUnitPrice !== null) {
                $this->applyUnitPrice($itemTransfer, $sourceUnitPrice, $priceMode);

                continue;
            }

            if ($catalogUnitPrice === null) {
                $this->rejectUnpricedItem($orderIntakeResponseTransfer, $quoteTransfer, $itemTransfer, $index);

                continue;
            }

            $this->applyUnitPrice($itemTransfer, $catalogUnitPrice, $priceMode);
        }

        return $quoteTransfer;
    }

    /**
     * @return array<int, \Generated\Shared\Transfer\PriceProductFilterTransfer>
     */
    protected function buildPriceProductFilters(
        QuoteTransfer $quoteTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): array {
        $priceProductFilterTransfersByIndex = [];
        $priceTypeName = $this->priceProductFacade->getDefaultPriceTypeName();
        $validationIssueFields = $this->indexValidationIssueFields($orderIntakeResponseTransfer);
        $quantitiesByPriceRelevantKey = $this->aggregateQuantitiesByPriceRelevantKey($quoteTransfer);

        foreach ($quoteTransfer->getItems() as $index => $itemTransfer) {
            $skuField = sprintf(OrderIntakeItemExpander::FIELD_ITEM_SKU_PATTERN, $index);

            if (isset($validationIssueFields[$skuField])) {
                continue;
            }

            $priceProductFilterTransfersByIndex[$index] = (new PriceProductFilterTransfer())
                ->setSku($itemTransfer->getSku())
                ->setPriceTypeName($priceTypeName)
                ->setPriceMode($quoteTransfer->getPriceMode())
                ->setCurrencyIsoCode($quoteTransfer->getCurrency()?->getCode())
                ->setStoreName($quoteTransfer->getStore()?->getName())
                ->setQuantity($quantitiesByPriceRelevantKey[$this->buildPriceRelevantKey($itemTransfer)])
                ->setProductOfferReference($itemTransfer->getProductOfferReference())
                ->setQuote($quoteTransfer)
                ->setIdentifier((string)$index);
        }

        return $priceProductFilterTransfersByIndex;
    }

    /**
     * A line's volume-price tier is resolved from the TOTAL quantity across every line that is the
     * "same item" for pricing purposes, not that individual line's own quantity — otherwise splitting
     * one order across several lines of the same SKU/offer silently loses the volume price a single combined line would have earned.
     *
     * @return array<string, int>
     */
    protected function aggregateQuantitiesByPriceRelevantKey(QuoteTransfer $quoteTransfer): array
    {
        $quantitiesByPriceRelevantKey = [];

        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $priceRelevantKey = $this->buildPriceRelevantKey($itemTransfer);
            $quantitiesByPriceRelevantKey[$priceRelevantKey] = ($quantitiesByPriceRelevantKey[$priceRelevantKey] ?? 0)
                + ($itemTransfer->getQuantity() ?? 0);
        }

        return $quantitiesByPriceRelevantKey;
    }

    protected function buildPriceRelevantKey(ItemTransfer $itemTransfer): string
    {
        return implode('-', [
            (string)$itemTransfer->getSku(),
            (string)$itemTransfer->getMerchantReference(),
            (string)$itemTransfer->getProductOfferReference(),
        ]);
    }

    /**
     * @return array<string, true>
     */
    protected function indexValidationIssueFields(OrderIntakeResponseTransfer $orderIntakeResponseTransfer): array
    {
        $validationIssueFields = [];

        foreach ($orderIntakeResponseTransfer->getValidationIssues() as $validationIssueTransfer) {
            $validationIssueFields[(string)$validationIssueTransfer->getField()] = true;
        }

        return $validationIssueFields;
    }

    protected function findSourceUnitPrice(ItemTransfer $itemTransfer, string $priceMode): ?int
    {
        if ($priceMode === PriceConfig::PRICE_MODE_NET) {
            return $itemTransfer->getSourceUnitNetPrice();
        }

        return $itemTransfer->getSourceUnitGrossPrice();
    }

    protected function extractUnitPrice(?PriceProductTransfer $priceProductTransfer, string $priceMode): ?int
    {
        $moneyValueTransfer = $priceProductTransfer?->getMoneyValue();

        if ($moneyValueTransfer === null) {
            return null;
        }

        if ($priceMode === PriceConfig::PRICE_MODE_NET) {
            return $moneyValueTransfer->getNetAmount();
        }

        return $moneyValueTransfer->getGrossAmount();
    }

    protected function applyOriginUnitPrice(ItemTransfer $itemTransfer, ?int $catalogUnitPrice, string $priceMode): void
    {
        if ($catalogUnitPrice === null) {
            return;
        }

        if ($priceMode === PriceConfig::PRICE_MODE_NET) {
            $itemTransfer->setOriginUnitNetPrice($catalogUnitPrice);

            return;
        }

        $itemTransfer->setOriginUnitGrossPrice($catalogUnitPrice);
    }

    protected function applyUnitPrice(ItemTransfer $itemTransfer, int $unitPrice, string $priceMode): void
    {
        if ($priceMode === PriceConfig::PRICE_MODE_NET) {
            $itemTransfer
                ->setUnitNetPrice($unitPrice)
                ->setUnitGrossPrice(0);

            return;
        }

        $itemTransfer
            ->setUnitGrossPrice($unitPrice)
            ->setUnitNetPrice(0);
    }

    protected function rejectUnpricedItem(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        QuoteTransfer $quoteTransfer,
        ItemTransfer $itemTransfer,
        int $index,
    ): void {
        $orderIntakeResponseTransfer->addValidationIssue(
            (new OrderIntakeValidationIssueTransfer())
                ->setField(sprintf(static::FIELD_ITEM_UNIT_PRICE_PATTERN, $index))
                ->setMessage(static::MESSAGE_PRICE_NOT_FOUND)
                ->setParameters([
                    '%sku%' => (string)$itemTransfer->getSku(),
                    '%store%' => (string)$quoteTransfer->getStore()?->getName(),
                    '%currency%' => (string)$quoteTransfer->getCurrency()?->getCode(),
                ]),
        );
    }
}
