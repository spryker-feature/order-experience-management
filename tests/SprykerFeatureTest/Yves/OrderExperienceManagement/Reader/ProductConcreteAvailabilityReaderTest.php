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
use SprykerFeature\Yves\OrderExperienceManagement\Reader\ProductConcreteAvailabilityReader;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Reader
 * @group ProductConcreteAvailabilityReaderTest
 */
class ProductConcreteAvailabilityReaderTest extends Unit
{
    protected const string LOCALE_NAME = 'de_DE';

    protected const int ID_PRODUCT_CONCRETE_AVAILABLE = 1;

    protected const int ID_PRODUCT_CONCRETE_UNAVAILABLE = 2;

    public function testReturnsAvailabilityKeyedByProductConcreteId(): void
    {
        // Arrange
        $productConcreteAvailabilityReader = $this->createReader([
            (new ProductViewTransfer())->setIdProductConcrete(static::ID_PRODUCT_CONCRETE_AVAILABLE)->setAvailable(true),
            (new ProductViewTransfer())->setIdProductConcrete(static::ID_PRODUCT_CONCRETE_UNAVAILABLE)->setAvailable(false),
        ]);

        // Act
        $result = $productConcreteAvailabilityReader->getAvailabilityByProductConcreteIds([
            static::ID_PRODUCT_CONCRETE_AVAILABLE,
            static::ID_PRODUCT_CONCRETE_UNAVAILABLE,
        ]);

        // Assert
        $this->assertSame([
            static::ID_PRODUCT_CONCRETE_AVAILABLE => true,
            static::ID_PRODUCT_CONCRETE_UNAVAILABLE => false,
        ], $result);
    }

    public function testReturnsEmptyArrayAndSkipsStorageForEmptyInput(): void
    {
        // Arrange
        $productConcreteAvailabilityReader = $this->createReader([], false);

        // Act
        $result = $productConcreteAvailabilityReader->getAvailabilityByProductConcreteIds([]);

        // Assert
        $this->assertSame([], $result);
    }

    public function testSkipsProductViewWithoutProductConcreteId(): void
    {
        // Arrange
        $productConcreteAvailabilityReader = $this->createReader([
            (new ProductViewTransfer())->setAvailable(false),
        ]);

        // Act
        $result = $productConcreteAvailabilityReader->getAvailabilityByProductConcreteIds([
            static::ID_PRODUCT_CONCRETE_AVAILABLE,
        ]);

        // Assert
        $this->assertSame([], $result);
    }

    public function testOmitsRequestedIdMissingFromStorageResponse(): void
    {
        // Arrange: storage skips restricted concretes, so the response can be shorter than the request.
        $productConcreteAvailabilityReader = $this->createReader([
            (new ProductViewTransfer())->setIdProductConcrete(static::ID_PRODUCT_CONCRETE_AVAILABLE)->setAvailable(true),
        ]);

        // Act
        $result = $productConcreteAvailabilityReader->getAvailabilityByProductConcreteIds([
            static::ID_PRODUCT_CONCRETE_AVAILABLE,
            static::ID_PRODUCT_CONCRETE_UNAVAILABLE,
        ]);

        // Assert
        $this->assertSame([static::ID_PRODUCT_CONCRETE_AVAILABLE => true], $result);
        $this->assertArrayNotHasKey(static::ID_PRODUCT_CONCRETE_UNAVAILABLE, $result);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductViewTransfer> $productViewTransfers
     */
    protected function createReader(array $productViewTransfers, bool $expectStorageCall = true): ProductConcreteAvailabilityReader
    {
        $productStorageClientMock = $this->createMock(ProductStorageClientInterface::class);
        $productStorageClientMock
            ->expects($expectStorageCall ? $this->once() : $this->never())
            ->method('getProductConcreteViewTransfers')
            ->willReturn($productViewTransfers);

        $localeClientMock = $this->createMock(LocaleClientInterface::class);
        $localeClientMock->method('getCurrentLocale')->willReturn(static::LOCALE_NAME);

        return new ProductConcreteAvailabilityReader($productStorageClientMock, $localeClientMock);
    }
}
