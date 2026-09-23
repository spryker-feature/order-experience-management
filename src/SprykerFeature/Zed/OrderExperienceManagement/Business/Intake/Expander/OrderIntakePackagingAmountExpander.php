<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander;

use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderIntakeItemTransfer;
use Generated\Shared\Transfer\OrderIntakePackagingAmountTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use Generated\Shared\Transfer\ProductPackagingUnitAmountTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\DecimalObject\Decimal;
use Spryker\Zed\Product\Business\ProductFacadeInterface;
use Spryker\Zed\ProductPackagingUnit\Business\ProductPackagingUnitFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\ProductMeasurementSalesUnitCodeResolverInterface;

/**
 * Resolves how much of the contained product each package on a line holds.
 *
 * @see \Spryker\Client\ProductPackagingUnitStorage\Expander\ItemTransferExpander::expandWithDefaultPackagingUnit()
 * @see \Spryker\Zed\ProductPackagingUnit\Business\Model\Validator\ProductPackagingUnitAmountRestrictionValidator
 * @see \Spryker\Zed\ProductPackagingUnit\Business\Model\OrderItem\SplittableOrderItemTransformer
 */
class OrderIntakePackagingAmountExpander implements OrderIntakePackagingAmountExpanderInterface
{
    public const string FIELD_PACKAGING_AMOUNT_PATTERN = 'items[%d].packagingAmount';

    public const string FIELD_PACKAGING_AMOUNT_VALUE_PATTERN = 'items[%d].packagingAmount.amount';

    public const string FIELD_PACKAGING_AMOUNT_SALES_UNIT_CODE_PATTERN = 'items[%d].packagingAmount.salesUnitCode';

    protected const string MESSAGE_NOT_A_PACKAGE = 'Product "%sku%" is not sold as a package, so it takes no packaging amount.';

    protected const string MESSAGE_AMOUNT_NOT_VARIABLE = 'Product "%sku%" is sold only in its configured amount of %defaultAmount%. Omit the amount, or send that figure.';

    protected const string MESSAGE_AMOUNT_BELOW_MIN = 'Amount %amount% is below the minimum of %min% for product "%sku%".';

    protected const string MESSAGE_AMOUNT_ABOVE_MAX = 'Amount %amount% is above the maximum of %max% for product "%sku%".';

    protected const string MESSAGE_AMOUNT_OFF_INTERVAL = 'Amount %amount% is not a step of %interval% for product "%sku%".';

    protected const string MESSAGE_SALES_UNIT_NOT_AVAILABLE = 'Measurement unit "%code%" is not a sales unit of "%leadSku%", the product contained in "%sku%".';

    protected const string MESSAGE_SALES_UNIT_AMBIGUOUS = 'Measurement unit "%code%" matches more than one sales unit of "%leadSku%".';

    protected const string MESSAGE_NO_DEFAULT_AMOUNT = 'Product "%sku%" has no packaging amount configured and cannot be ordered as a package.';

    /**
     * @see \Spryker\Zed\ProductPackagingUnit\Business\Model\OrderItem\SplittableOrderItemTransformer
     */
    protected const int DIVISION_SCALE = 10;

    public function __construct(
        protected readonly ProductPackagingUnitFacadeInterface $productPackagingUnitFacade,
        protected readonly ProductMeasurementSalesUnitCodeResolverInterface $productMeasurementSalesUnitCodeResolver,
        protected readonly ProductFacadeInterface $productFacade,
    ) {
    }

