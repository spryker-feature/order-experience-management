<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Resolver;

use Generated\Shared\Transfer\ProductOfferStorageTransfer;
use Spryker\Client\ProductOfferStorage\ProductOfferStorageClientInterface;

class ProductOfferStorageResolver implements ProductOfferStorageResolverInterface
{
    public function __construct(protected readonly ProductOfferStorageClientInterface $productOfferStorageClient)
    {
    }

    public function resolveProductOfferStorage(?string $productOfferReference): ?ProductOfferStorageTransfer
    {
        if ($productOfferReference === null || $productOfferReference === '') {
            return null;
        }

        return $this->productOfferStorageClient->findProductOfferStorageByReference($productOfferReference);
    }
}
