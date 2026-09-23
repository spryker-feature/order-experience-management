<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\OrderIntakeItemTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Spryker\Shared\Price\PriceConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\OrderIntakeQuoteAssembler;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group OrderIntakeQuoteAssemblerTest
 * Add your own group annotations below this line
 */
class OrderIntakeQuoteAssemblerTest extends Unit
{
    protected const string SKU = '131_24872891';

    protected const int UNIT_PRICE = 9999;

    protected const string ORDER_CUSTOM_REFERENCE = 'ERP-PO-99812';

    /**
     * A submitted price is the platform's SOURCE price — the same slot an agent's negotiated price on
     * an RFQ uses — not a directly written unit price. That is what lets it beat every price
     * dimension in OrderIntakePriceResolver while the catalogue figure is still resolved beside it.
     */
    public function testAssembleQuoteMapsASubmittedPriceToTheGrossSourcePriceInGrossMode(): void
    {
        // Act
        $quoteTransfer = $this->createAssembler()->assembleQuote(
            $this->createRequest('GROSS_MODE', static::UNIT_PRICE),
        );

        // Assert
        $itemTransfer = $quoteTransfer->getItems()->offsetGet(0);

        $this->assertSame(static::UNIT_PRICE, $itemTransfer->getSourceUnitGrossPrice());
        $this->assertNull($itemTransfer->getSourceUnitNetPrice());
    }

    /**
     * Only ONE of the two is ever set. Writing the same figure to both — as this did before — claims
     * a gross amount and a net amount are the same number, which is exactly what
     * `PriceManager::applySourceUnitPrices()` avoids by zeroing the opposite mode.
     */
    public function testAssembleQuoteMapsASubmittedPriceToTheNetSourcePriceInNetMode(): void
    {
        // Act
        $quoteTransfer = $this->createAssembler()->assembleQuote(
            $this->createRequest('NET_MODE', static::UNIT_PRICE),
        );

        // Assert
        $itemTransfer = $quoteTransfer->getItems()->offsetGet(0);

        $this->assertSame(static::UNIT_PRICE, $itemTransfer->getSourceUnitNetPrice());
        $this->assertNull($itemTransfer->getSourceUnitGrossPrice());
    }

    /**
     * The unit price is the price resolver's to write, from the catalogue or from the source price.
     * The assembler writing it directly is what made a priced line skip resolution entirely.
     */
    public function testAssembleQuoteLeavesTheUnitPriceForThePriceResolverToDecide(): void
    {
        // Act
        $quoteTransfer = $this->createAssembler()->assembleQuote(
            $this->createRequest('GROSS_MODE', static::UNIT_PRICE),
        );

        // Assert
        $itemTransfer = $quoteTransfer->getItems()->offsetGet(0);

        $this->assertNull($itemTransfer->getUnitGrossPrice());
        $this->assertNull($itemTransfer->getUnitNetPrice());
    }

    public function testAssembleQuoteLeavesSourcePricesUnsetForAnUnpricedLine(): void
    {
        // Act
        $quoteTransfer = $this->createAssembler()->assembleQuote(
            $this->createRequest('GROSS_MODE', null),
        );

        // Assert
        $itemTransfer = $quoteTransfer->getItems()->offsetGet(0);

        $this->assertNull($itemTransfer->getSourceUnitGrossPrice());
        $this->assertNull($itemTransfer->getSourceUnitNetPrice());
    }

    /**
     * Persisted onto the order by `OrderCustomReferenceOrderPostSavePlugin` — the caller's own handle
     * on the order, and the replacement for the three source-tracking fields intake used to carry.
     */
    public function testAssembleQuoteCarriesTheOrderCustomReferenceOntoTheQuote(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createRequest('GROSS_MODE', null)
            ->setOrderCustomReference(static::ORDER_CUSTOM_REFERENCE);

        // Act
        $quoteTransfer = $this->createAssembler()->assembleQuote($orderIntakeRequestTransfer);

        // Assert
        $this->assertSame(static::ORDER_CUSTOM_REFERENCE, $quoteTransfer->getOrderCustomReference());
    }

    /**
     * The platform owns the default price mode — `Spryker\Shared\Price\PriceConfig` — and a project
     * can override it. A copy in this module would keep placing GROSS orders after a store switched
     * to NET, and since the mode decides which field a submitted price lands in, that would put the
     * caller's figure in the wrong one entirely.
     */
    public function testAssembleQuoteFallsBackToTheDefaultPriceModeResolvedFromThePlatform(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createRequest('GROSS_MODE', null)->setPriceMode(null);

        // Act
        $quoteTransfer = $this->createAssembler(PriceConfig::PRICE_MODE_NET)->assembleQuote($orderIntakeRequestTransfer);

        // Assert
        $this->assertSame(PriceConfig::PRICE_MODE_NET, $quoteTransfer->getPriceMode());
    }

