<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Reader;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\MerchantStorageTransfer;
use Generated\Shared\Transfer\ProductOfferStorageCollectionTransfer;
use Generated\Shared\Transfer\ProductOfferStorageTransfer;
use Generated\Shared\Transfer\ProductOfferTransfer;
use Spryker\Client\MerchantStorage\MerchantStorageClientInterface;
use Spryker\Client\ProductOfferStorage\ProductOfferStorageClientInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Checker\AddedProductConcreteRestrictionCheckerInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Filter\ProductOfferAvailabilityFilterInterface;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedMerchantProductReaderInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedProductOfferReader;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Reader
 * @group AddedProductOfferReaderTest
 */
class AddedProductOfferReaderTest extends Unit
{
    public function testReturnsEmptyArrayAndSkipsLookupForEmptySku(): void
    {
        // Arrange
        $productOfferStorageClientMock = $this->createMock(ProductOfferStorageClientInterface::class);
        $productOfferStorageClientMock->expects($this->never())->method('getProductOfferStoragesBySkus');

        $addedProductOfferReader = new AddedProductOfferReader(
            $productOfferStorageClientMock,
            $this->createMock(MerchantStorageClientInterface::class),
            $this->createMock(ProductOfferAvailabilityFilterInterface::class),
            $this->createMerchantProductReaderMock(null),
            $this->createConfigMock(true),
            $this->createRestrictionCheckerMock(false),
        );

        // Act + Assert
        $this->assertSame([], $addedProductOfferReader->getAvailableProductOfferChoices(''));
    }

    public function testMapsOffersWithMerchantNamesAndSkipsFilterWhenFlagDisabled(): void
    {
        // Arrange
        $productOfferStorageCollectionTransfer = (new ProductOfferStorageCollectionTransfer())
            ->addProductOffer(
                (new ProductOfferStorageTransfer())
                    ->setProductConcreteSku('sku-1')
                    ->setProductOfferReference('offer-1')
                    ->setMerchantReference('MER-1'),
            );

        $productOfferAvailabilityFilterMock = $this->createMock(ProductOfferAvailabilityFilterInterface::class);
        $productOfferAvailabilityFilterMock->expects($this->never())->method('filterAvailable');

        $addedProductOfferReader = new AddedProductOfferReader(
            $this->createProductOfferStorageClientMock($productOfferStorageCollectionTransfer),
            $this->createMerchantStorageClientMock(['MER-1' => 'Merchant One']),
            $productOfferAvailabilityFilterMock,
            $this->createMerchantProductReaderMock(null),
            $this->createConfigMock(false),
            $this->createRestrictionCheckerMock(false),
        );

        // Act
        $result = $addedProductOfferReader->getAvailableProductOfferChoices('sku-1');

        // Assert
        $this->assertCount(1, $result);
        $this->assertSame('offer-1', $result[0]->getProductOfferReference());
        $this->assertSame('Merchant One', $result[0]->getMerchantName());
    }

    public function testAppliesAvailabilityFilterWhenFlagEnabled(): void
    {
        // Arrange
        $productOfferStorageCollectionTransfer = (new ProductOfferStorageCollectionTransfer())
            ->addProductOffer(
                (new ProductOfferStorageTransfer())
                    ->setProductConcreteSku('sku-1')
                    ->setProductOfferReference('offer-1')
                    ->setMerchantReference('MER-1'),
            );

        $productOfferAvailabilityFilterMock = $this->createMock(ProductOfferAvailabilityFilterInterface::class);
        $productOfferAvailabilityFilterMock->expects($this->once())->method('filterAvailable')->willReturn([]);

        $addedProductOfferReader = new AddedProductOfferReader(
            $this->createProductOfferStorageClientMock($productOfferStorageCollectionTransfer),
            $this->createMerchantStorageClientMock([]),
            $productOfferAvailabilityFilterMock,
            $this->createMerchantProductReaderMock(null),
            $this->createConfigMock(true),
            $this->createRestrictionCheckerMock(false),
        );

        // Act
        $result = $addedProductOfferReader->getAvailableProductOfferChoices('sku-1');

        // Assert
        $this->assertSame([], $result);
    }

