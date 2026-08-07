<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Reader;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\MerchantStorageTransfer;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\MerchantStorage\MerchantStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedMerchantProductReader;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\ProductConcreteAvailabilityReaderInterface;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Reader
 * @group AddedMerchantProductReaderTest
 */
class AddedMerchantProductReaderTest extends Unit
{
    protected const string SKU = 'sku-1';

    protected const string MERCHANT_REFERENCE = 'MER-OWNER';

    protected const string MERCHANT_NAME = 'Spryker';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    protected const string KEY_ID_PRODUCT_CONCRETE = 'id_product_concrete';

    protected const string KEY_MERCHANT_REFERENCE = 'merchant_reference';

    protected const int ID_PRODUCT_ABSTRACT = 1;

    protected const int ID_PRODUCT_CONCRETE = 2;

    public function testReturnsMerchantProductChoiceWhenAvailable(): void
    {
        // Arrange
        $addedMerchantProductReader = new AddedMerchantProductReader(
            $this->createProductStorageClientMock(
                [static::KEY_ID_PRODUCT_ABSTRACT => static::ID_PRODUCT_ABSTRACT, static::KEY_ID_PRODUCT_CONCRETE => static::ID_PRODUCT_CONCRETE],
                [static::KEY_MERCHANT_REFERENCE => static::MERCHANT_REFERENCE],
            ),
            $this->createMerchantStorageClientMock([static::MERCHANT_REFERENCE => static::MERCHANT_NAME]),
            $this->createLocaleClientMock(),
            $this->createProductConcreteAvailabilityReaderMock([static::ID_PRODUCT_CONCRETE => true]),
            $this->createConfigMock(true),
        );

        // Act
        $result = $addedMerchantProductReader->findMerchantProductChoice(static::SKU);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame(static::SKU, $result->getConcreteSku());
        $this->assertSame(static::MERCHANT_REFERENCE, $result->getMerchantReference());
        $this->assertSame(static::MERCHANT_NAME, $result->getMerchantName());
        $this->assertSame('', $result->getProductOfferReference());
    }

    public function testReturnsNullWhenConcreteStorageDataMissing(): void
    {
        // Arrange
        $addedMerchantProductReader = new AddedMerchantProductReader(
            $this->createProductStorageClientMock(null, null),
            $this->createMerchantStorageClientMock([]),
            $this->createLocaleClientMock(),
            $this->createProductConcreteAvailabilityReaderMock([], false),
            $this->createConfigMock(true),
        );

        // Act + Assert
        $this->assertNull($addedMerchantProductReader->findMerchantProductChoice(static::SKU));
    }

    public function testReturnsNullWhenNoOwningMerchantReference(): void
    {
        // Arrange
        $addedMerchantProductReader = new AddedMerchantProductReader(
            $this->createProductStorageClientMock(
                [static::KEY_ID_PRODUCT_ABSTRACT => static::ID_PRODUCT_ABSTRACT, static::KEY_ID_PRODUCT_CONCRETE => static::ID_PRODUCT_CONCRETE],
                [],
            ),
            $this->createMerchantStorageClientMock([]),
            $this->createLocaleClientMock(),
            $this->createProductConcreteAvailabilityReaderMock([], false),
            $this->createConfigMock(true),
        );

        // Act + Assert
        $this->assertNull($addedMerchantProductReader->findMerchantProductChoice(static::SKU));
    }

    public function testReturnsNullWhenUnavailableAndFlagEnabled(): void
    {
        // Arrange
        $addedMerchantProductReader = new AddedMerchantProductReader(
            $this->createProductStorageClientMock(
                [static::KEY_ID_PRODUCT_ABSTRACT => static::ID_PRODUCT_ABSTRACT, static::KEY_ID_PRODUCT_CONCRETE => static::ID_PRODUCT_CONCRETE],
                [static::KEY_MERCHANT_REFERENCE => static::MERCHANT_REFERENCE],
            ),
            $this->createMerchantStorageClientMock([static::MERCHANT_REFERENCE => static::MERCHANT_NAME]),
            $this->createLocaleClientMock(),
            $this->createProductConcreteAvailabilityReaderMock([static::ID_PRODUCT_CONCRETE => false]),
            $this->createConfigMock(true),
        );

        // Act + Assert
        $this->assertNull($addedMerchantProductReader->findMerchantProductChoice(static::SKU));
    }

