<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Checker;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductPackagingUnitStorageCollectionTransfer;
use Generated\Shared\Transfer\ProductPackagingUnitStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductPackagingUnitStorageTransfer;
use Generated\Shared\Transfer\ProductViewTransfer;
use Spryker\Client\ProductPackagingUnitStorage\ProductPackagingUnitStorageClientInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Checker\AddedProductPackagingUnitChecker;
use SprykerFeature\Yves\OrderExperienceManagement\Extractor\ProductConcreteIdExtractor;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Checker
 * @group AddedProductPackagingUnitCheckerTest
 */
class AddedProductPackagingUnitCheckerTest extends Unit
{
    protected const int ID_PRODUCT_CONCRETE = 324;

    protected const int ID_PRODUCT_CONCRETE_SECOND = 325;

    public function testProductViewWithPackagingUnitInStorageIsRestricted(): void
    {
        // Arrange
        $productViewTransfer = (new ProductViewTransfer())->setIdProductConcrete(static::ID_PRODUCT_CONCRETE);

        $addedProductPackagingUnitChecker = new AddedProductPackagingUnitChecker(
            $this->createProductPackagingUnitStorageClientMock(new ProductPackagingUnitStorageTransfer()),
            new ProductConcreteIdExtractor(),
        );

        // Act
        $isRestricted = $addedProductPackagingUnitChecker->isRestricted($productViewTransfer);

        // Assert
        $this->assertTrue($isRestricted);
    }

    public function testProductViewWithoutPackagingUnitInStorageIsNotRestricted(): void
    {
        // Arrange
        $productViewTransfer = (new ProductViewTransfer())->setIdProductConcrete(static::ID_PRODUCT_CONCRETE);

        $addedProductPackagingUnitChecker = new AddedProductPackagingUnitChecker(
            $this->createProductPackagingUnitStorageClientMock(null),
            new ProductConcreteIdExtractor(),
        );

        // Act
        $isRestricted = $addedProductPackagingUnitChecker->isRestricted($productViewTransfer);

        // Assert
        $this->assertFalse($isRestricted);
    }

    public function testProductViewWithoutConcreteIdSkipsTheStorageRead(): void
    {
        // Arrange
        $productPackagingUnitStorageClientMock = $this->createMock(ProductPackagingUnitStorageClientInterface::class);
        $productPackagingUnitStorageClientMock->expects($this->never())->method('findProductPackagingUnitById');

        $addedProductPackagingUnitChecker = new AddedProductPackagingUnitChecker($productPackagingUnitStorageClientMock, new ProductConcreteIdExtractor());

        // Act
        $isRestricted = $addedProductPackagingUnitChecker->isRestricted(new ProductViewTransfer());

        // Assert
        $this->assertFalse($isRestricted);
    }

    public function testGetRestrictionsByProductConcreteIdReadsEveryProductInOneRequest(): void
    {
        // Arrange
        $productViewTransfers = [
            (new ProductViewTransfer())->setIdProductConcrete(static::ID_PRODUCT_CONCRETE),
            (new ProductViewTransfer())->setIdProductConcrete(static::ID_PRODUCT_CONCRETE_SECOND),
        ];

        $addedProductPackagingUnitChecker = new AddedProductPackagingUnitChecker(
            $this->createCollectionClientMock([static::ID_PRODUCT_CONCRETE], 1),
            new ProductConcreteIdExtractor(),
        );

        // Act
        $isRestrictedByIdProductConcrete = $addedProductPackagingUnitChecker->getRestrictionsByProductConcreteId($productViewTransfers);

        // Assert
        $this->assertSame(
            [static::ID_PRODUCT_CONCRETE => true, static::ID_PRODUCT_CONCRETE_SECOND => false],
            $isRestrictedByIdProductConcrete,
        );
    }

