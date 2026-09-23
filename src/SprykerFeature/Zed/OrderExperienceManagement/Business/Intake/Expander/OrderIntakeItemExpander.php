<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Product\Business\ProductFacadeInterface;

/**
 * Expands intake items with the product identity the calculation pipeline needs.
 */
class OrderIntakeItemExpander implements OrderIntakeItemExpanderInterface
{
    public const string FIELD_ITEM_SKU_PATTERN = 'items[%d].sku';

    protected const string MESSAGE_UNKNOWN_SKU = 'Product with SKU "%sku%" was not found.';

    public function __construct(
        protected readonly ProductFacadeInterface $productFacade,
    ) {
    }

    public function expandItems(
        QuoteTransfer $quoteTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): QuoteTransfer {
        $skus = [];

        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $skus[] = $itemTransfer->getSkuOrFail();
        }

        if ($skus === []) {
            return $quoteTransfer;
        }

        $productConcreteTransfersBySku = $this->indexProductConcretesBySku($skus);
        $namesByIdProductAbstract = $this->findNamesByIdProductAbstract($productConcreteTransfersBySku);

        foreach ($quoteTransfer->getItems() as $index => $itemTransfer) {
            $sku = $itemTransfer->getSkuOrFail();

            if (!isset($productConcreteTransfersBySku[$sku])) {
                $orderIntakeResponseTransfer->addValidationIssue(
                    (new OrderIntakeValidationIssueTransfer())
                        ->setField(sprintf(static::FIELD_ITEM_SKU_PATTERN, $index))
                        ->setMessage(static::MESSAGE_UNKNOWN_SKU)
                        ->setParameters(['%sku%' => $sku]),
                );

                continue;
            }

            $this->expandItem($itemTransfer, $productConcreteTransfersBySku[$sku], $namesByIdProductAbstract);
        }

        return $quoteTransfer;
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\ProductConcreteTransfer> $productConcreteTransfersBySku
     *
     * @return array<int, string>
     */
    protected function findNamesByIdProductAbstract(array $productConcreteTransfersBySku): array
    {
        $productAbstractIds = [];

        foreach ($productConcreteTransfersBySku as $productConcreteTransfer) {
            $productAbstractIds[] = $productConcreteTransfer->getFkProductAbstractOrFail();
        }

        if ($productAbstractIds === []) {
            return [];
        }

        return $this->productFacade->getProductAbstractLocalizedAttributeNamesIndexedByIdProductAbstract(
            array_values(array_unique($productAbstractIds)),
        );
    }

    /**
     * @param array<int, string> $skus
     *
     * @return array<string, \Generated\Shared\Transfer\ProductConcreteTransfer>
     */
    protected function indexProductConcretesBySku(array $skus): array
    {
        $productConcreteTransfersBySku = [];

        foreach ($this->productFacade->findProductConcretesBySkus($skus) as $productConcreteTransfer) {
            $productConcreteTransfersBySku[$productConcreteTransfer->getSkuOrFail()] = $productConcreteTransfer;
        }

        return $productConcreteTransfersBySku;
    }

    /**
     * @param array<int, string> $namesByIdProductAbstract
     */
    protected function expandItem(
        ItemTransfer $itemTransfer,
        ProductConcreteTransfer $productConcreteTransfer,
        array $namesByIdProductAbstract
    ): void {
        $itemTransfer
            ->setId($productConcreteTransfer->getIdProductConcrete())
            ->setIdProductAbstract($productConcreteTransfer->getFkProductAbstract())
            ->setAbstractSku($productConcreteTransfer->getAbstractSku())
            ->setConcreteAttributes($productConcreteTransfer->getAttributes())
            ->setName($this->resolveName($productConcreteTransfer, $namesByIdProductAbstract))
            ->setIsQuantitySplittable($productConcreteTransfer->getIsQuantitySplittable())
            ->setGroupKey($this->buildGroupKey($itemTransfer, $productConcreteTransfer));
    }

    protected function buildGroupKey(ItemTransfer $itemTransfer, ProductConcreteTransfer $productConcreteTransfer): string
    {
        $groupKeyParts = [
            $productConcreteTransfer->getSkuOrFail(),
            $itemTransfer->getProductOfferReference(),
            $itemTransfer->getMerchantReference(),
        ];

        $sourceUnitPrice = $itemTransfer->getSourceUnitGrossPrice() ?? $itemTransfer->getSourceUnitNetPrice();

        if ($sourceUnitPrice !== null) {
            $groupKeyParts[] = md5((string)$sourceUnitPrice);
        }

        return implode('_', array_filter($groupKeyParts, static fn (?string $groupKeyPart): bool => $groupKeyPart !== null && $groupKeyPart !== ''));
    }

    /**
     * @param array<int, string> $namesByIdProductAbstract
     */
    protected function resolveName(ProductConcreteTransfer $productConcreteTransfer, array $namesByIdProductAbstract): ?string
    {
        $idProductAbstract = $productConcreteTransfer->getFkProductAbstract();

        if ($idProductAbstract !== null && isset($namesByIdProductAbstract[$idProductAbstract])) {
            return $namesByIdProductAbstract[$idProductAbstract];
        }

        return $productConcreteTransfer->getSku();
    }
}
