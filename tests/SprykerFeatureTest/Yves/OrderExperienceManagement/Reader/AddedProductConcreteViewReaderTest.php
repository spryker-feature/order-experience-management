<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Reader;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductViewTransfer;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedProductConcreteViewReader;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Reader
 * @group AddedProductConcreteViewReaderTest
 */
class AddedProductConcreteViewReaderTest extends Unit
{
    protected const string SKU = 'service-001-1';

    protected const string LOCALE_NAME = 'en_US';

    protected const int ID_PRODUCT_CONCRETE = 325;

    public function testFindProductConcreteViewReturnsTheViewMatchingTheSku(): void
    {
        // Arrange
        $productViewTransfer = (new ProductViewTransfer())->setSku(static::SKU);

        $addedProductConcreteViewReader = new AddedProductConcreteViewReader(
            $this->createProductStorageClientMock(
                ['id_product_concrete' => static::ID_PRODUCT_CONCRETE],
                [$productViewTransfer],
            ),
            $this->createLocaleClientMock(),
        );

        // Act
        $result = $addedProductConcreteViewReader->findProductConcreteView(static::SKU);

        // Assert
        $this->assertSame($productViewTransfer, $result);
    }

    public function testFindProductConcreteViewReturnsNullWhenTheConcreteIsNotInStorage(): void
    {
        // Arrange
        $addedProductConcreteViewReader = new AddedProductConcreteViewReader(
            $this->createProductStorageClientMock(null, []),
            $this->createLocaleClientMock(),
        );

        // Act
        $result = $addedProductConcreteViewReader->findProductConcreteView(static::SKU);

        // Assert
        $this->assertNull($result);
    }

    public function testFindProductConcreteViewReturnsNullWhenTheStorageDataHasNoConcreteId(): void
    {
        // Arrange
        $addedProductConcreteViewReader = new AddedProductConcreteViewReader(
            $this->createProductStorageClientMock(['id_product_abstract' => 225], []),
            $this->createLocaleClientMock(),
        );

        // Act
        $result = $addedProductConcreteViewReader->findProductConcreteView(static::SKU);

        // Assert
        $this->assertNull($result);
    }

    public function testGetProductConcreteViewsBySkuKeysTheViewsBySkuAndSkipsViewsWithoutOne(): void
    {
        // Arrange
        $addedProductConcreteViewReader = new AddedProductConcreteViewReader(
            $this->createProductStorageClientMock(null, [
                (new ProductViewTransfer())->setSku(static::SKU),
                new ProductViewTransfer(),
            ]),
            $this->createLocaleClientMock(),
        );

        // Act
        $result = $addedProductConcreteViewReader->getProductConcreteViewsBySku([static::ID_PRODUCT_CONCRETE]);

        // Assert
        $this->assertSame([static::SKU], array_keys($result));
    }

    public function testGetProductConcreteViewsBySkuSkipsTheStorageLookupWithoutIds(): void
    {
        // Arrange
        $productStorageClientMock = $this->createMock(ProductStorageClientInterface::class);
        $productStorageClientMock->expects($this->never())->method('getProductConcreteViewTransfers');

        $addedProductConcreteViewReader = new AddedProductConcreteViewReader(
            $productStorageClientMock,
            $this->createLocaleClientMock(),
        );

        // Act
        $result = $addedProductConcreteViewReader->getProductConcreteViewsBySku([]);

        // Assert
        $this->assertSame([], $result);
    }

    /**
     * @param array<string, mixed>|null $productConcreteStorageData
     * @param array<\Generated\Shared\Transfer\ProductViewTransfer> $productViewTransfers
     */
    protected function createProductStorageClientMock(
        ?array $productConcreteStorageData,
        array $productViewTransfers,
    ): ProductStorageClientInterface {
        $productStorageClientMock = $this->createMock(ProductStorageClientInterface::class);
        $productStorageClientMock
            ->method('findProductConcreteStorageDataByMapping')
            ->willReturn($productConcreteStorageData);
        $productStorageClientMock
            ->method('getProductConcreteViewTransfers')
            ->willReturn($productViewTransfers);

        return $productStorageClientMock;
    }

    protected function createLocaleClientMock(): LocaleClientInterface
    {
        $localeClientMock = $this->createMock(LocaleClientInterface::class);
        $localeClientMock->method('getCurrentLocale')->willReturn(static::LOCALE_NAME);

        return $localeClientMock;
    }
}
