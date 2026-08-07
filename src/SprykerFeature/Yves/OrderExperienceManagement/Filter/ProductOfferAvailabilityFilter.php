<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Filter;

use Generated\Shared\Transfer\ProductOfferStorageTransfer;

class ProductOfferAvailabilityFilter implements ProductOfferAvailabilityFilterInterface
{
    /**
     * @param array<\Generated\Shared\Transfer\ProductOfferStorageTransfer> $productOfferStorageTransfers
     *
     * @return array<\Generated\Shared\Transfer\ProductOfferStorageTransfer>
     */
    public function filterAvailable(array $productOfferStorageTransfers): array
    {
        return array_values(array_filter(
            $productOfferStorageTransfers,
            static fn (ProductOfferStorageTransfer $productOfferStorageTransfer): bool => $productOfferStorageTransfer->getIsNeverOutOfStock() === true
                    || $productOfferStorageTransfer->getStockQuantity() === null
                    || (float)$productOfferStorageTransfer->getStockQuantity() > 0,
        ));
    }
}
