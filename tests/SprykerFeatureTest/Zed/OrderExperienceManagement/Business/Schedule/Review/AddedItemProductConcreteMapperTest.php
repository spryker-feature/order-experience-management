<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ItemTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Mapper\AddedItemProductConcreteMapper;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Review
 * @group AddedItemProductConcreteMapperTest
 * Add your own group annotations below this line
 */
class AddedItemProductConcreteMapperTest extends Unit
{
    protected const string SKU = '215_124';

    protected const int ID_PRODUCT_CONCRETE = 298;

    protected const int ID_PRODUCT_ABSTRACT = 215;

    /**
     * The item transfer has no `idProductConcrete` property at all — the concrete id lives on `id`. Reading the
     * wrong one would silently produce transfers the measurement and packaging facades reject.
     */
    public function testMapsTheItemIdToTheProductConcreteId(): void
    {
        // Arrange
        $itemTransfers = [$this->createItem(static::SKU, static::ID_PRODUCT_CONCRETE, static::ID_PRODUCT_ABSTRACT)];

        // Act
        $productConcreteTransfers = (new AddedItemProductConcreteMapper())->mapItemTransfersToProductConcreteTransfers($itemTransfers);

        // Assert
        $this->assertSame([static::SKU], array_keys($productConcreteTransfers));
        $this->assertSame(static::ID_PRODUCT_CONCRETE, $productConcreteTransfers[static::SKU]->getIdProductConcrete());
        $this->assertSame(static::ID_PRODUCT_ABSTRACT, $productConcreteTransfers[static::SKU]->getFkProductAbstract());
    }

    public function testSkipsItemWithoutConcreteId(): void
    {
        // Arrange
        $itemTransfers = [$this->createItem(static::SKU, null, static::ID_PRODUCT_ABSTRACT)];

        // Act
        $productConcreteTransfers = (new AddedItemProductConcreteMapper())->mapItemTransfersToProductConcreteTransfers($itemTransfers);

        // Assert
        $this->assertSame([], $productConcreteTransfers);
    }

    public function testSkipsItemWithoutAbstractId(): void
    {
        // Arrange
        $itemTransfers = [$this->createItem(static::SKU, static::ID_PRODUCT_CONCRETE, null)];

        // Act
        $productConcreteTransfers = (new AddedItemProductConcreteMapper())->mapItemTransfersToProductConcreteTransfers($itemTransfers);

        // Assert
        $this->assertSame([], $productConcreteTransfers);
    }

    public function testSkipsItemWithoutSku(): void
    {
        // Arrange
        $itemTransfers = [$this->createItem(null, static::ID_PRODUCT_CONCRETE, static::ID_PRODUCT_ABSTRACT)];

        // Act
        $productConcreteTransfers = (new AddedItemProductConcreteMapper())->mapItemTransfersToProductConcreteTransfers($itemTransfers);

        // Assert
        $this->assertSame([], $productConcreteTransfers);
    }

    public function testKeysEveryResolvedItemBySkuIncludingBundledOnes(): void
    {
        // Arrange
        $itemTransfers = [
            $this->createItem(static::SKU, static::ID_PRODUCT_CONCRETE, static::ID_PRODUCT_ABSTRACT),
            $this->createItem('218_1230', 303, 218),
        ];

        // Act
        $productConcreteTransfers = (new AddedItemProductConcreteMapper())->mapItemTransfersToProductConcreteTransfers($itemTransfers);

        // Assert
        $this->assertSame([static::SKU, '218_1230'], array_keys($productConcreteTransfers));
    }

    protected function createItem(?string $sku, ?int $idProductConcrete, ?int $idProductAbstract): ItemTransfer
    {
        return (new ItemTransfer())
            ->setSku($sku)
            ->setId($idProductConcrete)
            ->setIdProductAbstract($idProductAbstract);
    }
}
