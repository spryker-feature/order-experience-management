<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderIntakeItemTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use Generated\Shared\Transfer\ProductMeasurementSalesUnitTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\ProductMeasurementSalesUnitCodeResolverInterface;

/**
 * Resolves the measurement unit a line was ordered in, and the base quantity that follows from it.
 */
class OrderIntakeSalesUnitExpander implements OrderIntakeSalesUnitExpanderInterface
{
    public const string FIELD_QUANTITY_PATTERN = 'items[%d].quantity';

    public const string FIELD_SALES_UNIT_CODE_PATTERN = 'items[%d].salesUnit.code';

    public const string FIELD_SALES_UNIT_AMOUNT_PATTERN = 'items[%d].salesUnit.amount';

    protected const string MESSAGE_UNIT_NOT_AVAILABLE = 'Measurement unit "%code%" is not a sales unit of product "%sku%" in store "%storeName%".';

    protected const string MESSAGE_UNIT_AMBIGUOUS = 'Measurement unit "%code%" matches more than one sales unit of product "%sku%".';

    protected const string MESSAGE_AMOUNT_NOT_WHOLE = 'Amount %amount% of "%code%" is %rawQuantity% base units, which is not a whole number. Order a multiple of %step%.';

    protected const string MESSAGE_AMOUNT_NOT_POSITIVE = 'Amount %amount% of "%code%" resolves to no base units at all.';

    protected const string MESSAGE_QUANTITY_CONTRADICTS_AMOUNT = 'Quantity %quantity% contradicts amount %amount% of "%code%", which is %derivedQuantity% base units. Omit the quantity, or send the base-unit figure.';

    /**
     * Floating-point slack for the whole-number test. `conversion` is a FLOAT column and the amount
     * arrives as JSON, so 0.1 * 3 lands at 0.30000000000000004 and an exact comparison would reject
     * an order that is arithmetically fine.
     */
    protected const float QUANTITY_EPSILON = 0.000001;

    public function __construct(
        protected readonly ProductMeasurementSalesUnitCodeResolverInterface $productMeasurementSalesUnitCodeResolver,
    ) {
    }

    public function expandSalesUnits(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        QuoteTransfer $quoteTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): QuoteTransfer {
        $orderIntakeItemTransfers = array_values(iterator_to_array($orderIntakeRequestTransfer->getItems()));

        $salesUnitsByCacheKey = [];

        foreach ($quoteTransfer->getItems() as $index => $itemTransfer) {
            $orderIntakeItemTransfer = $orderIntakeItemTransfers[$index] ?? null;

            if ($orderIntakeItemTransfer?->getSalesUnit() === null) {
                continue;
            }

            $this->expandItemSalesUnit(
                $itemTransfer,
                $orderIntakeItemTransfer,
                $index,
                $quoteTransfer,
                $orderIntakeResponseTransfer,
                $salesUnitsByCacheKey,
            );
        }

        return $quoteTransfer;
    }

    /**
     * @param array<string, array<int, \Generated\Shared\Transfer\ProductMeasurementSalesUnitTransfer>> $salesUnitsByCacheKey
     */
    protected function expandItemSalesUnit(
        ItemTransfer $itemTransfer,
        OrderIntakeItemTransfer $orderIntakeItemTransfer,
        int $index,
        QuoteTransfer $quoteTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        array &$salesUnitsByCacheKey,
    ): void {
        $orderIntakeSalesUnitTransfer = $orderIntakeItemTransfer->getSalesUnitOrFail();
        $code = (string)$orderIntakeSalesUnitTransfer->getCode();

        $productMeasurementSalesUnitTransfer = $this->findSalesUnitByCode(
            $itemTransfer,
            $code,
            $quoteTransfer,
            $index,
            $orderIntakeResponseTransfer,
            $salesUnitsByCacheKey,
        );

        if ($productMeasurementSalesUnitTransfer === null) {
            return;
        }

        $itemTransfer->setQuantitySalesUnit($productMeasurementSalesUnitTransfer);

        $amount = $orderIntakeSalesUnitTransfer->getAmount();

        if ($amount === null) {
            return;
        }

        $this->applyDerivedQuantity(
            $itemTransfer,
            $productMeasurementSalesUnitTransfer,
            $amount,
            $code,
            $index,
            $orderIntakeResponseTransfer,
        );
    }

