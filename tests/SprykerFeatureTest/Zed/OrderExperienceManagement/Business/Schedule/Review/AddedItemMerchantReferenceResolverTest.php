<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductOfferCollectionTransfer;
use Generated\Shared\Transfer\ProductOfferTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer;
use Spryker\Shared\ProductOffer\ProductOfferConfig as SharedProductOfferConfig;
use Spryker\Zed\MerchantProduct\Business\MerchantProductFacadeInterface;
use Spryker\Zed\ProductOffer\Business\ProductOfferFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\AddedItemMerchantReferenceResolver;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Review
 * @group AddedItemMerchantReferenceResolverTest
 * Add your own group annotations below this line
 */
class AddedItemMerchantReferenceResolverTest extends Unit
{
    protected const string SKU_OFFER_PRODUCT = '136_24425591';

    protected const string SKU_MERCHANT_PRODUCT = '213_123';

    protected const string SKU_OPERATOR_PRODUCT = '130_24725761';

    protected const string PRODUCT_OFFER_REFERENCE = 'offer1';

    protected const string MERCHANT_REFERENCE_OFFER = 'MER000001';

    protected const string MERCHANT_REFERENCE_MERCHANT_PRODUCT = 'MER000002';

    public function testResolveMerchantReferencesKeysOfferMerchantReferenceByAdditionIndex(): void
    {
        // Arrange
        $addedItemMerchantReferenceResolver = $this->createResolver(
            [$this->createProductOffer(static::PRODUCT_OFFER_REFERENCE, static::MERCHANT_REFERENCE_OFFER)],
            [],
        );

        // Act
        $merchantReferencesByIndex = $addedItemMerchantReferenceResolver->resolveMerchantReferences([
            $this->createAddition(static::SKU_OFFER_PRODUCT, static::PRODUCT_OFFER_REFERENCE),
        ]);

        // Assert
        $this->assertSame([0 => static::MERCHANT_REFERENCE_OFFER], $merchantReferencesByIndex);
    }

    public function testResolveMerchantReferencesResolvesMerchantProductWithoutProductOfferFromSku(): void
    {
        // Arrange
        $addedItemMerchantReferenceResolver = $this->createResolver(
            [],
            [static::SKU_MERCHANT_PRODUCT => static::MERCHANT_REFERENCE_MERCHANT_PRODUCT],
        );

        // Act
        $merchantReferencesByIndex = $addedItemMerchantReferenceResolver->resolveMerchantReferences([
            $this->createAddition(static::SKU_MERCHANT_PRODUCT, ''),
        ]);

        // Assert
        $this->assertSame([0 => static::MERCHANT_REFERENCE_MERCHANT_PRODUCT], $merchantReferencesByIndex);
    }

    public function testResolveMerchantReferencesKeepsOfferAndMerchantProductAdditionsOnTheirOwnIndexes(): void
    {
        // Arrange
        $addedItemMerchantReferenceResolver = $this->createResolver(
            [$this->createProductOffer(static::PRODUCT_OFFER_REFERENCE, static::MERCHANT_REFERENCE_OFFER)],
            [static::SKU_MERCHANT_PRODUCT => static::MERCHANT_REFERENCE_MERCHANT_PRODUCT],
        );

        // Act
        $merchantReferencesByIndex = $addedItemMerchantReferenceResolver->resolveMerchantReferences([
            $this->createAddition(static::SKU_MERCHANT_PRODUCT, null),
            $this->createAddition(static::SKU_OFFER_PRODUCT, static::PRODUCT_OFFER_REFERENCE),
        ]);

        // Assert
        $this->assertSame(
            [0 => static::MERCHANT_REFERENCE_MERCHANT_PRODUCT, 1 => static::MERCHANT_REFERENCE_OFFER],
            $merchantReferencesByIndex,
        );
    }

    public function testResolveMerchantReferencesOmitsProductWithoutOwningMerchant(): void
    {
        // Arrange
        $addedItemMerchantReferenceResolver = $this->createResolver([], []);

        // Act
        $merchantReferencesByIndex = $addedItemMerchantReferenceResolver->resolveMerchantReferences([
            $this->createAddition(static::SKU_OPERATOR_PRODUCT, null),
        ]);

        // Assert
        $this->assertSame([], $merchantReferencesByIndex);
    }

