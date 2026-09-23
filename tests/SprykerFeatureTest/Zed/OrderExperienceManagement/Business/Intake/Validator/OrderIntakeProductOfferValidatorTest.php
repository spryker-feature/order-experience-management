<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Validator;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\ProductOfferCollectionTransfer;
use Generated\Shared\Transfer\ProductOfferTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\ProductOffer\Business\ProductOfferFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeProductOfferValidator;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Validator
 * @group OrderIntakeProductOfferValidatorTest
 * Add your own group annotations below this line
 */
class OrderIntakeProductOfferValidatorTest extends Unit
{
    protected const string OFFER_REFERENCE = 'offer39';

    protected const string OFFER_SKU = '032_32125551';

    protected const string OFFER_MERCHANT_REFERENCE = 'MER000002';

    protected const string OTHER_MERCHANT_REFERENCE = 'MER000001';

    public function testValidateProductOffersAcceptsAMatchingOffer(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(static::OFFER_SKU, static::OFFER_REFERENCE, static::OFFER_MERCHANT_REFERENCE);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator()->validateProductOffers($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * Naming the offer is enough — the merchant it belongs to is not something the caller should
     * have to repeat.
     */
    public function testValidateProductOffersFillsTheMerchantReferenceFromTheOffer(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(static::OFFER_SKU, static::OFFER_REFERENCE, null);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator()->validateProductOffers($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(static::OFFER_MERCHANT_REFERENCE, $quoteTransfer->getItems()->offsetGet(0)->getMerchantReference());
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * The criteria excludes inactive and unapproved offers, so anything the lookup does not return
     * is invalid for one of those reasons and reports the same way.
     */
    public function testValidateProductOffersReportsAnUnknownOffer(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(static::OFFER_SKU, 'nope-999', null);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator()->validateProductOffers($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);

        $this->assertSame('items[0].productOfferReference', $validationIssueTransfer->getField());
        $this->assertSame('nope-999', $validationIssueTransfer->getParameters()['%productOfferReference%']);
    }

    /**
     * An offer that sells another product would price the line from the wrong product entirely.
     */
    public function testValidateProductOffersReportsAnOfferForADifferentSku(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote('131_24872891', static::OFFER_REFERENCE, null);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator()->validateProductOffers($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(
            static::OFFER_SKU,
            $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getParameters()['%offerSku%'],
        );
    }

    /**
     * Rejected rather than silently corrected: a merchant the caller named and an offer that belongs
     * to someone else means their mapping is wrong, and hiding it would let it stay wrong.
     */
    public function testValidateProductOffersReportsAMerchantThatDoesNotOwnTheOffer(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(static::OFFER_SKU, static::OFFER_REFERENCE, static::OTHER_MERCHANT_REFERENCE);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator()->validateProductOffers($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(
            static::OFFER_MERCHANT_REFERENCE,
            $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getParameters()['%offerMerchantReference%'],
        );
        $this->assertSame(static::OTHER_MERCHANT_REFERENCE, $quoteTransfer->getItems()->offsetGet(0)->getMerchantReference());
    }

    /**
     * An operator-sold line names no offer and must not cost a lookup.
     */
    public function testValidateProductOffersIgnoresALineWithNoOffer(): void
    {
        // Arrange
        $quoteTransfer = $this->createQuote(static::OFFER_SKU, null, null);
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $productOfferFacadeMock = $this->createMock(ProductOfferFacadeInterface::class);
        $productOfferFacadeMock->expects($this->never())->method('getProductOfferCollection');

        // Act
        (new OrderIntakeProductOfferValidator($productOfferFacadeMock))
            ->validateProductOffers($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    protected function createQuote(string $sku, ?string $productOfferReference, ?string $merchantReference): QuoteTransfer
    {
        return (new QuoteTransfer())->addItem(
            (new ItemTransfer())
                ->setSku($sku)
                ->setProductOfferReference($productOfferReference)
                ->setMerchantReference($merchantReference),
        );
    }

    protected function createValidator(): OrderIntakeProductOfferValidator
    {
        $productOfferCollectionTransfer = (new ProductOfferCollectionTransfer())->addProductOffer(
            (new ProductOfferTransfer())
                ->setProductOfferReference(static::OFFER_REFERENCE)
                ->setConcreteSku(static::OFFER_SKU)
                ->setMerchantReference(static::OFFER_MERCHANT_REFERENCE),
        );

        $productOfferFacadeMock = $this->createMock(ProductOfferFacadeInterface::class);
        $productOfferFacadeMock->method('getProductOfferCollection')->willReturn($productOfferCollectionTransfer);

        return new OrderIntakeProductOfferValidator($productOfferFacadeMock);
    }
}
