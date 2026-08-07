<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Spryker\Zed\ProductPackagingUnit\Business\ProductPackagingUnitFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Mapper\AddedItemProductConcreteMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Validator\AddedItemProductPackagingUnitValidator;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Review
 * @group AddedItemProductPackagingUnitValidatorTest
 * Add your own group annotations below this line
 */
class AddedItemProductPackagingUnitValidatorTest extends Unit
{
    protected const string SKU = '215_124';

    protected const string SKU_SECOND = '215_125';

    protected const string GLOSSARY_KEY_PACKAGING_UNIT_NOT_SUPPORTED = 'recurring_orders.review.add_product.error.packaging_unit_not_supported';

    public function testAcceptsAdditionWhenEveryProductPassesTheFilter(): void
    {
        // Arrange
        $productConcreteTransfers = $this->createProductConcreteTransfersBySku([static::SKU, static::SKU_SECOND]);

        $addedItemProductPackagingUnitValidator = new AddedItemProductPackagingUnitValidator(
            $this->createFacadeMock(array_values($productConcreteTransfers), 1),
            $this->createMapperMock($productConcreteTransfers),
        );

        // Act
        $errorTransfer = $addedItemProductPackagingUnitValidator->validate($this->createItemTransfers(), static::SKU);

        // Assert
        $this->assertNull($errorTransfer);
    }

    public function testRejectsAdditionWhenTheFilterDropsOneProduct(): void
    {
        // Arrange
        $productConcreteTransfers = $this->createProductConcreteTransfersBySku([static::SKU, static::SKU_SECOND]);

        $addedItemProductPackagingUnitValidator = new AddedItemProductPackagingUnitValidator(
            $this->createFacadeMock([$productConcreteTransfers[static::SKU_SECOND]], 1),
            $this->createMapperMock($productConcreteTransfers),
        );

        // Act
        $errorTransfer = $addedItemProductPackagingUnitValidator->validate($this->createItemTransfers(), static::SKU);

        // Assert
        $this->assertNotNull($errorTransfer);
        $this->assertSame(static::GLOSSARY_KEY_PACKAGING_UNIT_NOT_SUPPORTED, $errorTransfer->getMessage());
        $this->assertSame(['%sku%' => static::SKU], $errorTransfer->getParameters());
    }

    public function testRejectsAdditionWhenTheFilterDropsEveryProduct(): void
    {
        // Arrange
        $addedItemProductPackagingUnitValidator = new AddedItemProductPackagingUnitValidator(
            $this->createFacadeMock([], 1),
            $this->createMapperMock($this->createProductConcreteTransfersBySku([static::SKU])),
        );

        // Act
        $errorTransfer = $addedItemProductPackagingUnitValidator->validate($this->createItemTransfers(), static::SKU);

        // Assert
        $this->assertNotNull($errorTransfer);
        $this->assertSame(static::GLOSSARY_KEY_PACKAGING_UNIT_NOT_SUPPORTED, $errorTransfer->getMessage());
    }

    /**
     * The facade call is a database read, so an unresolvable product must not reach it.
     */
    public function testSkipsTheFacadeWhenTheMapperResolvesNoProduct(): void
    {
        // Arrange
        $addedItemProductPackagingUnitValidator = new AddedItemProductPackagingUnitValidator(
            $this->createFacadeMock([], 0),
            $this->createMapperMock([]),
        );

        // Act
        $errorTransfer = $addedItemProductPackagingUnitValidator->validate($this->createItemTransfers(), static::SKU);

        // Assert
        $this->assertNull($errorTransfer);
    }

    public function testSkipsTheFacadeForAnEmptyItemList(): void
    {
        // Arrange
        $addedItemProductPackagingUnitValidator = new AddedItemProductPackagingUnitValidator(
            $this->createFacadeMock([], 0),
            $this->createMapperMock([]),
        );

        // Act
        $errorTransfer = $addedItemProductPackagingUnitValidator->validate([], static::SKU);

        // Assert
        $this->assertNull($errorTransfer);
    }

    /**
     * The mapper returns a SKU-keyed map while the facade expects a list, so the keys must be dropped.
     */
    public function testPassesTheProductConcretesToTheFacadeAsAList(): void
    {
        // Arrange
        $productConcreteTransfers = $this->createProductConcreteTransfersBySku([static::SKU, static::SKU_SECOND]);

        $productPackagingUnitFacadeMock = $this->createMock(ProductPackagingUnitFacadeInterface::class);
        $productPackagingUnitFacadeMock
            ->method('filterProductsWithoutPackagingUnit')
            ->willReturnCallback(function (array $passedProductConcreteTransfers) use ($productConcreteTransfers): array {
                $this->assertSame([0, 1], array_keys($passedProductConcreteTransfers));

                return array_values($productConcreteTransfers);
            });

        $addedItemProductPackagingUnitValidator = new AddedItemProductPackagingUnitValidator(
            $productPackagingUnitFacadeMock,
            $this->createMapperMock($productConcreteTransfers),
        );

        // Act
        $errorTransfer = $addedItemProductPackagingUnitValidator->validate($this->createItemTransfers(), static::SKU);

        // Assert
        $this->assertNull($errorTransfer);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductConcreteTransfer> $unrestrictedProductConcreteTransfers
     */
    protected function createFacadeMock(
        array $unrestrictedProductConcreteTransfers,
        int $expectedCallCount,
    ): ProductPackagingUnitFacadeInterface {
        $productPackagingUnitFacadeMock = $this->createMock(ProductPackagingUnitFacadeInterface::class);
        $productPackagingUnitFacadeMock
            ->expects($this->exactly($expectedCallCount))
            ->method('filterProductsWithoutPackagingUnit')
            ->willReturn($unrestrictedProductConcreteTransfers);

        return $productPackagingUnitFacadeMock;
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\ProductConcreteTransfer> $productConcreteTransfersBySku
     */
    protected function createMapperMock(array $productConcreteTransfersBySku): AddedItemProductConcreteMapperInterface
    {
        $addedItemProductConcreteMapperMock = $this->createMock(AddedItemProductConcreteMapperInterface::class);
        $addedItemProductConcreteMapperMock
            ->method('mapItemTransfersToProductConcreteTransfers')
            ->willReturn($productConcreteTransfersBySku);

        return $addedItemProductConcreteMapperMock;
    }

    /**
     * @param array<string> $skus
     *
     * @return array<string, \Generated\Shared\Transfer\ProductConcreteTransfer>
     */
    protected function createProductConcreteTransfersBySku(array $skus): array
    {
        $productConcreteTransfersBySku = [];

        foreach ($skus as $sku) {
            $productConcreteTransfersBySku[$sku] = (new ProductConcreteTransfer())->setSku($sku);
        }

        return $productConcreteTransfersBySku;
    }

    /**
     * @return array<\Generated\Shared\Transfer\ItemTransfer>
     */
    protected function createItemTransfers(): array
    {
        return [
            (new ItemTransfer())->setSku(static::SKU),
            (new ItemTransfer())->setSku(static::SKU_SECOND),
        ];
    }
}
