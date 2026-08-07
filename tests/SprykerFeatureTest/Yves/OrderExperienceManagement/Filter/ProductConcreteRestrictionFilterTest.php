<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Filter;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductConcretePageSearchTransfer;
use Generated\Shared\Transfer\ProductViewTransfer;
use SprykerFeature\Yves\OrderExperienceManagement\Checker\AddedProductConcreteRestrictionCheckerInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Extractor\ProductConcreteIdExtractor;
use SprykerFeature\Yves\OrderExperienceManagement\Filter\ProductConcreteRestrictionFilter;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedProductConcreteViewReaderInterface;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Filter
 * @group ProductConcreteRestrictionFilterTest
 */
class ProductConcreteRestrictionFilterTest extends Unit
{
    protected const string SKU_UNRESTRICTED = 'sku-unrestricted';

    protected const string SKU_RESTRICTED = 'service-001-1';

    protected const int ID_PRODUCT_UNRESTRICTED = 11;

    protected const int ID_PRODUCT_RESTRICTED = 22;

    public function testFilterUnrestrictedDropsOnlyTheProductsTheCheckerRestricts(): void
    {
        // Arrange
        $productConcretePageSearchTransfers = [
            $this->createSearchResult(static::SKU_RESTRICTED, static::ID_PRODUCT_RESTRICTED),
            $this->createSearchResult(static::SKU_UNRESTRICTED, static::ID_PRODUCT_UNRESTRICTED),
        ];

        $productConcreteRestrictionFilter = new ProductConcreteRestrictionFilter(
            $this->createProductConcreteViewReaderMock([
                static::SKU_RESTRICTED => (new ProductViewTransfer())->setSku(static::SKU_RESTRICTED),
                static::SKU_UNRESTRICTED => (new ProductViewTransfer())->setSku(static::SKU_UNRESTRICTED),
            ]),
            $this->createRestrictionCheckerMock([static::SKU_RESTRICTED => true, static::SKU_UNRESTRICTED => false]),
            new ProductConcreteIdExtractor(),
        );

        // Act
        $result = $productConcreteRestrictionFilter->filterUnrestricted($productConcretePageSearchTransfers);

        // Assert
        $this->assertSame([static::SKU_UNRESTRICTED], array_map(
            static fn (ProductConcretePageSearchTransfer $productConcretePageSearchTransfer): ?string => $productConcretePageSearchTransfer->getSku(),
            $result,
        ));
    }

    public function testFilterUnrestrictedKeepsProductsMissingFromStorage(): void
    {
        // Arrange
        $productConcretePageSearchTransfers = [$this->createSearchResult(static::SKU_UNRESTRICTED, static::ID_PRODUCT_UNRESTRICTED)];

        $productConcreteRestrictionFilter = new ProductConcreteRestrictionFilter(
            $this->createProductConcreteViewReaderMock([]),
            $this->createRestrictionCheckerMock([]),
            new ProductConcreteIdExtractor(),
        );

        // Act
        $result = $productConcreteRestrictionFilter->filterUnrestricted($productConcretePageSearchTransfers);

        // Assert
        $this->assertSame($productConcretePageSearchTransfers, $result);
    }

    public function testFilterUnrestrictedSkipsTheStorageLookupForAnEmptyResultSet(): void
    {
        // Arrange
        $addedProductConcreteViewReaderMock = $this->createMock(AddedProductConcreteViewReaderInterface::class);
        $addedProductConcreteViewReaderMock->expects($this->never())->method('getProductConcreteViewsBySku');

        $productConcreteRestrictionFilter = new ProductConcreteRestrictionFilter(
            $addedProductConcreteViewReaderMock,
            $this->createMock(AddedProductConcreteRestrictionCheckerInterface::class),
            new ProductConcreteIdExtractor(),
        );

        // Act
        $result = $productConcreteRestrictionFilter->filterUnrestricted([]);

        // Assert
        $this->assertSame([], $result);
    }

    /**
     * Resolving the product views is a bulk storage read, so it must not happen when nothing can restrict.
     */
    public function testFilterUnrestrictedSkipsTheStorageLookupWhenNoRestrictionIsEnabled(): void
    {
        // Arrange
        $productConcretePageSearchTransfers = [
            $this->createSearchResult(static::SKU_RESTRICTED, static::ID_PRODUCT_RESTRICTED),
            $this->createSearchResult(static::SKU_UNRESTRICTED, static::ID_PRODUCT_UNRESTRICTED),
        ];

        $addedProductConcreteViewReaderMock = $this->createMock(AddedProductConcreteViewReaderInterface::class);
        $addedProductConcreteViewReaderMock->expects($this->never())->method('getProductConcreteViewsBySku');

        $addedProductConcreteRestrictionCheckerMock = $this->createMock(AddedProductConcreteRestrictionCheckerInterface::class);
        $addedProductConcreteRestrictionCheckerMock->method('isAnyRestrictionEnabled')->willReturn(false);
        $addedProductConcreteRestrictionCheckerMock->expects($this->never())->method('getRestrictionsBySku');

        $productConcreteRestrictionFilter = new ProductConcreteRestrictionFilter(
            $addedProductConcreteViewReaderMock,
            $addedProductConcreteRestrictionCheckerMock,
            new ProductConcreteIdExtractor(),
        );

        // Act
        $result = $productConcreteRestrictionFilter->filterUnrestricted($productConcretePageSearchTransfers);

        // Assert
        $this->assertSame($productConcretePageSearchTransfers, $result);
    }

    protected function createSearchResult(string $sku, int $idProductConcrete): ProductConcretePageSearchTransfer
    {
        return (new ProductConcretePageSearchTransfer())->setSku($sku)->setFkProduct($idProductConcrete);
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\ProductViewTransfer> $productViewTransfersBySku
     */
    protected function createProductConcreteViewReaderMock(array $productViewTransfersBySku): AddedProductConcreteViewReaderInterface
    {
        $addedProductConcreteViewReaderMock = $this->createMock(AddedProductConcreteViewReaderInterface::class);
        $addedProductConcreteViewReaderMock->method('getProductConcreteViewsBySku')->willReturn($productViewTransfersBySku);

        return $addedProductConcreteViewReaderMock;
    }

    /**
     * @param array<string, bool> $isRestrictedBySku
     */
    protected function createRestrictionCheckerMock(array $isRestrictedBySku): AddedProductConcreteRestrictionCheckerInterface
    {
        $addedProductConcreteRestrictionCheckerMock = $this->createMock(AddedProductConcreteRestrictionCheckerInterface::class);
        $addedProductConcreteRestrictionCheckerMock->method('isAnyRestrictionEnabled')->willReturn(true);
        $addedProductConcreteRestrictionCheckerMock
            ->expects($this->once())
            ->method('getRestrictionsBySku')
            ->willReturn($isRestrictedBySku);

        return $addedProductConcreteRestrictionCheckerMock;
    }
}
