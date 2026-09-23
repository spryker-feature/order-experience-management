<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Expander;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\ProductOptionCollectionTransfer;
use Generated\Shared\Transfer\ProductOptionCriteriaTransfer;
use Generated\Shared\Transfer\ProductOptionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Shared\Price\PriceConfig;
use Spryker\Zed\ProductOption\Business\ProductOptionFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeProductOptionExpander;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Expander
 * @group OrderIntakeProductOptionExpanderTest
 * Add your own group annotations below this line
 */
class OrderIntakeProductOptionExpanderTest extends Unit
{
    protected const string ITEM_SKU = '001_25904006';

    protected const string OPTION_SKU = 'OP_gift_wrapping';

    protected const string OPTION_GROUP_NAME = 'Gift wrapping';

    protected const string OPTION_VALUE = 'Premium paper';

    protected const int OPTION_UNIT_PRICE = 500;

    protected const int ID_OPTION_VALUE = 77;

    protected const int ITEM_QUANTITY = 3;

    public function testExpandProductOptionsReplacesTheSubmittedStubWithTheCatalogueOption(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote([static::OPTION_SKU]);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createExpander()->expandProductOptions($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $productOptionTransfer = $quoteTransfer->getItems()->offsetGet(0)->getProductOptions()->offsetGet(0);

        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame(static::ID_OPTION_VALUE, $productOptionTransfer->getIdProductOptionValue());
        $this->assertSame(static::OPTION_GROUP_NAME, $productOptionTransfer->getGroupName());
        $this->assertSame(static::OPTION_VALUE, $productOptionTransfer->getValue());
        $this->assertSame(static::OPTION_UNIT_PRICE, $productOptionTransfer->getUnitPrice());
    }

    /**
     * The guard against a 500 rather than a nicety: `SumGrossPriceCalculator` calls
     * `ProductOptionTransfer::requireQuantity()`, so an option reaching `recalculateQuote()` without
     * a quantity throws. The cart gets this from a PostSave plugin intake never runs.
     *
     * @see \Spryker\Zed\Calculation\Business\Model\Calculator\GrossPrice\SumGrossPriceCalculator::assertProductOptionPriceCalculationRequirements()
     * @see \Spryker\Zed\ProductOptionCartConnector\Business\Model\ProductOptionCartQuantity::changeQuantity()
     */
    public function testExpandProductOptionsCarriesTheLineQuantityOntoEveryOption(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote([static::OPTION_SKU]);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createExpander()->expandProductOptions($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(
            static::ITEM_QUANTITY,
            $quoteTransfer->getItems()->offsetGet(0)->getProductOptions()->offsetGet(0)->getQuantity(),
        );
    }

    /**
     * The criteria excludes options whose group is inactive or not assigned to this line's product,
     * so anything the lookup does not return is invalid for one of those reasons and reports the
     * same way — the message names all three so an integrator knows where to look.
     */
    public function testExpandProductOptionsReportsAnOptionTheCatalogueDoesNotReturn(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(['OP_nope']);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createExpander()->expandProductOptions($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);

        $this->assertSame('items[0].productOptions[0].sku', $validationIssueTransfer->getField());
        $this->assertSame('OP_nope', $validationIssueTransfer->getParameters()['%productOptionSku%']);
    }

    /**
     * An option with no price in the order's store and currency would be added free of charge. The
     * storefront catches this in a CART pre-check, which intake never runs.
     *
     * @see \Spryker\Zed\ProductOptionCartConnector\Communication\Plugin\ProductOptionValuePriceExistsCartPreCheckPlugin
     */
    public function testExpandProductOptionsReportsAnOptionWithNoPriceInTheOrderCurrency(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote([static::OPTION_SKU]);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $expander = $this->createExpanderReturning(
            (new ProductOptionTransfer())
                ->setIdProductOptionValue(static::ID_OPTION_VALUE)
                ->setSku(static::OPTION_SKU)
                ->setUnitPrice(null),
        );

        // Act
        $expander->expandProductOptions($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);

        $this->assertSame('items[0].productOptions[0].sku', $validationIssueTransfer->getField());
        $this->assertSame(static::OPTION_SKU, $validationIssueTransfer->getParameters()['%productOptionSku%']);
    }

    /**
     * Rejected rather than deduplicated: the same option twice on one line would charge its
     * surcharge twice, so silently collapsing it would hide a caller-side mapping bug.
     */
    public function testExpandProductOptionsReportsTheSameOptionListedTwiceOnOneLine(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote([static::OPTION_SKU, static::OPTION_SKU]);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createExpander()->expandProductOptions($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);

        $this->assertSame('items[0].productOptions[1].sku', $validationIssueTransfer->getField());
        $this->assertSame(static::OPTION_SKU, $validationIssueTransfer->getParameters()['%productOptionSku%']);
    }

    /**
     * The criteria has to carry the line's own SKU, or an option belonging to some other product's
     * group would be accepted.
     */
    public function testExpandProductOptionsScopesTheLookupToTheLineProductAndActiveGroupsOnly(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote([static::OPTION_SKU]);
        $capturedCriteriaTransfer = null;

        $productOptionFacadeMock = $this->createMock(ProductOptionFacadeInterface::class);
        $productOptionFacadeMock
            ->method('getProductOptionCollectionByProductOptionCriteria')
            ->willReturnCallback(function (ProductOptionCriteriaTransfer $productOptionCriteriaTransfer) use (&$capturedCriteriaTransfer): ProductOptionCollectionTransfer {
                $capturedCriteriaTransfer = $productOptionCriteriaTransfer;

                return new ProductOptionCollectionTransfer();
            });

        // Act
        (new OrderIntakeProductOptionExpander($productOptionFacadeMock))
            ->expandProductOptions($quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertNotNull($capturedCriteriaTransfer);
        $this->assertSame([static::OPTION_SKU], $capturedCriteriaTransfer->getProductOptionValueSkus());
        $this->assertSame(static::ITEM_SKU, $capturedCriteriaTransfer->getProductConcreteSku());
        $this->assertTrue($capturedCriteriaTransfer->getProductOptionGroupIsActive());
        $this->assertSame('EUR', $capturedCriteriaTransfer->getCurrencyIsoCode());
        $this->assertSame(PriceConfig::PRICE_MODE_GROSS, $capturedCriteriaTransfer->getPriceMode());
    }

    /**
     * A line with no options must not cost a lookup — most B2B lines have none.
     */
    public function testExpandProductOptionsIgnoresALineWithNoOptions(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote([]);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $productOptionFacadeMock = $this->createMock(ProductOptionFacadeInterface::class);
        $productOptionFacadeMock->expects($this->never())->method('getProductOptionCollectionByProductOptionCriteria');

        // Act
        (new OrderIntakeProductOptionExpander($productOptionFacadeMock))
            ->expandProductOptions($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * Each line is looked up on its own, because the group-assignment filter is per product SKU.
     */
    public function testExpandProductOptionsNamesTheLineTheIssueBelongsTo(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote([static::OPTION_SKU]);
        $quoteTransfer->addItem(
            (new ItemTransfer())
                ->setSku('131_24872891')
                ->setQuantity(1)
                ->addProductOption((new ProductOptionTransfer())->setSku('OP_nope')),
        );
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createExpander()->expandProductOptions($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame(
            'items[1].productOptions[0].sku',
            $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField(),
        );
    }

    /**
     * Two lines ordering the same product with the same option must not cost the catalogue two
     * identical lookups.
     */
    public function testExpandProductOptionsCallsTheFacadeOnceForTwoLinesRepeatingTheSameSkuAndOption(): void
    {
        // Arrange
        $itemTransfer = (new ItemTransfer())
            ->setSku(static::ITEM_SKU)
            ->setQuantity(static::ITEM_QUANTITY)
            ->addProductOption((new ProductOptionTransfer())->setSku(static::OPTION_SKU));
        $repeatedItemTransfer = (new ItemTransfer())
            ->setSku(static::ITEM_SKU)
            ->setQuantity(static::ITEM_QUANTITY)
            ->addProductOption((new ProductOptionTransfer())->setSku(static::OPTION_SKU));

        $quoteTransfer = (new QuoteTransfer())
            ->setPriceMode(PriceConfig::PRICE_MODE_GROSS)
            ->setCurrency((new CurrencyTransfer())->setCode('EUR'))
            ->addItem($itemTransfer)
            ->addItem($repeatedItemTransfer);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $productOptionCollectionTransfer = (new ProductOptionCollectionTransfer())->addProductOption(
            (new ProductOptionTransfer())
                ->setIdProductOptionValue(static::ID_OPTION_VALUE)
                ->setSku(static::OPTION_SKU)
                ->setGroupName(static::OPTION_GROUP_NAME)
                ->setValue(static::OPTION_VALUE)
                ->setUnitPrice(static::OPTION_UNIT_PRICE),
        );

        $productOptionFacadeMock = $this->createMock(ProductOptionFacadeInterface::class);
        $productOptionFacadeMock->expects($this->once())
            ->method('getProductOptionCollectionByProductOptionCriteria')
            ->willReturn($productOptionCollectionTransfer);

        // Act
        (new OrderIntakeProductOptionExpander($productOptionFacadeMock))
            ->expandProductOptions($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame(
            static::ID_OPTION_VALUE,
            $itemTransfer->getProductOptions()->offsetGet(0)->getIdProductOptionValue(),
        );
        $this->assertSame(
            static::ID_OPTION_VALUE,
            $repeatedItemTransfer->getProductOptions()->offsetGet(0)->getIdProductOptionValue(),
        );
    }

    /**
     * @param array<int, string> $productOptionSkus
     */
    protected function createQuote(array $productOptionSkus): QuoteTransfer
    {
        $itemTransfer = (new ItemTransfer())
            ->setSku(static::ITEM_SKU)
            ->setQuantity(static::ITEM_QUANTITY);

        foreach ($productOptionSkus as $productOptionSku) {
            $itemTransfer->addProductOption((new ProductOptionTransfer())->setSku($productOptionSku));
        }

        return (new QuoteTransfer())
            ->setPriceMode(PriceConfig::PRICE_MODE_GROSS)
            ->setCurrency((new CurrencyTransfer())->setCode('EUR'))
            ->addItem($itemTransfer);
    }

    protected function createExpander(): OrderIntakeProductOptionExpander
    {
        return $this->createExpanderReturning(
            (new ProductOptionTransfer())
                ->setIdProductOptionValue(static::ID_OPTION_VALUE)
                ->setSku(static::OPTION_SKU)
                ->setGroupName(static::OPTION_GROUP_NAME)
                ->setValue(static::OPTION_VALUE)
                ->setUnitPrice(static::OPTION_UNIT_PRICE),
        );
    }

    protected function createExpanderReturning(ProductOptionTransfer $productOptionTransfer): OrderIntakeProductOptionExpander
    {
        $productOptionCollectionTransfer = (new ProductOptionCollectionTransfer())
            ->addProductOption($productOptionTransfer);

        $productOptionFacadeMock = $this->createMock(ProductOptionFacadeInterface::class);
        $productOptionFacadeMock
            ->method('getProductOptionCollectionByProductOptionCriteria')
            ->willReturn($productOptionCollectionTransfer);

        return new OrderIntakeProductOptionExpander($productOptionFacadeMock);
    }
}
