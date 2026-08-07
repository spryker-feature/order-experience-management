<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Checker;

use Generated\Shared\Transfer\ProductViewTransfer;

class AddedProductMeasurementUnitChecker implements AddedProductMeasurementUnitCheckerInterface
{
    public function isRestricted(ProductViewTransfer $productViewTransfer): bool
    {
        return $productViewTransfer->getBaseUnit() !== null;
    }
}