    /**
     * @param array<string, array<int, \Generated\Shared\Transfer\ProductMeasurementSalesUnitTransfer>> $salesUnitsByCacheKey
     */
    protected function findSalesUnitByCode(
        ItemTransfer $itemTransfer,
        string $code,
        QuoteTransfer $quoteTransfer,
        int $index,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
        array &$salesUnitsByCacheKey,
    ): ?ProductMeasurementSalesUnitTransfer {
        $storeName = (string)$quoteTransfer->getStore()?->getName();
        $idProductConcrete = (int)$itemTransfer->getId();
        $cacheKey = sprintf('%d|%s|%s', $idProductConcrete, $code, $storeName);

        if (!array_key_exists($cacheKey, $salesUnitsByCacheKey)) {
            $salesUnitsByCacheKey[$cacheKey] = $this->productMeasurementSalesUnitCodeResolver->findSalesUnitsByCode(
                $idProductConcrete,
                $code,
                $storeName,
            );
        }

        $matchedProductMeasurementSalesUnitTransfers = $salesUnitsByCacheKey[$cacheKey];

        if ($matchedProductMeasurementSalesUnitTransfers === []) {
            $this->addIssue(
                $orderIntakeResponseTransfer,
                sprintf(static::FIELD_SALES_UNIT_CODE_PATTERN, $index),
                static::MESSAGE_UNIT_NOT_AVAILABLE,
                [
                    '%code%' => $code,
                    '%sku%' => (string)$itemTransfer->getSku(),
                    '%storeName%' => $storeName,
                ],
            );

            return null;
        }

        if (count($matchedProductMeasurementSalesUnitTransfers) > 1) {
            $this->addIssue(
                $orderIntakeResponseTransfer,
                sprintf(static::FIELD_SALES_UNIT_CODE_PATTERN, $index),
                static::MESSAGE_UNIT_AMBIGUOUS,
                [
                    '%code%' => $code,
                    '%sku%' => (string)$itemTransfer->getSku(),
                ],
            );

            return null;
        }

        return $matchedProductMeasurementSalesUnitTransfers[0];
    }

    protected function applyDerivedQuantity(
        ItemTransfer $itemTransfer,
        ProductMeasurementSalesUnitTransfer $productMeasurementSalesUnitTransfer,
        float $amount,
        string $code,
        int $index,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): void {
        $conversion = (float)$productMeasurementSalesUnitTransfer->getConversion();
        $rawQuantity = $amount * $conversion;
        $derivedQuantity = (int)round($rawQuantity);

        if (abs($rawQuantity - $derivedQuantity) > static::QUANTITY_EPSILON) {
            $this->addIssue(
                $orderIntakeResponseTransfer,
                sprintf(static::FIELD_SALES_UNIT_AMOUNT_PATTERN, $index),
                static::MESSAGE_AMOUNT_NOT_WHOLE,
                [
                    '%amount%' => $this->formatAmount($amount),
                    '%code%' => $code,
                    '%rawQuantity%' => $this->formatAmount($rawQuantity),
                    '%step%' => $this->formatAmount(1 / $conversion),
                ],
            );

            return;
        }

        if ($derivedQuantity < 1) {
            $this->addIssue(
                $orderIntakeResponseTransfer,
                sprintf(static::FIELD_SALES_UNIT_AMOUNT_PATTERN, $index),
                static::MESSAGE_AMOUNT_NOT_POSITIVE,
                [
                    '%amount%' => $this->formatAmount($amount),
                    '%code%' => $code,
                ],
            );

            return;
        }

        $submittedQuantity = $itemTransfer->getQuantity();

        if ($submittedQuantity !== null && $submittedQuantity !== $derivedQuantity) {
            $this->addIssue(
                $orderIntakeResponseTransfer,
                sprintf(static::FIELD_QUANTITY_PATTERN, $index),
                static::MESSAGE_QUANTITY_CONTRADICTS_AMOUNT,
                [
                    '%quantity%' => (string)$submittedQuantity,
                    '%amount%' => $this->formatAmount($amount),
                    '%code%' => $code,
                    '%derivedQuantity%' => (string)$derivedQuantity,
                ],
            );

            return;
        }

        $itemTransfer->setQuantity($derivedQuantity);
    }

    /**
     * Trailing zeros dropped so a message reads "2" rather than "2.000000".
     */
    protected function formatAmount(float $amount): string
    {
        return rtrim(rtrim(number_format($amount, 6, '.', ''), '0'), '.');
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
