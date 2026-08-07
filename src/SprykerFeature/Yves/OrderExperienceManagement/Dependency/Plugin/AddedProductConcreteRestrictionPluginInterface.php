<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Dependency\Plugin;

use Generated\Shared\Transfer\ProductViewTransfer;

interface AddedProductConcreteRestrictionPluginInterface
{
    /**
     * Specification:
     * - Checks whether the product concrete must not be offered for adding to a recurring schedule.
     * - The product view is resolved from product storage with all view expanders applied, so plugins can rely on
     *   the data their own module publishes there.
     * - Returning true removes the product from the add-product search results and makes the offer selector
     *   return no choices for it.
     * - Restrictions that must also survive a crafted request belong additionally to
     *   {@link \SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\AddedItemValidatorPluginInterface}.
     *
     * @api
     */
    public function isRestricted(ProductViewTransfer $productViewTransfer): bool;
}
