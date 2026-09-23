<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver;

interface ProductMeasurementSalesUnitCodeResolverInterface
{
    /**
     * @return array<int, \Generated\Shared\Transfer\ProductMeasurementSalesUnitTransfer>
     */
    public function findSalesUnitsByCode(int $idProductConcrete, string $code, string $storeName): array;
}