    public function testResolveMerchantReferencesDoesNotFallBackToSkuWhenNamedOfferIsInactive(): void
    {
        // Arrange
        $addedItemMerchantReferenceResolver = $this->createResolver(
            [
                $this->createProductOffer(static::PRODUCT_OFFER_REFERENCE, static::MERCHANT_REFERENCE_OFFER)
                    ->setIsActive(false),
            ],
            [static::SKU_OFFER_PRODUCT => static::MERCHANT_REFERENCE_MERCHANT_PRODUCT],
        );

        // Act
        $merchantReferencesByIndex = $addedItemMerchantReferenceResolver->resolveMerchantReferences([
            $this->createAddition(static::SKU_OFFER_PRODUCT, static::PRODUCT_OFFER_REFERENCE),
        ]);

        // Assert
        $this->assertSame([], $merchantReferencesByIndex);
    }

    public function testResolveMerchantReferencesOmitsUnapprovedOffer(): void
    {
        // Arrange
        $addedItemMerchantReferenceResolver = $this->createResolver(
            [
                $this->createProductOffer(static::PRODUCT_OFFER_REFERENCE, static::MERCHANT_REFERENCE_OFFER)
                    ->setApprovalStatus('waiting_for_approval'),
            ],
            [],
        );

        // Act
        $merchantReferencesByIndex = $addedItemMerchantReferenceResolver->resolveMerchantReferences([
            $this->createAddition(static::SKU_OFFER_PRODUCT, static::PRODUCT_OFFER_REFERENCE),
        ]);

        // Assert
        $this->assertSame([], $merchantReferencesByIndex);
    }

    public function testResolveMerchantReferencesDoesNotQueryMerchantProductsWhenEveryAdditionNamesAnOffer(): void
    {
        // Arrange
        $productOfferFacadeMock = $this->createMock(ProductOfferFacadeInterface::class);
        $productOfferFacadeMock->method('getProductOfferCollection')->willReturn(
            (new ProductOfferCollectionTransfer())->addProductOffer(
                $this->createProductOffer(static::PRODUCT_OFFER_REFERENCE, static::MERCHANT_REFERENCE_OFFER),
            ),
        );

        $merchantProductFacadeMock = $this->createMock(MerchantProductFacadeInterface::class);
        $merchantProductFacadeMock->expects($this->never())->method('getConcreteProductSkuMerchantReferenceMap');

        $addedItemMerchantReferenceResolver = new AddedItemMerchantReferenceResolver(
            $productOfferFacadeMock,
            $merchantProductFacadeMock,
        );

        // Act
        $merchantReferencesByIndex = $addedItemMerchantReferenceResolver->resolveMerchantReferences([
            $this->createAddition(static::SKU_OFFER_PRODUCT, static::PRODUCT_OFFER_REFERENCE),
        ]);

        // Assert
        $this->assertSame([0 => static::MERCHANT_REFERENCE_OFFER], $merchantReferencesByIndex);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductOfferTransfer> $productOfferTransfers
     * @param array<string, string> $merchantReferencesBySku
     */
    protected function createResolver(array $productOfferTransfers, array $merchantReferencesBySku): AddedItemMerchantReferenceResolver
    {
        $productOfferCollectionTransfer = new ProductOfferCollectionTransfer();

        foreach ($productOfferTransfers as $productOfferTransfer) {
            $productOfferCollectionTransfer->addProductOffer($productOfferTransfer);
        }

        $productOfferFacadeMock = $this->createMock(ProductOfferFacadeInterface::class);
        $productOfferFacadeMock->method('getProductOfferCollection')->willReturn($productOfferCollectionTransfer);

        $merchantProductFacadeMock = $this->createMock(MerchantProductFacadeInterface::class);
        $merchantProductFacadeMock->method('getConcreteProductSkuMerchantReferenceMap')->willReturn($merchantReferencesBySku);

        return new AddedItemMerchantReferenceResolver($productOfferFacadeMock, $merchantProductFacadeMock);
    }

    protected function createAddition(string $sku, ?string $productOfferReference): RecurringScheduleItemAdditionTransfer
    {
        return (new RecurringScheduleItemAdditionTransfer())
            ->setSku($sku)
            ->setQuantity(1)
            ->setProductOfferReference($productOfferReference);
    }

    protected function createProductOffer(string $productOfferReference, string $merchantReference): ProductOfferTransfer
    {
        return (new ProductOfferTransfer())
            ->setProductOfferReference($productOfferReference)
            ->setMerchantReference($merchantReference)
            ->setIsActive(true)
            ->setApprovalStatus(SharedProductOfferConfig::STATUS_APPROVED);
    }
}
