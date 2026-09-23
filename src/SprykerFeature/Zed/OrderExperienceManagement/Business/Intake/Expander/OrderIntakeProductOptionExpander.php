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
use Generated\Shared\Transfer\ProductOptionCriteriaTransfer;
use Generated\Shared\Transfer\ProductOptionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\ProductOption\Business\ProductOptionFacadeInterface;

/**
 * Resolves the product options an intake line asked for into the options the order is placed with.
 */
class OrderIntakeProductOptionExpander implements OrderIntakeProductOptionExpanderInterface
{
    public const string FIELD_PRODUCT_OPTION_SKU_PATTERN = 'items[%d].productOptions[%d].sku';

    protected const string MESSAGE_OPTION_NOT_AVAILABLE = 'Product option "%productOptionSku%" was not found, belongs to an inactive option group, or is not assigned to product "%itemSku%".';

    protected const string MESSAGE_OPTION_NOT_PRICED = 'Product option "%productOptionSku%" has no price in the order\'s store and currency.';

    protected const string MESSAGE_OPTION_DUPLICATED = 'Product option "%productOptionSku%" is listed more than once on this line.';

    public function __construct(
        protected readonly ProductOptionFacadeInterface $productOptionFacade,
    ) {
    }

    public function expandProductOptions(
        QuoteTransfer $quoteTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): QuoteTransfer {
        $availableProductOptionsByCacheKey = [];

        foreach ($quoteTransfer->getItems() as $itemIndex => $itemTransfer) {
            if ($itemTransfer->getProductOptions()->count() === 0) {
                continue;
            }

            $this->expandItemProductOptions(
                $itemTransfer,
                $itemIndex,
                $quoteTransfer,
                $orderIntakeResponseTransfer,
                $availableProductOptionsByCacheKey,
            );
        }

        return $quoteTransfer;
    }

    /**
     * @param array<string, array<string, \Generated\Shared\Transfer\ProductOptionTransfer>> $availableProductOptionsByCacheKey
     */
    protected function expandItemProductOptions(
        ItemTransfer $itemTransfer,
        int $itemIndex,
        QuoteTransfer $quoteTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        array &$availableProductOptionsByCacheKey,
    ): void {
        $itemSku = (string)$itemTransfer->getSku();
        $requestedProductOptionSkus = $this->extractRequestedProductOptionSkus($itemTransfer);
        sort($requestedProductOptionSkus);
        $cacheKey = sprintf('%s|%s', $itemSku, implode(',', $requestedProductOptionSkus));

        if (!array_key_exists($cacheKey, $availableProductOptionsByCacheKey)) {
            $availableProductOptionsByCacheKey[$cacheKey] = $this->findAvailableProductOptionsBySku(
                $itemTransfer,
                $quoteTransfer,
                $requestedProductOptionSkus,
            );
        }

        $productOptionTransfersBySku = $availableProductOptionsByCacheKey[$cacheKey];
        $seenProductOptionSkus = [];

        foreach ($itemTransfer->getProductOptions() as $productOptionIndex => $productOptionTransfer) {
            $productOptionSku = (string)$productOptionTransfer->getSku();

            if (isset($seenProductOptionSkus[$productOptionSku])) {
                $this->addIssue(
                    $orderIntakeResponseTransfer,
                    $itemIndex,
                    $productOptionIndex,
                    static::MESSAGE_OPTION_DUPLICATED,
                    ['%productOptionSku%' => $productOptionSku],
                );

                continue;
            }

            $seenProductOptionSkus[$productOptionSku] = true;
            $availableProductOptionTransfer = $productOptionTransfersBySku[$productOptionSku] ?? null;

            if ($availableProductOptionTransfer === null) {
                $this->addIssue(
                    $orderIntakeResponseTransfer,
                    $itemIndex,
                    $productOptionIndex,
                    static::MESSAGE_OPTION_NOT_AVAILABLE,
                    ['%productOptionSku%' => $productOptionSku, '%itemSku%' => $itemSku],
                );

                continue;
            }

            if ($availableProductOptionTransfer->getUnitPrice() === null) {
                $this->addIssue(
                    $orderIntakeResponseTransfer,
                    $itemIndex,
                    $productOptionIndex,
                    static::MESSAGE_OPTION_NOT_PRICED,
                    ['%productOptionSku%' => $productOptionSku],
                );

                continue;
            }

            $this->applyAvailableProductOption($productOptionTransfer, $availableProductOptionTransfer, $itemTransfer);
        }
    }

    /**
     * @param array<int, string> $requestedProductOptionSkus
     *
     * @return array<string, \Generated\Shared\Transfer\ProductOptionTransfer>
     */
    protected function findAvailableProductOptionsBySku(
        ItemTransfer $itemTransfer,
        QuoteTransfer $quoteTransfer,
        array $requestedProductOptionSkus,
    ): array {
        $productOptionCriteriaTransfer = (new ProductOptionCriteriaTransfer())
            ->setProductOptionValueSkus($requestedProductOptionSkus)
            ->setProductConcreteSku($itemTransfer->getSku())
            ->setProductOptionGroupIsActive(true)
            ->setPriceMode($quoteTransfer->getPriceMode())
            ->setCurrencyIsoCode($quoteTransfer->getCurrency()?->getCode());

        $productOptionCollectionTransfer = $this->productOptionFacade
            ->getProductOptionCollectionByProductOptionCriteria($productOptionCriteriaTransfer);

        $productOptionTransfersBySku = [];

        foreach ($productOptionCollectionTransfer->getProductOptions() as $productOptionTransfer) {
            $productOptionTransfersBySku[(string)$productOptionTransfer->getSku()] = $productOptionTransfer;
        }

        return $productOptionTransfersBySku;
    }

    /**
     * @return array<int, string>
     */
    protected function extractRequestedProductOptionSkus(ItemTransfer $itemTransfer): array
    {
        $productOptionSkus = [];

        foreach ($itemTransfer->getProductOptions() as $productOptionTransfer) {
            $productOptionSku = (string)$productOptionTransfer->getSku();

            if (!in_array($productOptionSku, $productOptionSkus, true)) {
                $productOptionSkus[] = $productOptionSku;
            }
        }

        return $productOptionSkus;
    }

    protected function applyAvailableProductOption(
        ProductOptionTransfer $productOptionTransfer,
        ProductOptionTransfer $availableProductOptionTransfer,
        ItemTransfer $itemTransfer,
    ): void {
        $productOptionTransfer
            ->fromArray($availableProductOptionTransfer->modifiedToArray(), true)
            ->setQuantity($itemTransfer->getQuantity());
    }

    /**
     * Adds a validation issue; `$parameters` fills the named placeholders `$message` still carries untranslated.
     *
     * @param array<string, string> $parameters
     */
    protected function addIssue(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        int $itemIndex,
        int $productOptionIndex,
        string $message,
        array $parameters = [],
    ): void {
        $orderIntakeResponseTransfer->addValidationIssue(
            (new OrderIntakeValidationIssueTransfer())
                ->setField(sprintf(static::FIELD_PRODUCT_OPTION_SKU_PATTERN, $itemIndex, $productOptionIndex))
                ->setMessage($message)
                ->setParameters($parameters),
        );
    }
}
