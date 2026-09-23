<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Expander;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderIntakeItemTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeSalesUnitTransfer;
use Generated\Shared\Transfer\ProductMeasurementBaseUnitTransfer;
use Generated\Shared\Transfer\ProductMeasurementSalesUnitTransfer;
use Generated\Shared\Transfer\ProductMeasurementUnitTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StoreRelationTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeSalesUnitExpander;
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
 * @group OrderIntakeSalesUnitExpanderTest
 * Add your own group annotations below this line
 */
class OrderIntakeSalesUnitExpanderTest extends Unit
{
    protected const string ITEM_SKU = '215_123';

    protected const int ID_PRODUCT_CONCRETE = 297;

    protected const string UNIT_CODE = 'METR';

    protected const string UNIT_NAME = 'Metre';

    protected const string BASE_UNIT_NAME = 'Item';

    protected const string STORE_NAME = 'DE';

    protected const float CONVERSION = 5.0;

    protected const int PRECISION = 100;

    public function testExpandSalesUnitsResolvesTheCodeIntoTheCatalogueSalesUnit(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote();
        $orderIntakeRequestTransfer = $this->createRequest(static::UNIT_CODE, 2.0);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createExpander()->expandSalesUnits($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $quantitySalesUnitTransfer = $quoteTransfer->getItems()->offsetGet(0)->getQuantitySalesUnit();

        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertNotNull($quantitySalesUnitTransfer);
        $this->assertSame(static::UNIT_CODE, $quantitySalesUnitTransfer->getProductMeasurementUnit()->getCode());
        $this->assertSame(static::CONVERSION, $quantitySalesUnitTransfer->getConversion());
        $this->assertSame(static::PRECISION, $quantitySalesUnitTransfer->getPrecision());
    }

    /**
     * The point of the feature: the caller says "2 metres" and the base-unit quantity the whole
     * platform runs on is worked out here, not by the caller.
     */
    public function testExpandSalesUnitsDerivesTheBaseQuantityFromTheAmount(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote();
        $orderIntakeRequestTransfer = $this->createRequest(static::UNIT_CODE, 2.0);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createExpander()->expandSalesUnits($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(10, $quoteTransfer->getItems()->offsetGet(0)->getQuantity());
    }

    /**
     * `quantity` is an integer column. Rounding 2.5 base units to 3 would place an order for
     * something the caller did not ask for, so it is rejected instead.
     */
    public function testExpandSalesUnitsRejectsAnAmountThatIsNotAWholeNumberOfBaseUnits(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote();
        $orderIntakeRequestTransfer = $this->createRequest(static::UNIT_CODE, 2.5);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $expander = $this->createExpander(static::UNIT_CODE, 0.3);

        // Act
        $expander->expandSalesUnits($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);

        $this->assertSame('items[0].salesUnit.amount', $validationIssueTransfer->getField());
    }

    /**
     * The silent-wrong-data case this slice exists to close: an ERP sending "2 pallets" AND
     * `quantity: 2` has forgotten the conversion, and would otherwise get 2 base units.
     */
    public function testExpandSalesUnitsRejectsAQuantityThatContradictsTheAmount(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(2);
        $orderIntakeRequestTransfer = $this->createRequest(static::UNIT_CODE, 2.0);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createExpander()->expandSalesUnits($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);

        $this->assertSame('items[0].quantity', $validationIssueTransfer->getField());
        $this->assertSame(2, $quoteTransfer->getItems()->offsetGet(0)->getQuantity());
    }

    /**
     * A storefront-shaped payload — both numbers, already consistent — behaves exactly as it does
     * against the cart API.
     */
    public function testExpandSalesUnitsAcceptsAQuantityThatAgreesWithTheAmount(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(10);
        $orderIntakeRequestTransfer = $this->createRequest(static::UNIT_CODE, 2.0);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createExpander()->expandSalesUnits($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame(10, $quoteTransfer->getItems()->offsetGet(0)->getQuantity());
    }

    /**
     * Naming the unit without an amount is the storefront's own model: the quantity stands, the unit
     * is recorded so the order reads back in the caller's terms.
     */
    public function testExpandSalesUnitsLeavesTheQuantityAloneWhenNoAmountIsGiven(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(7);
        $orderIntakeRequestTransfer = $this->createRequest(static::UNIT_CODE, null);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createExpander()->expandSalesUnits($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame(7, $quoteTransfer->getItems()->offsetGet(0)->getQuantity());
        $this->assertNotNull($quoteTransfer->getItems()->offsetGet(0)->getQuantitySalesUnit());
    }

    public function testExpandSalesUnitsReportsACodeThatIsNotASalesUnitOfTheProduct(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote();
        $orderIntakeRequestTransfer = $this->createRequest('NOPE', 2.0);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createExpander()->expandSalesUnits($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);

        $this->assertSame('items[0].salesUnit.code', $validationIssueTransfer->getField());
        $this->assertSame('NOPE', $validationIssueTransfer->getParameters()['%code%']);
    }

    /**
     * A sales unit is restricted to the stores it is related to. `getSalesUnitsByIdProduct()` returns
     * every store's units, so the filtering happens here — the storefront gets it from a CART
     * pre-check that intake never runs.
     *
     * @see \Spryker\Zed\ProductMeasurementUnit\Communication\Plugin\Cart\ProductMeasurementSalesUnitCartPreCheckPlugin
     */
    public function testExpandSalesUnitsReportsASalesUnitNotAvailableInTheOrderStore(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote();
        $orderIntakeRequestTransfer = $this->createRequest(static::UNIT_CODE, 2.0);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $expander = $this->createExpander(static::UNIT_CODE, static::CONVERSION, 'AT');

        // Act
        $expander->expandSalesUnits($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);

        $this->assertSame('items[0].salesUnit.code', $validationIssueTransfer->getField());
        $this->assertSame(static::STORE_NAME, $validationIssueTransfer->getParameters()['%storeName%']);
    }

    /**
     * `(fk_product, fk_product_measurement_unit)` carries no unique constraint, so two sales units
     * can in principle share a code for one product. Picking one arbitrarily would order against an
     * unpredictable conversion.
     */
    public function testExpandSalesUnitsReportsAnAmbiguousCode(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote();
        $orderIntakeRequestTransfer = $this->createRequest(static::UNIT_CODE, 2.0);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $productMeasurementSalesUnitCodeResolverMock = $this->createMock(ProductMeasurementSalesUnitCodeResolverInterface::class);
        $productMeasurementSalesUnitCodeResolverMock->method('findSalesUnitsByCode')->willReturn([
            $this->createSalesUnit(static::UNIT_CODE, static::CONVERSION, static::STORE_NAME),
            $this->createSalesUnit(static::UNIT_CODE, 9.0, static::STORE_NAME),
        ]);

        // Act
        (new OrderIntakeSalesUnitExpander($productMeasurementSalesUnitCodeResolverMock))
            ->expandSalesUnits($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(
            'items[0].salesUnit.code',
            $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField(),
        );
    }

    /**
     * A line sold in base units must not cost a catalogue lookup.
     */
    public function testExpandSalesUnitsIgnoresALineWithNoSalesUnit(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote();
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())
            ->addItem((new OrderIntakeItemTransfer())->setSku(static::ITEM_SKU)->setQuantity(5));
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $productMeasurementSalesUnitCodeResolverMock = $this->createMock(ProductMeasurementSalesUnitCodeResolverInterface::class);
        $productMeasurementSalesUnitCodeResolverMock->expects($this->never())->method('findSalesUnitsByCode');

        // Act
        (new OrderIntakeSalesUnitExpander($productMeasurementSalesUnitCodeResolverMock))
            ->expandSalesUnits($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * Two lines ordering the same product in the same unit must not cost the catalogue two identical
     * lookups.
     */
    public function testExpandSalesUnitsCallsTheResolverOnceForTwoLinesRepeatingTheSameProductAndCode(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->setStore((new StoreTransfer())->setName(static::STORE_NAME))
            ->addItem((new ItemTransfer())->setSku(static::ITEM_SKU)->setId(static::ID_PRODUCT_CONCRETE)->setQuantity(10))
            ->addItem((new ItemTransfer())->setSku(static::ITEM_SKU)->setId(static::ID_PRODUCT_CONCRETE)->setQuantity(10));

        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())
            ->addItem(
                (new OrderIntakeItemTransfer())
                    ->setSku(static::ITEM_SKU)
                    ->setSalesUnit((new OrderIntakeSalesUnitTransfer())->setCode(static::UNIT_CODE)),
            )
            ->addItem(
                (new OrderIntakeItemTransfer())
                    ->setSku(static::ITEM_SKU)
                    ->setSalesUnit((new OrderIntakeSalesUnitTransfer())->setCode(static::UNIT_CODE)),
            );
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $productMeasurementSalesUnitCodeResolverMock = $this->createMock(ProductMeasurementSalesUnitCodeResolverInterface::class);
        $productMeasurementSalesUnitCodeResolverMock->expects($this->once())
            ->method('findSalesUnitsByCode')
            ->willReturn([$this->createSalesUnit(static::UNIT_CODE, static::CONVERSION, static::STORE_NAME)]);

        // Act
        (new OrderIntakeSalesUnitExpander($productMeasurementSalesUnitCodeResolverMock))
            ->expandSalesUnits($orderIntakeRequestTransfer, $quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertNotNull($quoteTransfer->getItems()->offsetGet(0)->getQuantitySalesUnit());
        $this->assertNotNull($quoteTransfer->getItems()->offsetGet(1)->getQuantitySalesUnit());
    }

    protected function createQuote(?int $quantity = null): QuoteTransfer
    {
        return (new QuoteTransfer())
            ->setStore((new StoreTransfer())->setName(static::STORE_NAME))
            ->addItem(
                (new ItemTransfer())
                    ->setSku(static::ITEM_SKU)
                    ->setId(static::ID_PRODUCT_CONCRETE)
                    ->setQuantity($quantity),
            );
    }

    protected function createRequest(string $code, ?float $amount): OrderIntakeRequestTransfer
    {
        return (new OrderIntakeRequestTransfer())->addItem(
            (new OrderIntakeItemTransfer())
                ->setSku(static::ITEM_SKU)
                ->setSalesUnit(
                    (new OrderIntakeSalesUnitTransfer())->setCode($code)->setAmount($amount),
                ),
        );
    }

    protected function createExpander(
        string $code = self::UNIT_CODE,
        float $conversion = self::CONVERSION,
        string $storeName = self::STORE_NAME,
    ): OrderIntakeSalesUnitExpander {
        $productMeasurementSalesUnitCodeResolverMock = $this->createMock(ProductMeasurementSalesUnitCodeResolverInterface::class);
        $productMeasurementSalesUnitCodeResolverMock
            ->method('findSalesUnitsByCode')
            ->willReturnCallback(
                fn (int $idProductConcrete, string $requestedCode, string $requestedStoreName): array => $requestedCode === $code && $requestedStoreName === $storeName
                        ? [$this->createSalesUnit($code, $conversion, $storeName)]
                        : [],
            );

        return new OrderIntakeSalesUnitExpander($productMeasurementSalesUnitCodeResolverMock);
    }

    protected function createSalesUnit(
        string $code,
        float $conversion,
        string $storeName,
    ): ProductMeasurementSalesUnitTransfer {
        return (new ProductMeasurementSalesUnitTransfer())
            ->setConversion($conversion)
            ->setPrecision(static::PRECISION)
            ->setProductMeasurementUnit(
                (new ProductMeasurementUnitTransfer())->setCode($code)->setName(static::UNIT_NAME),
            )
            ->setProductMeasurementBaseUnit(
                (new ProductMeasurementBaseUnitTransfer())->setProductMeasurementUnit(
                    (new ProductMeasurementUnitTransfer())->setName(static::BASE_UNIT_NAME),
                ),
            )
            ->setStoreRelation(
                (new StoreRelationTransfer())->addStores((new StoreTransfer())->setName($storeName)),
            );
    }
}
