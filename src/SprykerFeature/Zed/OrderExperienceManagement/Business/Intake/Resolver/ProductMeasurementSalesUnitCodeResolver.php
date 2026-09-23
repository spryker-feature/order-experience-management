<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver;

use Generated\Shared\Transfer\ProductMeasurementSalesUnitTransfer;
use Spryker\Zed\ProductMeasurementUnit\Business\ProductMeasurementUnitFacadeInterface;

/**
 * Turns a measurement unit code into the sales unit it names, for one product in one store.
 *
 * @see \Spryker\Zed\ProductMeasurementUnit\Business\CartChange\Checker\ItemProductMeasurementSalesUnitChecker
 */
class ProductMeasurementSalesUnitCodeResolver implements ProductMeasurementSalesUnitCodeResolverInterface
{
    public function __construct(
        protected readonly ProductMeasurementUnitFacadeInterface $productMeasurementUnitFacade,
    ) {
    }

    public function findSalesUnitsByCode(int $idProductConcrete, string $code, string $storeName): array
    {
        $matchedProductMeasurementSalesUnitTransfers = [];

        foreach ($this->productMeasurementUnitFacade->getSalesUnitsByIdProduct($idProductConcrete) as $productMeasurementSalesUnitTransfer) {
            if ($productMeasurementSalesUnitTransfer->getProductMeasurementUnit()?->getCode() !== $code) {
                continue;
            }

            if (!$this->isAvailableInStore($productMeasurementSalesUnitTransfer, $storeName)) {
                continue;
            }

            $matchedProductMeasurementSalesUnitTransfers[] = $productMeasurementSalesUnitTransfer;
        }

        return $matchedProductMeasurementSalesUnitTransfers;
    }

    protected function isAvailableInStore(
        ProductMeasurementSalesUnitTransfer $productMeasurementSalesUnitTransfer,
        string $storeName,
    ): bool {
        $storeTransfers = $productMeasurementSalesUnitTransfer->getStoreRelation()?->getStores();

        if ($storeTransfers === null || $storeTransfers->count() === 0) {
            return true;
        }

        foreach ($storeTransfers as $storeTransfer) {
            if ($storeTransfer->getName() === $storeName) {
                return true;
            }
        }

        return false;
    }
}
