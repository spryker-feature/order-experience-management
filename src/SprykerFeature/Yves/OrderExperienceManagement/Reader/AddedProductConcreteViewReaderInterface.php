<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Reader;

use Generated\Shared\Transfer\ProductViewTransfer;

interface AddedProductConcreteViewReaderInterface
{
    public function findProductConcreteView(string $sku): ?ProductViewTransfer;

    /**
     * @param array<int> $productConcreteIds
     *
     * @return array<string, \Generated\Shared\Transfer\ProductViewTransfer>
     */
    public function getProductConcreteViewsBySku(array $productConcreteIds): array;
}
