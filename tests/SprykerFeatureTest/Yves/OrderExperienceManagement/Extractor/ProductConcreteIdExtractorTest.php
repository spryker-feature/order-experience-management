<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Extractor;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductConcretePageSearchTransfer;
use Generated\Shared\Transfer\ProductViewTransfer;
use SprykerFeature\Yves\OrderExperienceManagement\Extractor\ProductConcreteIdExtractor;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Extractor
 * @group ProductConcreteIdExtractorTest
 */
class ProductConcreteIdExtractorTest extends Unit
{
    protected const int ID_PRODUCT_FIRST = 11;

    protected const int ID_PRODUCT_SECOND = 22;

    public function testReturnsIdsInInputOrder(): void
    {
        // Arrange
        $productConcretePageSearchTransfers = [
            (new ProductConcretePageSearchTransfer())->setFkProduct(static::ID_PRODUCT_SECOND),
            (new ProductConcretePageSearchTransfer())->setFkProduct(static::ID_PRODUCT_FIRST),
        ];

        // Act
        $result = (new ProductConcreteIdExtractor())->getProductConcreteIds($productConcretePageSearchTransfers);

        // Assert
        $this->assertSame([static::ID_PRODUCT_SECOND, static::ID_PRODUCT_FIRST], $result);
    }

    public function testSkipsSearchResultWithoutProductConcreteId(): void
    {
        // Arrange
        $productConcretePageSearchTransfers = [
            new ProductConcretePageSearchTransfer(),
            (new ProductConcretePageSearchTransfer())->setFkProduct(static::ID_PRODUCT_FIRST),
        ];

        // Act
        $result = (new ProductConcreteIdExtractor())->getProductConcreteIds($productConcretePageSearchTransfers);

        // Assert: the list stays gapless so it can be passed straight to a storage read.
        $this->assertSame([static::ID_PRODUCT_FIRST], $result);
    }

    public function testReturnsEmptyArrayForEmptyInput(): void
    {
        // Act
        $result = (new ProductConcreteIdExtractor())->getProductConcreteIds([]);

        // Assert
        $this->assertSame([], $result);
    }

    public function testReturnsProductViewIdsInInputOrder(): void
    {
        // Arrange
        $productViewTransfers = [
            (new ProductViewTransfer())->setIdProductConcrete(static::ID_PRODUCT_SECOND),
            (new ProductViewTransfer())->setIdProductConcrete(static::ID_PRODUCT_FIRST),
        ];

        // Act
        $result = (new ProductConcreteIdExtractor())->getProductConcreteIdsFromProductViews($productViewTransfers);

        // Assert
        $this->assertSame([static::ID_PRODUCT_SECOND, static::ID_PRODUCT_FIRST], $result);
    }

    public function testSkipsProductViewWithoutProductConcreteId(): void
    {
        // Arrange
        $productViewTransfers = [
            new ProductViewTransfer(),
            (new ProductViewTransfer())->setIdProductConcrete(static::ID_PRODUCT_FIRST),
        ];

        // Act
        $result = (new ProductConcreteIdExtractor())->getProductConcreteIdsFromProductViews($productViewTransfers);

        // Assert: the list stays gapless so it can be passed straight to a storage read.
        $this->assertSame([static::ID_PRODUCT_FIRST], $result);
    }

    public function testReturnsEmptyArrayForEmptyProductViewInput(): void
    {
        // Act
        $result = (new ProductConcreteIdExtractor())->getProductConcreteIdsFromProductViews([]);

        // Assert
        $this->assertSame([], $result);
    }
}
