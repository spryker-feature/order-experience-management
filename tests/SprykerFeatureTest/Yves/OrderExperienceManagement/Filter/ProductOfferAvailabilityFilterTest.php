<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Filter;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductOfferStorageTransfer;
use SprykerFeature\Yves\OrderExperienceManagement\Filter\ProductOfferAvailabilityFilter;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Filter
 * @group ProductOfferAvailabilityFilterTest
 */
class ProductOfferAvailabilityFilterTest extends Unit
{
    public function testFilterAvailableKeepsInStockAndNeverOutOfStockButDropsOutOfStock(): void
    {
        // Arrange
        $productOfferStorageTransfers = [
            (new ProductOfferStorageTransfer())->setProductOfferReference('in-stock')->setStockQuantity(5.0)->setIsNeverOutOfStock(false),
            (new ProductOfferStorageTransfer())->setProductOfferReference('never-out-of-stock')->setStockQuantity(0.0)->setIsNeverOutOfStock(true),
            (new ProductOfferStorageTransfer())->setProductOfferReference('out-of-stock')->setStockQuantity(0.0)->setIsNeverOutOfStock(false),
        ];

        // Act
        $result = (new ProductOfferAvailabilityFilter())->filterAvailable($productOfferStorageTransfers);

        // Assert
        $productOfferReferences = array_map(
            static fn (ProductOfferStorageTransfer $productOfferStorageTransfer): ?string => $productOfferStorageTransfer->getProductOfferReference(),
            $result,
        );
        $this->assertSame(['in-stock', 'never-out-of-stock'], $productOfferReferences);
    }

    public function testFilterAvailableKeepsOfferWhenStockQuantityIsUnknown(): void
    {
        // Arrange: neither availability field populated (fail-open).
        $productOfferStorageTransfers = [
            (new ProductOfferStorageTransfer())->setProductOfferReference('unknown'),
        ];

        // Act
        $result = (new ProductOfferAvailabilityFilter())->filterAvailable($productOfferStorageTransfers);

        // Assert
        $this->assertCount(1, $result);
        $this->assertSame('unknown', $result[0]->getProductOfferReference());
    }

    public function testFilterAvailableReturnsEmptyArrayForEmptyInput(): void
    {
        $this->assertSame([], (new ProductOfferAvailabilityFilter())->filterAvailable([]));
    }
}
