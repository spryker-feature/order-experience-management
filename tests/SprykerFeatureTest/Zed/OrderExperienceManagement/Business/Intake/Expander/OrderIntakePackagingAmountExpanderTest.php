<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Expander;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderIntakeItemTransfer;
use Generated\Shared\Transfer\OrderIntakePackagingAmountTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductMeasurementBaseUnitTransfer;
use Generated\Shared\Transfer\ProductMeasurementSalesUnitTransfer;
use Generated\Shared\Transfer\ProductMeasurementUnitTransfer;
use Generated\Shared\Transfer\ProductPackagingUnitAmountTransfer;
use Generated\Shared\Transfer\ProductPackagingUnitTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\DecimalObject\Decimal;
use Spryker\Zed\Product\Business\ProductFacadeInterface;
use Spryker\Zed\ProductPackagingUnit\Business\ProductPackagingUnitFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakePackagingAmountExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\ProductMeasurementSalesUnitCodeResolverInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Expander
 * @group OrderIntakePackagingAmountExpanderTest
 * Add your own group annotations below this line
 */
class OrderIntakePackagingAmountExpanderTest extends Unit
{
    protected const string PACKAGE_SKU = '218_1234';

    protected const string LEAD_SKU = '218_123';

    protected const int ID_LEAD_PRODUCT = 555;

    protected const string UNIT_CODE = 'KILO';

    protected const string STORE_NAME = 'DE';

    protected const float DEFAULT_AMOUNT = 100.0;

