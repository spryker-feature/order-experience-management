<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Resolver;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\MoneyValueTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use Generated\Shared\Transfer\PriceProductFilterTransfer;
use Generated\Shared\Transfer\PriceProductTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Service\PriceProduct\PriceProductServiceInterface;
use Spryker\Zed\PriceProduct\Business\PriceProductFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\OrderIntakePriceResolver;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Resolver
 * @group OrderIntakePriceResolverTest
 * Add your own group annotations below this line
 */
class OrderIntakePriceResolverTest extends Unit
{
    protected const string SKU = '131_24872891';

    protected const int CATALOGUE_GROSS_PRICE = 7365;

    protected const int CATALOGUE_NET_PRICE = 6629;

    protected const int SUBMITTED_PRICE = 9999;

    public function testResolvePricesFillsAnItemTheCallerDidNotPrice(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote('GROSS_MODE', [new ItemTransfer()]);

        // Act
        $quoteTransfer = $this->createResolver()->resolvePrices($quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $itemTransfer = $quoteTransfer->getItems()->offsetGet(0);

        $this->assertSame(static::CATALOGUE_GROSS_PRICE, $itemTransfer->getUnitGrossPrice());
        $this->assertSame(0, $itemTransfer->getUnitNetPrice());
    }

    /**
     * The whole point of keeping the field: a negotiated price must survive, and it must beat every
     * price dimension the catalogue could have applied.
     */
    public function testResolvePricesLetsASourcePriceBeatTheCataloguePrice(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote('GROSS_MODE', [
            (new ItemTransfer())->setSourceUnitGrossPrice(static::SUBMITTED_PRICE),
        ]);

        // Act
        $quoteTransfer = $this->createResolver()->resolvePrices($quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertSame(static::SUBMITTED_PRICE, $quoteTransfer->getItems()->offsetGet(0)->getUnitGrossPrice());
    }

    /**
     * The reason every line is now resolved: the catalogue figure is kept beside the charged one, so
     * the line records what this buyer would otherwise have paid.
     */
    public function testResolvePricesKeepsTheCataloguePriceAsTheOriginPriceBesideAnOverride(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote('GROSS_MODE', [
            (new ItemTransfer())->setSourceUnitGrossPrice(static::SUBMITTED_PRICE),
        ]);

        // Act
        $quoteTransfer = $this->createResolver()->resolvePrices($quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $itemTransfer = $quoteTransfer->getItems()->offsetGet(0);

        $this->assertSame(static::SUBMITTED_PRICE, $itemTransfer->getUnitGrossPrice());
        $this->assertSame(static::CATALOGUE_GROSS_PRICE, $itemTransfer->getOriginUnitGrossPrice());
    }

    /**
     * A product the catalogue cannot price is not a reason to reject a line the caller priced.
     */
    public function testResolvePricesAcceptsASourcePriceForAProductTheCatalogueCannotPrice(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote('GROSS_MODE', [
            (new ItemTransfer())->setSourceUnitGrossPrice(static::SUBMITTED_PRICE),
        ]);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $quoteTransfer = $this->createResolver(null)->resolvePrices($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(static::SUBMITTED_PRICE, $quoteTransfer->getItems()->offsetGet(0)->getUnitGrossPrice());
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * Mixed quotes are the realistic case: some lines carry a contract price, the rest fall back to
     * the catalogue.
     */
    public function testResolvePricesFillsOnlyTheUnpricedItemsOfAMixedQuote(): void
    {
        // Arrange
        $pricedItemTransfer = (new ItemTransfer())->setSourceUnitGrossPrice(static::SUBMITTED_PRICE);
        $quoteTransfer = $this->createQuote('GROSS_MODE', [$pricedItemTransfer, new ItemTransfer()]);

        // Act
        $quoteTransfer = $this->createResolver()->resolvePrices($quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertSame(static::SUBMITTED_PRICE, $quoteTransfer->getItems()->offsetGet(0)->getUnitGrossPrice());
        $this->assertSame(static::CATALOGUE_GROSS_PRICE, $quoteTransfer->getItems()->offsetGet(1)->getUnitGrossPrice());
    }

    /**
     * Only the field for the store's price mode carries a figure; claiming a gross amount and a net
     * amount are the same number is what the platform treats as wrong.
     */
    public function testResolvePricesTakesTheNetAmountAndZeroesGrossInNetPriceMode(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote('NET_MODE', [new ItemTransfer()]);

        // Act
        $quoteTransfer = $this->createResolver()->resolvePrices($quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $itemTransfer = $quoteTransfer->getItems()->offsetGet(0);

        $this->assertSame(static::CATALOGUE_NET_PRICE, $itemTransfer->getUnitNetPrice());
        $this->assertSame(0, $itemTransfer->getUnitGrossPrice());
    }

    /**
     * In net mode the caller's figure lives in the net source field, and the gross one is not a
     * fallback for it — reading the wrong one would charge the catalogue price silently.
     */
    public function testResolvePricesReadsTheSourcePriceOfTheStoresPriceMode(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote('NET_MODE', [
            (new ItemTransfer())->setSourceUnitNetPrice(static::SUBMITTED_PRICE),
        ]);

        // Act
        $quoteTransfer = $this->createResolver()->resolvePrices($quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertSame(static::SUBMITTED_PRICE, $quoteTransfer->getItems()->offsetGet(0)->getUnitNetPrice());
    }

    /**
     * Defaulting to zero would place a free order because the catalogue had nothing to say.
     */
    public function testResolvePricesReportsAnItemTheCatalogueCannotPrice(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote('GROSS_MODE', [new ItemTransfer()]);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $quoteTransfer = $this->createResolver(null)->resolvePrices($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);

        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame('items[0].unitCustomPrice', $validationIssueTransfer->getField());
        $this->assertSame(static::SKU, $validationIssueTransfer->getParameters()['%sku%']);
        $this->assertNull($quoteTransfer->getItems()->offsetGet(0)->getUnitGrossPrice());
    }

    /**
     * A line `OrderIntakeItemExpander` already rejected as an unknown SKU has nothing for pricing to
     * add: the real problem is that the SKU does not exist, and "no price found — send a
     * unitCustomPrice to override" is not just redundant but actively misleading advice for a
     * product that was never going to be found at any price.
     */
    public function testResolvePricesSkipsAnItemAlreadyReportedAsAnUnknownSku(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote('GROSS_MODE', [new ItemTransfer()]);
        $orderIntakeResponseTransfer = (new OrderIntakeResponseTransfer())->addValidationIssue(
            (new OrderIntakeValidationIssueTransfer())
                ->setField('items[0].sku')
                ->setMessage(sprintf('Product with SKU "%s" was not found.', static::SKU)),
        );

        $priceProductFacadeMock = $this->createMock(PriceProductFacadeInterface::class);
        $priceProductFacadeMock->expects($this->never())->method('getValidPrices');

        // Act
        $this->createResolver(null, $priceProductFacadeMock)->resolvePrices($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert — still exactly the one "unknown SKU" issue; no second, pricing-flavoured one.
        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame('items[0].sku', $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField());
    }

    /**
     * `MerchantRelationshipPriceQueryExpander` reads the buyer's business unit off the filter's quote
     * to find the contract prices it is entitled to. Without the quote it finds none and the line is
     * priced at the operator's list — the wrong number, quietly.
     */
    public function testResolvePricesCarriesTheQuoteOnEveryFilterSoContractPricesCanApply(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote('GROSS_MODE', [new ItemTransfer()]);
        $capturedPriceProductFilterTransfers = [];

        $priceProductFacadeMock = $this->createMock(PriceProductFacadeInterface::class);
        $priceProductFacadeMock->method('getDefaultPriceTypeName')->willReturn('DEFAULT');
        $priceProductFacadeMock->method('getValidPrices')->willReturnCallback(
            function (array $priceProductFilterTransfers) use (&$capturedPriceProductFilterTransfers): array {
                $capturedPriceProductFilterTransfers = $priceProductFilterTransfers;

                return [];
            },
        );

        $priceProductServiceMock = $this->createMock(PriceProductServiceInterface::class);
        $priceProductServiceMock->method('resolveProductPriceByPriceProductFilter')->willReturn(
            (new PriceProductTransfer())->setMoneyValue(
                (new MoneyValueTransfer())->setGrossAmount(static::CATALOGUE_GROSS_PRICE),
            ),
        );

        // Act
        (new OrderIntakePriceResolver($priceProductFacadeMock, $priceProductServiceMock))
            ->resolvePrices($quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertCount(1, $capturedPriceProductFilterTransfers);
        $this->assertInstanceOf(PriceProductFilterTransfer::class, $capturedPriceProductFilterTransfers[0]);
        $this->assertSame($quoteTransfer, $capturedPriceProductFilterTransfers[0]->getQuote());
    }

    /**
     * A priced line is resolved too, which the previous version skipped. That is the cost of being
     * able to compare, and it must be a deliberate cost rather than an accident.
     */
    public function testResolvePricesResolvesEveryLineIncludingThePricedOnes(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote('GROSS_MODE', [
            (new ItemTransfer())->setSourceUnitGrossPrice(static::SUBMITTED_PRICE),
            new ItemTransfer(),
        ]);
        $capturedFilterCount = 0;

        $priceProductFacadeMock = $this->createMock(PriceProductFacadeInterface::class);
        $priceProductFacadeMock->method('getDefaultPriceTypeName')->willReturn('DEFAULT');
        $priceProductFacadeMock->expects($this->once())
            ->method('getValidPrices')
            ->willReturnCallback(function (array $priceProductFilterTransfers) use (&$capturedFilterCount): array {
                $capturedFilterCount = count($priceProductFilterTransfers);

                return [];
            });

        $priceProductServiceMock = $this->createMock(PriceProductServiceInterface::class);
        $priceProductServiceMock->method('resolveProductPriceByPriceProductFilter')->willReturn(
            (new PriceProductTransfer())->setMoneyValue(
                (new MoneyValueTransfer())->setGrossAmount(static::CATALOGUE_GROSS_PRICE),
            ),
        );

        // Act
        (new OrderIntakePriceResolver($priceProductFacadeMock, $priceProductServiceMock))
            ->resolvePrices($quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertSame(2, $capturedFilterCount);
    }

    /**
     * CC-40626: a volume-price tier must be resolved from the combined quantity of every line that is
     * the same item for pricing purposes, not each line's own quantity — otherwise splitting one order
     * across two lines of the same SKU silently loses the volume price a single combined line would
     * have earned. This mirrors the storefront cart's aggregation
     * ({@see \Pyz\Zed\PriceCartConnector\PriceCartConnectorConfig::getItemFieldsForIsSameItemComparison}).
     */
    public function testResolvePricesUsesTheCombinedQuantityOfMatchingLinesForTheVolumePriceTier(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote('GROSS_MODE', [
            (new ItemTransfer())->setQuantity(5),
            (new ItemTransfer())->setQuantity(5),
        ]);
        $capturedQuantities = [];

        $priceProductFacadeMock = $this->createMock(PriceProductFacadeInterface::class);
        $priceProductFacadeMock->method('getDefaultPriceTypeName')->willReturn('DEFAULT');
        $priceProductFacadeMock->method('getValidPrices')->willReturnCallback(
            function (array $priceProductFilterTransfers) use (&$capturedQuantities): array {
                foreach ($priceProductFilterTransfers as $priceProductFilterTransfer) {
                    $capturedQuantities[] = $priceProductFilterTransfer->getQuantity();
                }

                return [];
            },
        );

        $priceProductServiceMock = $this->createMock(PriceProductServiceInterface::class);
        $priceProductServiceMock->method('resolveProductPriceByPriceProductFilter')->willReturn(
            (new PriceProductTransfer())->setMoneyValue(
                (new MoneyValueTransfer())->setGrossAmount(static::CATALOGUE_GROSS_PRICE),
            ),
        );

        // Act
        (new OrderIntakePriceResolver($priceProductFacadeMock, $priceProductServiceMock))
            ->resolvePrices($quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertSame([10, 10], $capturedQuantities);
    }

    /**
     * Two lines of the same SKU sold by different merchant offers are NOT the same purchase for
     * volume-pricing purposes — each is priced on its own quantity, matching the storefront cart's
     * identity fields (SKU + merchantReference + productOfferReference).
     */
    public function testResolvePricesDoesNotCombineQuantityAcrossDifferentMerchantOffers(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote('GROSS_MODE', [
            (new ItemTransfer())->setQuantity(5)->setProductOfferReference('offer-a'),
            (new ItemTransfer())->setQuantity(5)->setProductOfferReference('offer-b'),
        ]);
        $capturedQuantities = [];

        $priceProductFacadeMock = $this->createMock(PriceProductFacadeInterface::class);
        $priceProductFacadeMock->method('getDefaultPriceTypeName')->willReturn('DEFAULT');
        $priceProductFacadeMock->method('getValidPrices')->willReturnCallback(
            function (array $priceProductFilterTransfers) use (&$capturedQuantities): array {
                foreach ($priceProductFilterTransfers as $priceProductFilterTransfer) {
                    $capturedQuantities[] = $priceProductFilterTransfer->getQuantity();
                }

                return [];
            },
        );

        $priceProductServiceMock = $this->createMock(PriceProductServiceInterface::class);
        $priceProductServiceMock->method('resolveProductPriceByPriceProductFilter')->willReturn(
            (new PriceProductTransfer())->setMoneyValue(
                (new MoneyValueTransfer())->setGrossAmount(static::CATALOGUE_GROSS_PRICE),
            ),
        );

        // Act
        (new OrderIntakePriceResolver($priceProductFacadeMock, $priceProductServiceMock))
            ->resolvePrices($quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertSame([5, 5], $capturedQuantities);
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     */
    protected function createQuote(string $priceMode, array $itemTransfers): QuoteTransfer
    {
        $quoteTransfer = (new QuoteTransfer())
            ->setPriceMode($priceMode)
            ->setCurrency((new CurrencyTransfer())->setCode('EUR'))
            ->setStore((new StoreTransfer())->setName('DE'));

        foreach ($itemTransfers as $itemTransfer) {
            $quoteTransfer->addItem($itemTransfer->getSku() === null ? $itemTransfer->setSku(static::SKU) : $itemTransfer);
        }

        return $quoteTransfer;
    }

    /**
     * Passing null models a catalogue with no price for the product.
     */
    protected function createResolver(
        ?int $unused = 0,
        ?PriceProductFacadeInterface $priceProductFacadeMock = null,
    ): OrderIntakePriceResolver {
        $priceProductTransfer = $unused === null ? null : (new PriceProductTransfer())->setMoneyValue(
            (new MoneyValueTransfer())
                ->setGrossAmount(static::CATALOGUE_GROSS_PRICE)
                ->setNetAmount(static::CATALOGUE_NET_PRICE),
        );

        if ($priceProductFacadeMock === null) {
            $priceProductFacadeMock = $this->createMock(PriceProductFacadeInterface::class);
            $priceProductFacadeMock->method('getDefaultPriceTypeName')->willReturn('DEFAULT');
            $priceProductFacadeMock->method('getValidPrices')->willReturn([]);
        }

        $priceProductServiceMock = $this->createMock(PriceProductServiceInterface::class);
        $priceProductServiceMock->method('resolveProductPriceByPriceProductFilter')->willReturn($priceProductTransfer);

        return new OrderIntakePriceResolver($priceProductFacadeMock, $priceProductServiceMock);
    }
}
