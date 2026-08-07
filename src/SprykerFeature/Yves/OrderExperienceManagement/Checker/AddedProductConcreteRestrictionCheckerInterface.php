<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Checker;

use Generated\Shared\Transfer\ProductViewTransfer;

interface AddedProductConcreteRestrictionCheckerInterface
{
    public function isProductConcreteRestricted(string $sku): bool;

    public function isProductViewRestricted(ProductViewTransfer $productViewTransfer): bool;

    /**
     * @param array<string, \Generated\Shared\Transfer\ProductViewTransfer> $productViewTransfersBySku
     *
     * @return array<string, bool>
     */
    public function getRestrictionsBySku(array $productViewTransfersBySku): array;

    public function isAnyRestrictionEnabled(): bool;
}
