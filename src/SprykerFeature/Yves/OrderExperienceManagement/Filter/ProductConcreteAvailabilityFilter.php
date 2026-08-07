<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Filter;

use Generated\Shared\Transfer\ProductConcretePageSearchTransfer;
use SprykerFeature\Yves\OrderExperienceManagement\Extractor\ProductConcreteIdExtractorInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\ProductConcreteAvailabilityReaderInterface;

class ProductConcreteAvailabilityFilter implements ProductConcreteAvailabilityFilterInterface
{
    public function __construct(
        protected ProductConcreteAvailabilityReaderInterface $productConcreteAvailabilityReader,
        protected ProductConcreteIdExtractorInterface $productConcreteIdExtractor,
    ) {
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductConcretePageSearchTransfer> $productConcretePageSearchTransfers
     *
     * @return array<\Generated\Shared\Transfer\ProductConcretePageSearchTransfer>
     */
    public function filterAvailable(array $productConcretePageSearchTransfers): array
    {
        if ($productConcretePageSearchTransfers === []) {
            return [];
        }

        $isAvailableByIdProductConcrete = $this->productConcreteAvailabilityReader->getAvailabilityByProductConcreteIds(
            $this->productConcreteIdExtractor->getProductConcreteIds($productConcretePageSearchTransfers),
        );

        return array_values(array_filter(
            $productConcretePageSearchTransfers,
            static fn (ProductConcretePageSearchTransfer $productConcretePageSearchTransfer): bool => ($isAvailableByIdProductConcrete[$productConcretePageSearchTransfer->getFkProduct()] ?? true) === true,
        ));
    }
}
