<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Reader;

use Generated\Shared\Transfer\ProductOfferTransfer;

interface AddedMerchantProductReaderInterface
{
    /**
     * Returns the concrete's owning merchant product as a selectable choice (a ProductOfferTransfer with an
     * empty product offer reference), mirroring the storefront buy box. Returns null when the concrete has no
     * owning merchant or when it is excluded as unavailable.
     */
    public function findMerchantProductChoice(string $sku): ?ProductOfferTransfer;
}
