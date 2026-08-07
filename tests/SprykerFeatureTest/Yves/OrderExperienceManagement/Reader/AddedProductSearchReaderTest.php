<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Reader;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductConcretePageSearchTransfer;
use Spryker\Client\Catalog\CatalogClientInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Filter\ProductConcreteAvailabilityFilterInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Filter\ProductConcreteRestrictionFilterInterface;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedProductSearchReader;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Reader
 * @group AddedProductSearchReaderTest
 */
class AddedProductSearchReaderTest extends Unit
{
    protected const string RESULT_FORMATTER_KEY = 'ProductConcreteCatalogSearchResultFormatterPlugin';

    public function testSearchReturnsUnfilteredResultsWhenFlagDisabled(): void
    {
        // Arrange
        $productConcretePageSearchTransfers = [(new ProductConcretePageSearchTransfer())->setSku('sku-1')];

        $productConcreteAvailabilityFilterMock = $this->createMock(ProductConcreteAvailabilityFilterInterface::class);
        $productConcreteAvailabilityFilterMock->expects($this->never())->method('filterAvailable');

        $addedProductSearchReader = new AddedProductSearchReader(
            $this->createCatalogClientMock($productConcretePageSearchTransfers),
            $productConcreteAvailabilityFilterMock,
            $this->createConfigMock(false),
            $this->createProductConcreteRestrictionFilterMock(),
        );

        // Act
        $result = $addedProductSearchReader->searchAvailableProductConcretes('sku', 10, []);

        // Assert
        $this->assertSame($productConcretePageSearchTransfers, $result);
    }

    public function testSearchAppliesAvailabilityFilterWhenFlagEnabled(): void
    {
        // Arrange
        $foundProductConcretePageSearchTransfers = [
            (new ProductConcretePageSearchTransfer())->setSku('sku-1'),
            (new ProductConcretePageSearchTransfer())->setSku('sku-2'),
        ];
        $filteredProductConcretePageSearchTransfers = [$foundProductConcretePageSearchTransfers[0]];

        $productConcreteAvailabilityFilterMock = $this->createMock(ProductConcreteAvailabilityFilterInterface::class);
        $productConcreteAvailabilityFilterMock
            ->expects($this->once())
            ->method('filterAvailable')
            ->with($foundProductConcretePageSearchTransfers)
            ->willReturn($filteredProductConcretePageSearchTransfers);

        $addedProductSearchReader = new AddedProductSearchReader(
            $this->createCatalogClientMock($foundProductConcretePageSearchTransfers),
            $productConcreteAvailabilityFilterMock,
            $this->createConfigMock(true),
            $this->createProductConcreteRestrictionFilterMock(),
        );

        // Act
        $result = $addedProductSearchReader->searchAvailableProductConcretes('sku', 10, []);

        // Assert
        $this->assertSame($filteredProductConcretePageSearchTransfers, $result);
    }

    /**
     * The restriction plugins express domain rules rather than an availability preference, so they must apply
     * even when unavailable products are allowed in the picker.
     */
    public function testSearchAppliesRestrictionFilterWhenAvailabilityFlagDisabled(): void
    {
        // Arrange
        $foundProductConcretePageSearchTransfers = [
            (new ProductConcretePageSearchTransfer())->setSku('sku-1'),
            (new ProductConcretePageSearchTransfer())->setSku('service-001-1'),
        ];
        $unrestrictedProductConcretePageSearchTransfers = [$foundProductConcretePageSearchTransfers[0]];

        $productConcreteRestrictionFilterMock = $this->createMock(ProductConcreteRestrictionFilterInterface::class);
        $productConcreteRestrictionFilterMock
            ->expects($this->once())
            ->method('filterUnrestricted')
            ->with($foundProductConcretePageSearchTransfers)
            ->willReturn($unrestrictedProductConcretePageSearchTransfers);

        $addedProductSearchReader = new AddedProductSearchReader(
            $this->createCatalogClientMock($foundProductConcretePageSearchTransfers),
            $this->createMock(ProductConcreteAvailabilityFilterInterface::class),
            $this->createConfigMock(false),
            $productConcreteRestrictionFilterMock,
        );

        // Act
        $result = $addedProductSearchReader->searchAvailableProductConcretes('sku', 10, []);

        // Assert
        $this->assertSame($unrestrictedProductConcretePageSearchTransfers, $result);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductConcretePageSearchTransfer> $productConcretePageSearchTransfers
     */
    protected function createCatalogClientMock(array $productConcretePageSearchTransfers): CatalogClientInterface
    {
        $catalogClientMock = $this->createMock(CatalogClientInterface::class);
        $catalogClientMock
            ->method('searchProductConcretesByFullText')
            ->willReturn([static::RESULT_FORMATTER_KEY => $productConcretePageSearchTransfers]);

        return $catalogClientMock;
    }

    protected function createConfigMock(bool $isExclusionEnabled): OrderExperienceManagementConfig
    {
        $configMock = $this->createMock(OrderExperienceManagementConfig::class);
        $configMock->method('isUnavailableProductsExcludedFromAddProductSearch')->willReturn($isExclusionEnabled);

        return $configMock;
    }

    protected function createProductConcreteRestrictionFilterMock(): ProductConcreteRestrictionFilterInterface
    {
        $productConcreteRestrictionFilterMock = $this->createMock(ProductConcreteRestrictionFilterInterface::class);
        $productConcreteRestrictionFilterMock->method('filterUnrestricted')->willReturnArgument(0);

        return $productConcreteRestrictionFilterMock;
    }
}
