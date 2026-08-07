<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Filter;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductConcretePageSearchTransfer;
use SprykerFeature\Yves\OrderExperienceManagement\Extractor\ProductConcreteIdExtractor;
use SprykerFeature\Yves\OrderExperienceManagement\Filter\ProductConcreteAvailabilityFilter;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\ProductConcreteAvailabilityReaderInterface;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Filter
 * @group ProductConcreteAvailabilityFilterTest
 */
class ProductConcreteAvailabilityFilterTest extends Unit
{
    protected const int ID_PRODUCT_CONCRETE_AVAILABLE = 1;

    protected const int ID_PRODUCT_CONCRETE_UNAVAILABLE = 2;

    public function testFilterAvailableKeepsOnlyAvailableProducts(): void
    {
        // Arrange
        $productConcretePageSearchTransfers = [
            (new ProductConcretePageSearchTransfer())->setFkProduct(static::ID_PRODUCT_CONCRETE_AVAILABLE)->setSku('sku-available'),
            (new ProductConcretePageSearchTransfer())->setFkProduct(static::ID_PRODUCT_CONCRETE_UNAVAILABLE)->setSku('sku-unavailable'),
        ];
        $productConcreteAvailabilityFilter = $this->createFilter([
            static::ID_PRODUCT_CONCRETE_AVAILABLE => true,
            static::ID_PRODUCT_CONCRETE_UNAVAILABLE => false,
        ]);

        // Act
        $result = $productConcreteAvailabilityFilter->filterAvailable($productConcretePageSearchTransfers);

        // Assert
        $this->assertCount(1, $result);
        $this->assertSame('sku-available', $result[0]->getSku());
    }

    public function testFilterAvailableKeepsProductWhenAvailabilityCannotBeResolved(): void
    {
        // Arrange: the reader resolves no availability for the product concrete (fail-open).
        $productConcretePageSearchTransfers = [
            (new ProductConcretePageSearchTransfer())->setFkProduct(static::ID_PRODUCT_CONCRETE_AVAILABLE)->setSku('sku-unknown'),
        ];
        $productConcreteAvailabilityFilter = $this->createFilter([]);

        // Act
        $result = $productConcreteAvailabilityFilter->filterAvailable($productConcretePageSearchTransfers);

        // Assert
        $this->assertCount(1, $result);
        $this->assertSame('sku-unknown', $result[0]->getSku());
    }

    public function testFilterAvailableReturnsEmptyArrayAndSkipsStorageForEmptyInput(): void
    {
        // Arrange
        $productConcreteAvailabilityFilter = $this->createFilter([], false);

        // Act
        $result = $productConcreteAvailabilityFilter->filterAvailable([]);

        // Assert
        $this->assertSame([], $result);
    }

    /**
     * @param array<int, bool> $isAvailableByIdProductConcrete
     */
    protected function createFilter(array $isAvailableByIdProductConcrete, bool $expectReaderCall = true): ProductConcreteAvailabilityFilter
    {
        $productConcreteAvailabilityReaderMock = $this->createMock(ProductConcreteAvailabilityReaderInterface::class);
        $productConcreteAvailabilityReaderMock
            ->expects($expectReaderCall ? $this->once() : $this->never())
            ->method('getAvailabilityByProductConcreteIds')
            ->willReturn($isAvailableByIdProductConcrete);

        return new ProductConcreteAvailabilityFilter($productConcreteAvailabilityReaderMock, new ProductConcreteIdExtractor());
    }
}
