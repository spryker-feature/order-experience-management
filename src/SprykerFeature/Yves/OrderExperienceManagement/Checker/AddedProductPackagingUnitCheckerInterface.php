<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Checker;

use Generated\Shared\Transfer\ProductViewTransfer;

interface AddedProductPackagingUnitCheckerInterface
{
    public function isRestricted(ProductViewTransfer $productViewTransfer): bool;

    /**
     * @param array<\Generated\Shared\Transfer\ProductViewTransfer> $productViewTransfers
     *
     * @return array<int, bool> Keyed by product concrete id.
     */
    public function getRestrictionsByProductConcreteId(array $productViewTransfers): array;
}