    /**
     * The core semantic this slice hinges on: the platform stores the LINE total, the caller states
     * the PER-PACKAGE figure. 3 boxes of 250 is `quantity: 3`, `amount: 250`, and the item carries 750.
     *
     * @see \Spryker\Client\ProductPackagingUnitStorage\Expander\ItemTransferExpander::expandWithDefaultPackagingUnit()
     */
    public function testExpandPackagingAmountsMultipliesThePerPackageAmountByTheLineQuantity(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(3);
        $orderIntakeRequestTransfer = $this->createRequest(250.0, static::UNIT_CODE);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createExpander()->expandPackagingAmounts($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertTrue(
            $quoteTransfer->getItems()->offsetGet(0)->getAmount()->equals(Decimal::create(750)),
        );
    }

    /**
     * Omitting the amount orders the package as configured.
     */
    public function testExpandPackagingAmountsFallsBackToTheConfiguredDefaultAmount(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(2);
        $orderIntakeRequestTransfer = $this->createRequest(null, static::UNIT_CODE);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createExpander()->expandPackagingAmounts($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertTrue(
            $quoteTransfer->getItems()->offsetGet(0)->getAmount()->equals(Decimal::create(200)),
        );
    }

    public function testExpandPackagingAmountsReportsAnAmountBelowTheMinimum(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(1);
        $orderIntakeRequestTransfer = $this->createRequest(50.0, static::UNIT_CODE);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createExpander()->expandPackagingAmounts($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(
            'items[0].packagingAmount.amount',
            $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField(),
        );
    }

    public function testExpandPackagingAmountsReportsAnAmountAboveTheMaximum(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(1);
        $orderIntakeRequestTransfer = $this->createRequest(5000.0, static::UNIT_CODE);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createExpander()->expandPackagingAmounts($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(
            'items[0].packagingAmount.amount',
            $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField(),
        );
    }

    /**
     * The package steps in tens; 105 is between two orderable amounts.
     */
    public function testExpandPackagingAmountsReportsAnAmountOffTheConfiguredInterval(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(1);
        $orderIntakeRequestTransfer = $this->createRequest(105.0, static::UNIT_CODE);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createExpander()->expandPackagingAmounts($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(
            'items[0].packagingAmount.amount',
            $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField(),
        );
    }

    /**
     * A fixed-size package cannot be ordered at another amount — its price and its stock are defined
     * for the configured one.
     */
    public function testExpandPackagingAmountsReportsACustomAmountOnAFixedSizePackage(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(1);
        $orderIntakeRequestTransfer = $this->createRequest(250.0, static::UNIT_CODE);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $expander = $this->createExpander(isAmountVariable: false);

        // Act
        $expander->expandPackagingAmounts($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(
            'items[0].packagingAmount.amount',
            $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField(),
        );
    }

    /**
     * The amount is expressed in a unit of the CONTAINED product, so the code resolves against the
     * lead product — not the package the line names.
     *
     * @see \Spryker\Client\ProductPackagingUnitStorage\Expander\ItemTransferExpander::expandWithDefaultPackagingUnit()
     */
    public function testExpandPackagingAmountsResolvesTheSalesUnitCodeAgainstTheLeadProduct(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(1);
        $orderIntakeRequestTransfer = $this->createRequest(100.0, static::UNIT_CODE);
        $capturedIdProduct = null;

        $productMeasurementSalesUnitCodeResolverMock = $this->createMock(ProductMeasurementSalesUnitCodeResolverInterface::class);
        $productMeasurementSalesUnitCodeResolverMock
            ->method('findSalesUnitsByCode')
            ->willReturnCallback(function (int $idProductConcrete) use (&$capturedIdProduct): array {
                $capturedIdProduct = $idProductConcrete;

                return [$this->createSalesUnit(static::UNIT_CODE)];
            });

        // Act
        $this->createExpander(salesUnitCodeResolver: $productMeasurementSalesUnitCodeResolverMock)
            ->expandPackagingAmounts($orderIntakeRequestTransfer, $quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertSame(static::ID_LEAD_PRODUCT, $capturedIdProduct);
    }

    public function testExpandPackagingAmountsReportsASalesUnitCodeTheLeadProductDoesNotHave(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(1);
        $orderIntakeRequestTransfer = $this->createRequest(100.0, 'NOPE');
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $productMeasurementSalesUnitCodeResolverMock = $this->createMock(ProductMeasurementSalesUnitCodeResolverInterface::class);
        $productMeasurementSalesUnitCodeResolverMock->method('findSalesUnitsByCode')->willReturn([]);

        // Act
        $this->createExpander(salesUnitCodeResolver: $productMeasurementSalesUnitCodeResolverMock)
            ->expandPackagingAmounts($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(
            'items[0].packagingAmount.salesUnitCode',
            $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField(),
        );
    }

    /**
     * Naming a packaging amount on a product that is not sold as a package means the caller has the
     * wrong SKU; accepting it would place an order the package rules never checked.
     */
    public function testExpandPackagingAmountsReportsAPackagingAmountOnANonPackagedProduct(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(1);
        $orderIntakeRequestTransfer = $this->createRequest(100.0, static::UNIT_CODE);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $productPackagingUnitFacadeMock = $this->createMock(ProductPackagingUnitFacadeInterface::class);
        $productPackagingUnitFacadeMock
            ->method('expandCartChangeWithProductPackagingUnit')
            ->willReturnArgument(0);

        $expander = $this->createExpander(productPackagingUnitFacade: $productPackagingUnitFacadeMock);

        // Act
        $expander->expandPackagingAmounts($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(
            'items[0].packagingAmount',
            $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField(),
        );
    }

    /**
     * A bulk product is its own lead product, so quantity and amount count the same thing and a unit
     * on both would state it twice.
     *
     * @see \Spryker\Zed\ProductPackagingUnit\Business\Model\CartChange\AmountSalesUnitItemExpander::expandCartWithAmountSalesUnit()
     */
    public function testExpandPackagingAmountsDropsTheQuantitySalesUnitOfASelfLeadProduct(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(1);
        $quoteTransfer->getItems()->offsetGet(0)
            ->setSku(static::LEAD_SKU)
            ->setQuantitySalesUnit($this->createSalesUnit(static::UNIT_CODE));
        $orderIntakeRequestTransfer = $this->createRequest(100.0, static::UNIT_CODE);
        $orderIntakeRequestTransfer->getItems()->offsetGet(0)->setSku(static::LEAD_SKU);

        // Act
        $this->createExpander()->expandPackagingAmounts($orderIntakeRequestTransfer, $quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertNull($quoteTransfer->getItems()->offsetGet(0)->getQuantitySalesUnit());
    }

    /**
     * A line with no packaging amount must not cost a lookup.
     */
    public function testExpandPackagingAmountsIgnoresALineWithoutAPackagingAmount(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(1);
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())
            ->addItem((new OrderIntakeItemTransfer())->setSku(static::PACKAGE_SKU)->setQuantity(1));
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $productPackagingUnitFacadeMock = $this->createMock(ProductPackagingUnitFacadeInterface::class);
        $productPackagingUnitFacadeMock->expects($this->never())->method('expandCartChangeWithProductPackagingUnit');

        // Act
        $this->createExpander(productPackagingUnitFacade: $productPackagingUnitFacadeMock)
            ->expandPackagingAmounts($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * A variable-amount package priced for its default amount has to be repriced for the amount
     * actually ordered, or a 250-unit box would be sold at the 100-unit price.
     *
     * @see \Spryker\Zed\ProductPackagingUnit\Business\Model\PriceChange\PriceChangeExpander
     */
    public function testExpandPackagingAmountsRepricesTheLineForTheOrderedAmount(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(1);
        $orderIntakeRequestTransfer = $this->createRequest(250.0, static::UNIT_CODE);
        $wasRepriced = false;

        $productPackagingUnitFacadeMock = $this->createMock(ProductPackagingUnitFacadeInterface::class);
        $productPackagingUnitFacadeMock
            ->method('expandCartChangeWithProductPackagingUnit')
            ->willReturnCallback(fn (CartChangeTransfer $cartChangeTransfer): CartChangeTransfer => $this->applyPackagingUnit($cartChangeTransfer));
        $productPackagingUnitFacadeMock
            ->method('setCustomAmountPrice')
            ->willReturnCallback(function (CartChangeTransfer $cartChangeTransfer) use (&$wasRepriced): CartChangeTransfer {
                $wasRepriced = true;

                return $cartChangeTransfer;
            });

        // Act
        $this->createExpander(productPackagingUnitFacade: $productPackagingUnitFacadeMock)
            ->expandPackagingAmounts($orderIntakeRequestTransfer, $quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertTrue($wasRepriced);
    }

    /**
     * Two lines packaging the same lead product in the same unit must not cost the catalogue two
     * identical sales-unit-code lookups.
     */
    public function testExpandPackagingAmountsCallsTheSalesUnitCodeResolverOnceForTwoLinesRepeatingTheSameLeadProductAndCode(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->setStore((new StoreTransfer())->setName(static::STORE_NAME))
            ->addItem((new ItemTransfer())->setSku(static::PACKAGE_SKU)->setId(999)->setQuantity(1))
            ->addItem((new ItemTransfer())->setSku(static::PACKAGE_SKU)->setId(998)->setQuantity(1));

        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())
            ->addItem(
                (new OrderIntakeItemTransfer())
                    ->setSku(static::PACKAGE_SKU)
                    ->setPackagingAmount((new OrderIntakePackagingAmountTransfer())->setAmount(100.0)->setSalesUnitCode(static::UNIT_CODE)),
            )
            ->addItem(
                (new OrderIntakeItemTransfer())
                    ->setSku(static::PACKAGE_SKU)
                    ->setPackagingAmount((new OrderIntakePackagingAmountTransfer())->setAmount(100.0)->setSalesUnitCode(static::UNIT_CODE)),
            );
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $productMeasurementSalesUnitCodeResolverMock = $this->createMock(ProductMeasurementSalesUnitCodeResolverInterface::class);
        $productMeasurementSalesUnitCodeResolverMock->expects($this->once())
            ->method('findSalesUnitsByCode')
            ->willReturn([$this->createSalesUnit(static::UNIT_CODE)]);

        // Act
        $this->createExpander(salesUnitCodeResolver: $productMeasurementSalesUnitCodeResolverMock)
            ->expandPackagingAmounts($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    protected function createQuote(int $quantity): QuoteTransfer
    {
        return (new QuoteTransfer())
            ->setStore((new StoreTransfer())->setName(static::STORE_NAME))
            ->addItem(
                (new ItemTransfer())
                    ->setSku(static::PACKAGE_SKU)
                    ->setId(999)
                    ->setQuantity($quantity),
            );
    }

    protected function createRequest(?float $amount, ?string $salesUnitCode): OrderIntakeRequestTransfer
    {
        return (new OrderIntakeRequestTransfer())->addItem(
            (new OrderIntakeItemTransfer())
                ->setSku(static::PACKAGE_SKU)
                ->setPackagingAmount(
                    (new OrderIntakePackagingAmountTransfer())->setAmount($amount)->setSalesUnitCode($salesUnitCode),
                ),
        );
    }

    protected function createExpander(
        bool $isAmountVariable = true,
        ?ProductPackagingUnitFacadeInterface $productPackagingUnitFacade = null,
        ?ProductMeasurementSalesUnitCodeResolverInterface $salesUnitCodeResolver = null,
    ): OrderIntakePackagingAmountExpander {
        if ($productPackagingUnitFacade === null) {
            $productPackagingUnitFacade = $this->createMock(ProductPackagingUnitFacadeInterface::class);
            $productPackagingUnitFacade
                ->method('expandCartChangeWithProductPackagingUnit')
                ->willReturnCallback(fn (CartChangeTransfer $cartChangeTransfer): CartChangeTransfer => $this->applyPackagingUnit($cartChangeTransfer, $isAmountVariable));
            $productPackagingUnitFacade->method('setCustomAmountPrice')->willReturnArgument(0);
        }

        if ($salesUnitCodeResolver === null) {
            $salesUnitCodeResolver = $this->createMock(ProductMeasurementSalesUnitCodeResolverInterface::class);
            $salesUnitCodeResolver->method('findSalesUnitsByCode')->willReturn([$this->createSalesUnit(static::UNIT_CODE)]);
        }

        $productFacadeMock = $this->createMock(ProductFacadeInterface::class);
        $productFacadeMock
            ->method('getProductConcreteIdsByConcreteSkus')
            ->willReturn([static::LEAD_SKU => static::ID_LEAD_PRODUCT]);

        return new OrderIntakePackagingAmountExpander($productPackagingUnitFacade, $salesUnitCodeResolver, $productFacadeMock);
    }

    protected function applyPackagingUnit(CartChangeTransfer $cartChangeTransfer, bool $isAmountVariable = true): CartChangeTransfer
    {
        foreach ($cartChangeTransfer->getItems() as $itemTransfer) {
            $itemTransfer
                ->setAmountLeadProduct(
                    (new ProductConcreteTransfer())->setSku(static::LEAD_SKU)->setIdProductConcrete(static::ID_LEAD_PRODUCT),
                )
                ->setProductPackagingUnit(
                    (new ProductPackagingUnitTransfer())->setProductPackagingUnitAmount(
                        (new ProductPackagingUnitAmountTransfer())
                            ->setIsAmountVariable($isAmountVariable)
                            ->setDefaultAmount(Decimal::create(static::DEFAULT_AMOUNT))
                            ->setAmountMin(Decimal::create(100))
                            ->setAmountMax(Decimal::create(1000))
                            ->setAmountInterval(Decimal::create(10)),
                    ),
                );
        }

        return $cartChangeTransfer;
    }

    protected function createSalesUnit(string $code): ProductMeasurementSalesUnitTransfer
    {
        return (new ProductMeasurementSalesUnitTransfer())
            ->setConversion(1.0)
            ->setPrecision(1000)
            ->setProductMeasurementUnit(
                (new ProductMeasurementUnitTransfer())->setCode($code)->setName('Kilo'),
            )
            ->setProductMeasurementBaseUnit(
                (new ProductMeasurementBaseUnitTransfer())->setProductMeasurementUnit(
                    (new ProductMeasurementUnitTransfer())->setName('Gram'),
                ),
            );
    }
}
