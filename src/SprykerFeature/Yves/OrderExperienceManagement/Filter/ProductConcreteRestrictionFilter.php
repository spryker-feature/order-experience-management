<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Filter;

use Generated\Shared\Transfer\ProductConcretePageSearchTransfer;
use SprykerFeature\Yves\OrderExperienceManagement\Checker\AddedProductConcreteRestrictionCheckerInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Extractor\ProductConcreteIdExtractorInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedProductConcreteViewReaderInterface;

class ProductConcreteRestrictionFilter implements ProductConcreteRestrictionFilterInterface
{
    public function __construct(
        protected readonly AddedProductConcreteViewReaderInterface $addedProductConcreteViewReader,
        protected readonly AddedProductConcreteRestrictionCheckerInterface $addedProductConcreteRestrictionChecker,
        protected readonly ProductConcreteIdExtractorInterface $productConcreteIdExtractor,
    ) {
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductConcretePageSearchTransfer> $productConcretePageSearchTransfers
     *
     * @return array<\Generated\Shared\Transfer\ProductConcretePageSearchTransfer>
     */
    public function filterUnrestricted(array $productConcretePageSearchTransfers): array
    {
        if ($productConcretePageSearchTransfers === []) {
            return [];
        }

        if (!$this->addedProductConcreteRestrictionChecker->isAnyRestrictionEnabled()) {
            return $productConcretePageSearchTransfers;
        }

        $isRestrictedBySku = $this->buildRestrictionMap($productConcretePageSearchTransfers);

        return array_values(array_filter(
            $productConcretePageSearchTransfers,
            static fn (ProductConcretePageSearchTransfer $productConcretePageSearchTransfer): bool => ($isRestrictedBySku[$productConcretePageSearchTransfer->getSku()] ?? false) === false,
        ));
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductConcretePageSearchTransfer> $productConcretePageSearchTransfers
     *
     * @return array<string, bool>
     */
    protected function buildRestrictionMap(array $productConcretePageSearchTransfers): array
    {
        $productViewTransfersBySku = $this->addedProductConcreteViewReader->getProductConcreteViewsBySku(
            $this->productConcreteIdExtractor->getProductConcreteIds($productConcretePageSearchTransfers),
        );

        return $this->addedProductConcreteRestrictionChecker->getRestrictionsBySku($productViewTransfersBySku);
    }
}