    public function testGetRestrictionsByProductConcreteIdPassesTheProductIdsAsCriteria(): void
    {
        // Arrange
        $productViewTransfers = [(new ProductViewTransfer())->setIdProductConcrete(static::ID_PRODUCT_CONCRETE)];

        $productPackagingUnitStorageClientMock = $this->createMock(ProductPackagingUnitStorageClientInterface::class);
        $productPackagingUnitStorageClientMock
            ->method('getProductPackagingUnitStorageCollection')
            ->willReturnCallback(function (ProductPackagingUnitStorageCriteriaTransfer $criteriaTransfer): ProductPackagingUnitStorageCollectionTransfer {
                $this->assertSame(
                    [static::ID_PRODUCT_CONCRETE],
                    $criteriaTransfer->getProductPackagingUnitStorageConditionsOrFail()->getProductIds(),
                );

                return new ProductPackagingUnitStorageCollectionTransfer();
            });

        $addedProductPackagingUnitChecker = new AddedProductPackagingUnitChecker($productPackagingUnitStorageClientMock, new ProductConcreteIdExtractor());

        // Act
        $isRestrictedByIdProductConcrete = $addedProductPackagingUnitChecker->getRestrictionsByProductConcreteId($productViewTransfers);

        // Assert
        $this->assertSame([static::ID_PRODUCT_CONCRETE => false], $isRestrictedByIdProductConcrete);
    }

    public function testGetRestrictionsByProductConcreteIdSkipsProductViewsWithoutConcreteId(): void
    {
        // Arrange
        $addedProductPackagingUnitChecker = new AddedProductPackagingUnitChecker(
            $this->createCollectionClientMock([static::ID_PRODUCT_CONCRETE], 1),
            new ProductConcreteIdExtractor(),
        );

        // Act
        $isRestrictedByIdProductConcrete = $addedProductPackagingUnitChecker->getRestrictionsByProductConcreteId([
            new ProductViewTransfer(),
            (new ProductViewTransfer())->setIdProductConcrete(static::ID_PRODUCT_CONCRETE),
        ]);

        // Assert
        $this->assertSame([static::ID_PRODUCT_CONCRETE => true], $isRestrictedByIdProductConcrete);
    }

    public function testGetRestrictionsByProductConcreteIdSkipsTheStorageReadWithoutResolvableIds(): void
    {
        // Arrange
        $addedProductPackagingUnitChecker = new AddedProductPackagingUnitChecker(
            $this->createCollectionClientMock([], 0),
            new ProductConcreteIdExtractor(),
        );

        // Act
        $isRestrictedByIdProductConcrete = $addedProductPackagingUnitChecker->getRestrictionsByProductConcreteId([
            new ProductViewTransfer(),
        ]);

        // Assert
        $this->assertSame([], $isRestrictedByIdProductConcrete);
    }

    /**
     * @param array<int> $packagedProductConcreteIds
     */
    protected function createCollectionClientMock(
        array $packagedProductConcreteIds,
        int $expectedCallCount,
    ): ProductPackagingUnitStorageClientInterface {
        $productPackagingUnitStorageCollectionTransfer = new ProductPackagingUnitStorageCollectionTransfer();

        foreach ($packagedProductConcreteIds as $idProductConcrete) {
            $productPackagingUnitStorageCollectionTransfer->addProductPackagingUnitStorage(
                (new ProductPackagingUnitStorageTransfer())->setIdProduct($idProductConcrete),
            );
        }

        $productPackagingUnitStorageClientMock = $this->createMock(ProductPackagingUnitStorageClientInterface::class);
        $productPackagingUnitStorageClientMock
            ->expects($this->exactly($expectedCallCount))
            ->method('getProductPackagingUnitStorageCollection')
            ->willReturn($productPackagingUnitStorageCollectionTransfer);

        return $productPackagingUnitStorageClientMock;
    }

    protected function createProductPackagingUnitStorageClientMock(
        ?ProductPackagingUnitStorageTransfer $productPackagingUnitStorageTransfer,
    ): ProductPackagingUnitStorageClientInterface {
        $productPackagingUnitStorageClientMock = $this->createMock(ProductPackagingUnitStorageClientInterface::class);
        $productPackagingUnitStorageClientMock
            ->method('findProductPackagingUnitById')
            ->with(static::ID_PRODUCT_CONCRETE)
            ->willReturn($productPackagingUnitStorageTransfer);

        return $productPackagingUnitStorageClientMock;
    }
}