    public function expandPackagingAmounts(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        QuoteTransfer $quoteTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): QuoteTransfer {
        $orderIntakeItemTransfers = array_values(iterator_to_array($orderIntakeRequestTransfer->getItems()));
        $itemTransfersByIndex = [];

        foreach ($quoteTransfer->getItems() as $index => $itemTransfer) {
            if (($orderIntakeItemTransfers[$index] ?? null)?->getPackagingAmount() === null) {
                continue;
            }

            $itemTransfersByIndex[$index] = $itemTransfer;
        }

        if ($itemTransfersByIndex === []) {
            return $quoteTransfer;
        }

        $this->resolvePackagingUnits($itemTransfersByIndex, $quoteTransfer);
        $leadProductIdsBySku = $this->findLeadProductIdsBySku($itemTransfersByIndex);

        $salesUnitsByCacheKey = [];

        foreach ($itemTransfersByIndex as $index => $itemTransfer) {
            $this->expandItemPackagingAmount(
                $itemTransfer,
                $orderIntakeItemTransfers[$index],
                $index,
                $quoteTransfer,
                $leadProductIdsBySku,
                $orderIntakeResponseTransfer,
                $salesUnitsByCacheKey,
            );
        }

        $this->repriceForOrderedAmounts($itemTransfersByIndex, $quoteTransfer);

        return $quoteTransfer;
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfersByIndex
     */
    protected function resolvePackagingUnits(array $itemTransfersByIndex, QuoteTransfer $quoteTransfer): void
    {
        foreach ($itemTransfersByIndex as $itemTransfer) {
            $itemTransfer->setAmount(Decimal::create(1));
        }

        $this->productPackagingUnitFacade->expandCartChangeWithProductPackagingUnit(
            $this->createCartChange($itemTransfersByIndex, $quoteTransfer),
        );

        foreach ($itemTransfersByIndex as $itemTransfer) {
            $itemTransfer->setAmount(null);
        }
    }

    /**
     * @param array<string, int> $leadProductIdsBySku
     * @param array<string, array<int, \Generated\Shared\Transfer\ProductMeasurementSalesUnitTransfer>> $salesUnitsByCacheKey
     */
    protected function expandItemPackagingAmount(
        ItemTransfer $itemTransfer,
        OrderIntakeItemTransfer $orderIntakeItemTransfer,
        int $index,
        QuoteTransfer $quoteTransfer,
        array $leadProductIdsBySku,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        array &$salesUnitsByCacheKey,
    ): void {
        $productPackagingUnitAmountTransfer = $itemTransfer->getProductPackagingUnit()?->getProductPackagingUnitAmount();

        if ($productPackagingUnitAmountTransfer === null) {
            $this->addIssue(
                $orderIntakeResponseTransfer,
                sprintf(static::FIELD_PACKAGING_AMOUNT_PATTERN, $index),
                static::MESSAGE_NOT_A_PACKAGE,
                ['%sku%' => (string)$itemTransfer->getSku()],
            );

            return;
        }

        $defaultAmount = $productPackagingUnitAmountTransfer->getDefaultAmount();

        if ($defaultAmount === null) {
            $this->addIssue(
                $orderIntakeResponseTransfer,
                sprintf(static::FIELD_PACKAGING_AMOUNT_PATTERN, $index),
                static::MESSAGE_NO_DEFAULT_AMOUNT,
                ['%sku%' => (string)$itemTransfer->getSku()],
            );

            return;
        }

        $this->resetQuantitySalesUnitForSelfLeadProduct($itemTransfer);

        $orderIntakePackagingAmountTransfer = $orderIntakeItemTransfer->getPackagingAmountOrFail();
        $submittedAmount = $orderIntakePackagingAmountTransfer->getAmount();
        $amountPerPackage = $submittedAmount === null ? $defaultAmount : Decimal::create($submittedAmount);

        if (
            $submittedAmount !== null
            && !$this->isAmountAllowed($amountPerPackage, $defaultAmount, $productPackagingUnitAmountTransfer, $itemTransfer, $index, $orderIntakeResponseTransfer)
        ) {
            return;
        }

        $isSalesUnitCodeApplied = $this->applySalesUnitCode(
            $itemTransfer,
            $orderIntakePackagingAmountTransfer,
            $quoteTransfer,
            $index,
            $leadProductIdsBySku,
            $orderIntakeResponseTransfer,
            $salesUnitsByCacheKey,
        );

        if (!$isSalesUnitCodeApplied) {
            return;
        }

        $itemTransfer->setAmount($amountPerPackage->multiply((int)$itemTransfer->getQuantity()));
    }

    protected function resetQuantitySalesUnitForSelfLeadProduct(ItemTransfer $itemTransfer): void
    {
        if ($itemTransfer->getAmountLeadProduct()?->getSku() !== $itemTransfer->getSku()) {
            return;
        }

        $itemTransfer->setQuantitySalesUnit(null);
    }

    protected function isAmountAllowed(
        Decimal $amountPerPackage,
        Decimal $defaultAmount,
        ProductPackagingUnitAmountTransfer $productPackagingUnitAmountTransfer,
        ItemTransfer $itemTransfer,
        int $index,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): bool {
        $sku = (string)$itemTransfer->getSku();
        $field = sprintf(static::FIELD_PACKAGING_AMOUNT_VALUE_PATTERN, $index);

        if ($productPackagingUnitAmountTransfer->getIsAmountVariable() !== true) {
            if ($amountPerPackage->equals($defaultAmount)) {
                return true;
            }

            $this->addIssue(
                $orderIntakeResponseTransfer,
                $field,
                static::MESSAGE_AMOUNT_NOT_VARIABLE,
                [
                    '%sku%' => $sku,
                    '%defaultAmount%' => (string)$defaultAmount->trim(),
                ],
            );

            return false;
        }

        $amountMin = $productPackagingUnitAmountTransfer->getAmountMin();

        if ($amountMin !== null && $amountPerPackage->lessThan($amountMin)) {
            $this->addIssue(
                $orderIntakeResponseTransfer,
                $field,
                static::MESSAGE_AMOUNT_BELOW_MIN,
                [
                    '%amount%' => (string)$amountPerPackage->trim(),
                    '%min%' => (string)$amountMin->trim(),
                    '%sku%' => $sku,
                ],
            );

            return false;
        }

        $amountMax = $productPackagingUnitAmountTransfer->getAmountMax();

        if ($amountMax !== null && $amountPerPackage->greaterThan($amountMax)) {
            $this->addIssue(
                $orderIntakeResponseTransfer,
                $field,
                static::MESSAGE_AMOUNT_ABOVE_MAX,
                [
                    '%amount%' => (string)$amountPerPackage->trim(),
                    '%max%' => (string)$amountMax->trim(),
                    '%sku%' => $sku,
                ],
            );

            return false;
        }

        return $this->isAmountOnInterval($amountPerPackage, $productPackagingUnitAmountTransfer, $sku, $field, $orderIntakeResponseTransfer);
    }

    protected function isAmountOnInterval(
        Decimal $amountPerPackage,
        ProductPackagingUnitAmountTransfer $productPackagingUnitAmountTransfer,
        string $sku,
        string $field,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): bool {
        $amountInterval = $productPackagingUnitAmountTransfer->getAmountInterval();

        if ($amountInterval === null || $amountInterval->isZero()) {
            return true;
        }

        $offset = $amountPerPackage->subtract($productPackagingUnitAmountTransfer->getAmountMin() ?? Decimal::create(0));
        $steps = $offset->divide($amountInterval, static::DIVISION_SCALE);

        if ($steps->equals($steps->round(0))) {
            return true;
        }

        $this->addIssue(
            $orderIntakeResponseTransfer,
            $field,
            static::MESSAGE_AMOUNT_OFF_INTERVAL,
            [
                '%amount%' => (string)$amountPerPackage->trim(),
                '%interval%' => (string)$amountInterval->trim(),
                '%sku%' => $sku,
            ],
        );

        return false;
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfersByIndex
     *
     * @return array<string, int>
     */
    protected function findLeadProductIdsBySku(array $itemTransfersByIndex): array
    {
        $leadProductSkus = [];

        foreach ($itemTransfersByIndex as $itemTransfer) {
            $leadProductSku = $itemTransfer->getAmountLeadProduct()?->getSku();

            if ($leadProductSku !== null && !in_array($leadProductSku, $leadProductSkus, true)) {
                $leadProductSkus[] = $leadProductSku;
            }
        }

        if ($leadProductSkus === []) {
            return [];
        }

        return $this->productFacade->getProductConcreteIdsByConcreteSkus($leadProductSkus);
    }

    /**
     * @param array<string, int> $leadProductIdsBySku
     * @param array<string, array<int, \Generated\Shared\Transfer\ProductMeasurementSalesUnitTransfer>> $salesUnitsByCacheKey
     */
    protected function applySalesUnitCode(
        ItemTransfer $itemTransfer,
        OrderIntakePackagingAmountTransfer $orderIntakePackagingAmountTransfer,
        QuoteTransfer $quoteTransfer,
        int $index,
        array $leadProductIdsBySku,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        array &$salesUnitsByCacheKey,
    ): bool {
        $code = $orderIntakePackagingAmountTransfer->getSalesUnitCode();

        if ($code === null || $code === '') {
            return true;
        }

        $leadProductTransfer = $itemTransfer->getAmountLeadProduct();
        $field = sprintf(static::FIELD_PACKAGING_AMOUNT_SALES_UNIT_CODE_PATTERN, $index);
        $idLeadProductConcrete = $leadProductIdsBySku[(string)$leadProductTransfer?->getSku()] ?? 0;
        $storeName = (string)$quoteTransfer->getStore()?->getName();
        $cacheKey = sprintf('%d|%s|%s', $idLeadProductConcrete, $code, $storeName);

        if (!array_key_exists($cacheKey, $salesUnitsByCacheKey)) {
            $salesUnitsByCacheKey[$cacheKey] = $this->productMeasurementSalesUnitCodeResolver->findSalesUnitsByCode(
                $idLeadProductConcrete,
                $code,
                $storeName,
            );
        }

        $matchedProductMeasurementSalesUnitTransfers = $salesUnitsByCacheKey[$cacheKey];

        if ($matchedProductMeasurementSalesUnitTransfers === []) {
            $this->addIssue(
                $orderIntakeResponseTransfer,
                $field,
                static::MESSAGE_SALES_UNIT_NOT_AVAILABLE,
                [
                    '%code%' => $code,
                    '%leadSku%' => (string)$leadProductTransfer?->getSku(),
                    '%sku%' => (string)$itemTransfer->getSku(),
                ],
            );

            return false;
        }

        if (count($matchedProductMeasurementSalesUnitTransfers) > 1) {
            $this->addIssue(
                $orderIntakeResponseTransfer,
                $field,
                static::MESSAGE_SALES_UNIT_AMBIGUOUS,
                [
                    '%code%' => $code,
                    '%leadSku%' => (string)$leadProductTransfer?->getSku(),
                ],
            );

            return false;
        }

        $itemTransfer->setAmountSalesUnit($matchedProductMeasurementSalesUnitTransfers[0]);

        return true;
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfersByIndex
     */
    protected function repriceForOrderedAmounts(array $itemTransfersByIndex, QuoteTransfer $quoteTransfer): void
    {
        $pricedItemTransfers = [];

        foreach ($itemTransfersByIndex as $itemTransfer) {
            if ($itemTransfer->getAmount() === null) {
                continue;
            }

            $pricedItemTransfers[] = $itemTransfer;
        }

        if ($pricedItemTransfers === []) {
            return;
        }

        $this->productPackagingUnitFacade->setCustomAmountPrice(
            $this->createCartChange($pricedItemTransfers, $quoteTransfer),
        );
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     */
    protected function createCartChange(array $itemTransfers, QuoteTransfer $quoteTransfer): CartChangeTransfer
    {
        $cartChangeTransfer = (new CartChangeTransfer())->setQuote($quoteTransfer);

        foreach ($itemTransfers as $itemTransfer) {
            $cartChangeTransfer->addItem($itemTransfer);
        }

        return $cartChangeTransfer;
    }

    /**
     * Adds a validation issue; `$parameters` fills the named placeholders `$message` still carries untranslated.
     *
     * @param array<string, string> $parameters
     */
    protected function addIssue(
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        string $field,
        string $message,
        array $parameters = [],
    ): void {
        $orderIntakeResponseTransfer->addValidationIssue(
            (new OrderIntakeValidationIssueTransfer())
                ->setField($field)
                ->setMessage($message)
                ->setParameters($parameters),
        );
    }
}
