<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Reader;

use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;

class ProductConcreteAvailabilityReader implements ProductConcreteAvailabilityReaderInterface
{
    public function __construct(
        protected ProductStorageClientInterface $productStorageClient,
        protected LocaleClientInterface $localeClient,
    ) {
    }

    /**
     * @param array<int> $productConcreteIds
     *
     * @return array<int, bool>
     */
    public function getAvailabilityByProductConcreteIds(array $productConcreteIds): array
    {
        if ($productConcreteIds === []) {
            return [];
        }

        $productViewTransfers = $this->productStorageClient->getProductConcreteViewTransfers(
            $productConcreteIds,
            $this->localeClient->getCurrentLocale(),
        );

        $isAvailableByIdProductConcrete = [];

        foreach ($productViewTransfers as $productViewTransfer) {
            $idProductConcrete = $productViewTransfer->getIdProductConcrete();

            if ($idProductConcrete === null) {
                continue;
            }

            $isAvailableByIdProductConcrete[$idProductConcrete] = (bool)$productViewTransfer->getAvailable();
        }

        return $isAvailableByIdProductConcrete;
    }
}