    public function testReturnsChoiceWhenUnavailableButFlagDisabled(): void
    {
        // Arrange: the disabled flag must short-circuit before any availability is read.
        $addedMerchantProductReader = new AddedMerchantProductReader(
            $this->createProductStorageClientMock(
                [static::KEY_ID_PRODUCT_ABSTRACT => static::ID_PRODUCT_ABSTRACT, static::KEY_ID_PRODUCT_CONCRETE => static::ID_PRODUCT_CONCRETE],
                [static::KEY_MERCHANT_REFERENCE => static::MERCHANT_REFERENCE],
            ),
            $this->createMerchantStorageClientMock([static::MERCHANT_REFERENCE => static::MERCHANT_NAME]),
            $this->createLocaleClientMock(),
            $this->createProductConcreteAvailabilityReaderMock([static::ID_PRODUCT_CONCRETE => false], false),
            $this->createConfigMock(false),
        );

        // Act
        $result = $addedMerchantProductReader->findMerchantProductChoice(static::SKU);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame(static::MERCHANT_REFERENCE, $result->getMerchantReference());
    }

    public function testReturnsChoiceWhenAvailabilityCannotBeResolved(): void
    {
        // Arrange: an unresolvable availability must not hide the merchant product (fail-open).
        $addedMerchantProductReader = new AddedMerchantProductReader(
            $this->createProductStorageClientMock(
                [static::KEY_ID_PRODUCT_ABSTRACT => static::ID_PRODUCT_ABSTRACT, static::KEY_ID_PRODUCT_CONCRETE => static::ID_PRODUCT_CONCRETE],
                [static::KEY_MERCHANT_REFERENCE => static::MERCHANT_REFERENCE],
            ),
            $this->createMerchantStorageClientMock([static::MERCHANT_REFERENCE => static::MERCHANT_NAME]),
            $this->createLocaleClientMock(),
            $this->createProductConcreteAvailabilityReaderMock([]),
            $this->createConfigMock(true),
        );

        // Act
        $result = $addedMerchantProductReader->findMerchantProductChoice(static::SKU);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame(static::MERCHANT_REFERENCE, $result->getMerchantReference());
    }

    /**
     * @param array<string, mixed>|null $productConcreteStorageData
     * @param array<string, mixed>|null $productAbstractStorageData
     */
    protected function createProductStorageClientMock(
        ?array $productConcreteStorageData,
        ?array $productAbstractStorageData
    ): ProductStorageClientInterface {
        $productStorageClientMock = $this->createMock(ProductStorageClientInterface::class);
        $productStorageClientMock->method('findProductConcreteStorageDataByMapping')->willReturn($productConcreteStorageData);
        $productStorageClientMock->method('findProductAbstractStorageData')->willReturn($productAbstractStorageData);

        return $productStorageClientMock;
    }

    /**
     * @param array<int, bool> $isAvailableByIdProductConcrete
     */
    protected function createProductConcreteAvailabilityReaderMock(
        array $isAvailableByIdProductConcrete,
        bool $expectReaderCall = true
    ): ProductConcreteAvailabilityReaderInterface {
        $productConcreteAvailabilityReaderMock = $this->createMock(ProductConcreteAvailabilityReaderInterface::class);
        $productConcreteAvailabilityReaderMock
            ->expects($expectReaderCall ? $this->once() : $this->never())
            ->method('getAvailabilityByProductConcreteIds')
            ->willReturn($isAvailableByIdProductConcrete);

        return $productConcreteAvailabilityReaderMock;
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

    protected function createLocaleClientMock(): LocaleClientInterface
    {
        $localeClientMock = $this->createMock(LocaleClientInterface::class);
        $localeClientMock->method('getCurrentLocale')->willReturn('de_DE');

        return $localeClientMock;
    }

    protected function createConfigMock(bool $isExclusionEnabled): OrderExperienceManagementConfig
    {
        $configMock = $this->createMock(OrderExperienceManagementConfig::class);
        $configMock->method('isUnavailableProductsExcludedFromAddProductSearch')->willReturn($isExclusionEnabled);

        return $configMock;
    }
}
