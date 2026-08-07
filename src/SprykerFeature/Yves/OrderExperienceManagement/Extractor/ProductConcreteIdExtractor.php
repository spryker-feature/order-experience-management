<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Extractor;

class ProductConcreteIdExtractor implements ProductConcreteIdExtractorInterface
{
    /**
     * @param array<\Generated\Shared\Transfer\ProductConcretePageSearchTransfer> $productConcretePageSearchTransfers
     *
     * @return array<int>
     */
    public function getProductConcreteIds(array $productConcretePageSearchTransfers): array
    {
        $productConcreteIds = [];

        foreach ($productConcretePageSearchTransfers as $productConcretePageSearchTransfer) {
            $idProductConcrete = $productConcretePageSearchTransfer->getFkProduct();

            if ($idProductConcrete !== null) {
                $productConcreteIds[] = $idProductConcrete;
            }
        }

        return $productConcreteIds;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductViewTransfer> $productViewTransfers
     *
     * @return array<int>
     */
    public function getProductConcreteIdsFromProductViews(array $productViewTransfers): array
    {
        $productConcreteIds = [];

        foreach ($productViewTransfers as $productViewTransfer) {
            $idProductConcrete = $productViewTransfer->getIdProductConcrete();

            if ($idProductConcrete !== null) {
                $productConcreteIds[] = $idProductConcrete;
            }
        }

        return $productConcreteIds;
    }
}