    public function testAssembleQuoteKeepsAPriceModeTheCallerSuppliedOverTheDefault(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createRequest(PriceConfig::PRICE_MODE_NET, null);

        // Act
        $quoteTransfer = $this->createAssembler(PriceConfig::PRICE_MODE_GROSS)->assembleQuote($orderIntakeRequestTransfer);

        // Assert
        $this->assertSame(PriceConfig::PRICE_MODE_NET, $quoteTransfer->getPriceMode());
    }

    /**
     * No default any more — `OrderIntakePaymentResolver` is what confirms the key is real and
     * resolves its provider. The assembler's only job is to carry the submitted key through as the
     * lookup key that step reads.
     */
    public function testAssembleQuoteCarriesTheSubmittedPaymentMethodAsTheProvisionalPaymentSelection(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createRequest('GROSS_MODE', null)
            ->setPaymentMethodName('dummyPaymentInvoice');

        // Act
        $quoteTransfer = $this->createAssembler()->assembleQuote($orderIntakeRequestTransfer);

        // Assert
        $paymentTransfer = $quoteTransfer->getPaymentOrFail();

        $this->assertSame('dummyPaymentInvoice', $paymentTransfer->getPaymentSelection());
        $this->assertNull($paymentTransfer->getPaymentProvider());
    }

    /**
     * This quote is assembled fresh per request and never carries a `uuid`, so
     * `QuoteCheckoutCondition` would otherwise lock it on the customer's own name+email — colliding
     * across unrelated concurrent submissions for the same customer. `source` opts intake out of
     * that fallback (see `Pyz\Zed\QuoteCheckoutConnector\QuoteCheckoutConnectorConfig`).
     */
    public function testAssembleQuoteMarksTheQuoteWithTheApiSource(): void
    {
        // Act
        $quoteTransfer = $this->createAssembler()->assembleQuote($this->createRequest('GROSS_MODE', null));

        // Assert
        $this->assertSame(OrderExperienceManagementConfig::SOURCE_API, $quoteTransfer->getSource());
    }

    protected function createAssembler(string $defaultPriceMode = PriceConfig::PRICE_MODE_GROSS): OrderIntakeQuoteAssembler
    {
        return new OrderIntakeQuoteAssembler($defaultPriceMode);
    }

    protected function createRequest(string $priceMode, ?int $unitCustomPrice): OrderIntakeRequestTransfer
    {
        return (new OrderIntakeRequestTransfer())
            ->setPriceMode($priceMode)
            ->setStoreName('DE')
            ->setCurrencyCode('EUR')
            ->setCustomer((new CustomerTransfer())->setCustomerReference('DE--21'))
            ->setBillingAddress((new AddressTransfer())->setCity('Berlin'))
            ->setShippingAddress((new AddressTransfer())->setCity('Hamburg'))
            ->addItem(
                (new OrderIntakeItemTransfer())
                    ->setSku(static::SKU)
                    ->setQuantity(1)
                    ->setUnitCustomPrice($unitCustomPrice),
            );
    }

    /**
     * The shipping address is carried across as the caller stated it. It used to fall back to a
     * clone of the billing address when absent; that default moved out of the backend, and
     * OrderIntakeRequestValidator rejects a request that omits it UNLESS every item names its own.
     */
    public function testAssembleQuoteCarriesTheSubmittedShippingAddressRatherThanTheBillingOne(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createRequest(PriceConfig::PRICE_MODE_GROSS, 7000);

        // Act
        $quoteTransfer = $this->createAssembler()->assembleQuote($orderIntakeRequestTransfer);

        // Assert
        $this->assertSame('Hamburg', $quoteTransfer->getShippingAddress()?->getCity());
        $this->assertSame('Berlin', $quoteTransfer->getBillingAddress()?->getCity());
        $this->assertTrue($quoteTransfer->getShippingAddress()?->getIsAddressSavingSkipped());
    }

    public function testAssembleQuoteToleratesAnAbsentOrderLevelShippingAddress(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createRequest(PriceConfig::PRICE_MODE_GROSS, 7000)
            ->setShippingAddress(null);

        // Act
        $quoteTransfer = $this->createAssembler()->assembleQuote($orderIntakeRequestTransfer);

        // Assert
        $this->assertNull($quoteTransfer->getShippingAddress());
        $this->assertSame('Berlin', $quoteTransfer->getBillingAddress()?->getCity());
    }

    public function testAssembleQuoteCarriesTheItemNoteOntoTheItem(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = $this->createRequest(PriceConfig::PRICE_MODE_GROSS, 7000);
        $orderIntakeRequestTransfer->getItems()->offsetGet(0)->setCartNote('Deliver to gate 3');

        // Act
        $quoteTransfer = $this->createAssembler()->assembleQuote($orderIntakeRequestTransfer);

        // Assert
        $this->assertSame('Deliver to gate 3', $quoteTransfer->getItems()->offsetGet(0)->getCartNote());
    }
}