    public function testPrependsMerchantProductChoiceBeforeOffers(): void
    {
        // Arrange
        $productOfferStorageCollectionTransfer = (new ProductOfferStorageCollectionTransfer())
            ->addProductOffer(
                (new ProductOfferStorageTransfer())
                    ->setProductConcreteSku('sku-1')
                    ->setProductOfferReference('offer-1')
                    ->setMerchantReference('MER-1'),
            );

        $merchantProductChoiceTransfer = (new ProductOfferTransfer())
            ->setConcreteSku('sku-1')
            ->setMerchantName('Spryker')
            ->setMerchantReference('MER-OWNER')
            ->setProductOfferReference('');

        $productOfferAvailabilityFilterMock = $this->createMock(ProductOfferAvailabilityFilterInterface::class);
        $productOfferAvailabilityFilterMock->method('filterAvailable')
            ->willReturnArgument(0);

        $addedProductOfferReader = new AddedProductOfferReader(
            $this->createProductOfferStorageClientMock($productOfferStorageCollectionTransfer),
            $this->createMerchantStorageClientMock(['MER-1' => 'Merchant One']),
            $productOfferAvailabilityFilterMock,
            $this->createMerchantProductReaderMock($merchantProductChoiceTransfer),
            $this->createConfigMock(true),
            $this->createRestrictionCheckerMock(false),
        );

        // Act
        $result = $addedProductOfferReader->getAvailableProductOfferChoices('sku-1');

        // Assert
        $this->assertCount(2, $result);
        $this->assertSame('', $result[0]->getProductOfferReference());
        $this->assertSame('Spryker', $result[0]->getMerchantName());
        $this->assertSame('offer-1', $result[1]->getProductOfferReference());
    }

    /**
     * A restricted product must offer nothing at all — neither merchant offers nor the owning merchant's own
     * product choice.
     */
    public function testReturnsEmptyArrayAndSkipsLookupForRestrictedProduct(): void
    {
        // Arrange
        $productOfferStorageClientMock = $this->createMock(ProductOfferStorageClientInterface::class);
        $productOfferStorageClientMock->expects($this->never())->method('getProductOfferStoragesBySkus');

        $addedMerchantProductReaderMock = $this->createMock(AddedMerchantProductReaderInterface::class);
        $addedMerchantProductReaderMock->expects($this->never())->method('findMerchantProductChoice');

        $addedProductOfferReader = new AddedProductOfferReader(
            $productOfferStorageClientMock,
            $this->createMock(MerchantStorageClientInterface::class),
            $this->createMock(ProductOfferAvailabilityFilterInterface::class),
            $addedMerchantProductReaderMock,
            $this->createConfigMock(true),
            $this->createRestrictionCheckerMock(true),
        );

        // Act + Assert
        $this->assertSame([], $addedProductOfferReader->getAvailableProductOfferChoices('service-001-1'));
    }

    protected function createRestrictionCheckerMock(bool $isRestricted): AddedProductConcreteRestrictionCheckerInterface
    {
        $addedProductConcreteRestrictionCheckerMock = $this->createMock(AddedProductConcreteRestrictionCheckerInterface::class);
        $addedProductConcreteRestrictionCheckerMock->method('isProductConcreteRestricted')->willReturn($isRestricted);

        return $addedProductConcreteRestrictionCheckerMock;
    }

    protected function createProductOfferStorageClientMock(
        ProductOfferStorageCollectionTransfer $productOfferStorageCollectionTransfer
    ): ProductOfferStorageClientInterface {
        $productOfferStorageClientMock = $this->createMock(ProductOfferStorageClientInterface::class);
        $productOfferStorageClientMock
            ->method('getProductOfferStoragesBySkus')
            ->willReturn($productOfferStorageCollectionTransfer);

        return $productOfferStorageClientMock;
    }

    /**
     * @param array<string, string> $merchantNamesByReference
     */
    protected function createMerchantStorageClientMock(array $merchantNamesByReference): MerchantStorageClientInterface
    {
        $merchantStorageTransfers = [];

        foreach ($merchantNamesByReference as $merchantReference => $merchantName) {
            $merchantStorageTransfers[] = (new MerchantStorageTransfer())
                ->setMerchantReference($merchantReference)
                ->setName($merchantName);
        }

        $merchantStorageClientMock = $this->createMock(MerchantStorageClientInterface::class);
        $merchantStorageClientMock->method('get')->willReturn($merchantStorageTransfers);

        return $merchantStorageClientMock;
    }

    protected function createMerchantProductReaderMock(?ProductOfferTransfer $productOfferTransfer): AddedMerchantProductReaderInterface
    {
        $addedMerchantProductReaderMock = $this->createMock(AddedMerchantProductReaderInterface::class);
        $addedMerchantProductReaderMock->method('findMerchantProductChoice')->willReturn($productOfferTransfer);

        return $addedMerchantProductReaderMock;
    }

    protected function createConfigMock(bool $isExclusionEnabled): OrderExperienceManagementConfig
    {
        $configMock = $this->createMock(OrderExperienceManagementConfig::class);
        $configMock->method('isUnavailableProductsExcludedFromAddProductSearch')->willReturn($isExclusionEnabled);

        return $configMock;
    }
}
