<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Expander;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Product\Business\ProductFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeItemExpander;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Expander
 * @group OrderIntakeItemExpanderTest
 * Add your own group annotations below this line
 */
class OrderIntakeItemExpanderTest extends Unit
{
    protected const string SKU = '131_24872891';

    protected const int ID_PRODUCT_ABSTRACT = 42;

    protected const string RESOLVED_NAME = 'Canon PowerShot SC620';

    /**
     * `ProductFacade::findProductConcretesBySkus()` never populates `localizedAttributes` — the
     * name must come from the batched `getProductAbstractLocalizedAttributeNamesIndexedByIdProductAbstract()`
     * lookup, not from reading that (always-empty) field. Regression test for that bug.
     */
    public function testExpandItemsSetsTheResolvedProductNameNotTheSku(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())->addItem((new ItemTransfer())->setSku(static::SKU));

        $productFacadeMock = $this->createMock(ProductFacadeInterface::class);
        $productFacadeMock->method('findProductConcretesBySkus')->willReturn([
            (new ProductConcreteTransfer())
                ->setSku(static::SKU)
                ->setFkProductAbstract(static::ID_PRODUCT_ABSTRACT),
        ]);
        $productFacadeMock->method('getProductAbstractLocalizedAttributeNamesIndexedByIdProductAbstract')
            ->willReturn([static::ID_PRODUCT_ABSTRACT => static::RESOLVED_NAME]);

        // Act
        $quoteTransfer = (new OrderIntakeItemExpander($productFacadeMock))
            ->expandItems($quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertSame(static::RESOLVED_NAME, $quoteTransfer->getItems()->offsetGet(0)->getName());
    }

    /**
     * A product abstract with no localized attribute name at all (e.g. import gap) must not leave
     * the item nameless — the SKU is the documented fallback.
     */
    public function testExpandItemsFallsBackToSkuWhenNoNameIsFound(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())->addItem((new ItemTransfer())->setSku(static::SKU));

        $productFacadeMock = $this->createMock(ProductFacadeInterface::class);
        $productFacadeMock->method('findProductConcretesBySkus')->willReturn([
            (new ProductConcreteTransfer())
                ->setSku(static::SKU)
                ->setFkProductAbstract(static::ID_PRODUCT_ABSTRACT),
        ]);
        $productFacadeMock->method('getProductAbstractLocalizedAttributeNamesIndexedByIdProductAbstract')->willReturn([]);

        // Act
        $quoteTransfer = (new OrderIntakeItemExpander($productFacadeMock))
            ->expandItems($quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertSame(static::SKU, $quoteTransfer->getItems()->offsetGet(0)->getName());
    }

    public function testExpandItemsReportsAValidationIssueForAnUnknownSku(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())->addItem((new ItemTransfer())->setSku(static::SKU));
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $productFacadeMock = $this->createMock(ProductFacadeInterface::class);
        $productFacadeMock->method('findProductConcretesBySkus')->willReturn([]);
        $productFacadeMock->expects($this->never())->method('getProductAbstractLocalizedAttributeNamesIndexedByIdProductAbstract');

        // Act
        (new OrderIntakeItemExpander($productFacadeMock))->expandItems($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame(static::SKU, $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getParameters()['%sku%']);
        $this->assertSame('items[0].sku', $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField());
    }

    /**
     * An order can carry fifty lines; "Product with SKU X was not found" on its own does not say
     * which one, and the caller has to match the SKU by hand. Every other per-line issue in intake
     * names its index — this one was the exception.
     */
    public function testExpandItemsNamesTheOffendingLineByIndex(): void
    {
        // Arrange — line 0 resolves, line 1 does not.
        $quoteTransfer = (new QuoteTransfer())
            ->addItem((new ItemTransfer())->setSku(static::SKU))
            ->addItem((new ItemTransfer())->setSku('unknown-sku'));
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $productFacadeMock = $this->createMock(ProductFacadeInterface::class);
        $productFacadeMock->method('findProductConcretesBySkus')->willReturn([
            (new ProductConcreteTransfer())->setSku(static::SKU)->setFkProductAbstract(1)->setIdProductConcrete(1),
        ]);
        $productFacadeMock->method('getProductAbstractLocalizedAttributeNamesIndexedByIdProductAbstract')->willReturn([]);

        // Act
        (new OrderIntakeItemExpander($productFacadeMock))->expandItems($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame('items[1].sku', $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField());
    }

    public function testExpandItemsGivesLinesOfOneSkuFromDifferentOffersDistinctGroupKeys(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->addItem((new ItemTransfer())->setSku(static::SKU)->setProductOfferReference('offer-1')->setMerchantReference('MER000001'))
            ->addItem((new ItemTransfer())->setSku(static::SKU)->setProductOfferReference('offer-2')->setMerchantReference('MER000002'));

        // Act
        $quoteTransfer = (new OrderIntakeItemExpander($this->createProductFacadeMock()))
            ->expandItems($quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertNotSame(
            $quoteTransfer->getItems()->offsetGet(0)->getGroupKey(),
            $quoteTransfer->getItems()->offsetGet(1)->getGroupKey(),
        );
    }

    public function testExpandItemsGivesLinesOfOneSkuAtDifferentCustomPricesDistinctGroupKeys(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->addItem((new ItemTransfer())->setSku(static::SKU)->setSourceUnitGrossPrice(1000))
            ->addItem((new ItemTransfer())->setSku(static::SKU)->setSourceUnitGrossPrice(1500));

        // Act
        $quoteTransfer = (new OrderIntakeItemExpander($this->createProductFacadeMock()))
            ->expandItems($quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertNotSame(
            $quoteTransfer->getItems()->offsetGet(0)->getGroupKey(),
            $quoteTransfer->getItems()->offsetGet(1)->getGroupKey(),
        );
    }

    public function testExpandItemsUsesTheSkuAsGroupKeyForAPlainCatalogLine(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())->addItem((new ItemTransfer())->setSku(static::SKU));

        // Act
        $quoteTransfer = (new OrderIntakeItemExpander($this->createProductFacadeMock()))
            ->expandItems($quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertSame(static::SKU, $quoteTransfer->getItems()->offsetGet(0)->getGroupKey());
    }

    protected function createProductFacadeMock(): ProductFacadeInterface
    {
        $productFacadeMock = $this->createMock(ProductFacadeInterface::class);
        $productFacadeMock->method('findProductConcretesBySkus')->willReturn([
            (new ProductConcreteTransfer())
                ->setSku(static::SKU)
                ->setFkProductAbstract(static::ID_PRODUCT_ABSTRACT),
        ]);
        $productFacadeMock->method('getProductAbstractLocalizedAttributeNamesIndexedByIdProductAbstract')->willReturn([]);

        return $productFacadeMock;
    }
}
