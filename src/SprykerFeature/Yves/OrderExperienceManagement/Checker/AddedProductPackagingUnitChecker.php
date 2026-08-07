<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Checker;

use Generated\Shared\Transfer\ProductPackagingUnitStorageConditionsTransfer;
use Generated\Shared\Transfer\ProductPackagingUnitStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductViewTransfer;
use Spryker\Client\ProductPackagingUnitStorage\ProductPackagingUnitStorageClientInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Extractor\ProductConcreteIdExtractorInterface;

class AddedProductPackagingUnitChecker implements AddedProductPackagingUnitCheckerInterface
{
    public function __construct(
        protected readonly ProductPackagingUnitStorageClientInterface $productPackagingUnitStorageClient,
        protected readonly ProductConcreteIdExtractorInterface $productConcreteIdExtractor,
    ) {
    }

    public function isRestricted(ProductViewTransfer $productViewTransfer): bool
    {
        $idProductConcrete = $productViewTransfer->getIdProductConcrete();

        if ($idProductConcrete === null) {
            return false;
        }

        return $this->productPackagingUnitStorageClient->findProductPackagingUnitById($idProductConcrete) !== null;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductViewTransfer> $productViewTransfers
     *
     * @return array<int, bool>
     */
    public function getRestrictionsByProductConcreteId(array $productViewTransfers): array
    {
        $productConcreteIds = $this->productConcreteIdExtractor->getProductConcreteIdsFromProductViews($productViewTransfers);

        if ($productConcreteIds === []) {
            return [];
        }

        $restrictedProductConcreteIds = $this->getPackagedProductConcreteIds($productConcreteIds);

        $isRestrictedByIdProductConcrete = [];

        foreach ($productConcreteIds as $idProductConcrete) {
            $isRestrictedByIdProductConcrete[$idProductConcrete] = isset($restrictedProductConcreteIds[$idProductConcrete]);
        }

        return $isRestrictedByIdProductConcrete;
    }

    /**
     * @param array<int> $productConcreteIds
     *
     * @return array<int, true> Keyed by product concrete id.
     */
    protected function getPackagedProductConcreteIds(array $productConcreteIds): array
    {
        $productPackagingUnitStorageCriteriaTransfer = (new ProductPackagingUnitStorageCriteriaTransfer())
            ->setProductPackagingUnitStorageConditions(
                (new ProductPackagingUnitStorageConditionsTransfer())->setProductIds($productConcreteIds),
            );

        $productPackagingUnitStorageCollectionTransfer = $this->productPackagingUnitStorageClient
            ->getProductPackagingUnitStorageCollection($productPackagingUnitStorageCriteriaTransfer);

        $packagedProductConcreteIds = [];

        foreach ($productPackagingUnitStorageCollectionTransfer->getProductPackagingUnitStorages() as $productPackagingUnitStorageTransfer) {
            $idProduct = $productPackagingUnitStorageTransfer->getIdProduct();

            if ($idProduct !== null) {
                $packagedProductConcreteIds[$idProduct] = true;
            }
        }

        return $packagedProductConcreteIds;
    }
}
