<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Validator;

use Generated\Shared\Transfer\ErrorTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

class AddedItemProductUnitValidator implements AddedItemProductUnitValidatorInterface
{
    public function __construct(
        protected readonly AddedItemProductMeasurementUnitValidatorInterface $addedItemProductMeasurementUnitValidator,
        protected readonly AddedItemProductPackagingUnitValidatorInterface $addedItemProductPackagingUnitValidator,
        protected readonly OrderExperienceManagementConfig $orderExperienceManagementConfig,
    ) {
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     */
    public function validate(array $itemTransfers, string $sku): ?ErrorTransfer
    {
        return $this->validateMeasurementUnit($itemTransfers, $sku)
            ?? $this->validatePackagingUnit($itemTransfers, $sku);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     */
    protected function validateMeasurementUnit(array $itemTransfers, string $sku): ?ErrorTransfer
    {
        if (!$this->orderExperienceManagementConfig->isMeasurementUnitProductAdditionRestricted()) {
            return null;
        }

        return $this->addedItemProductMeasurementUnitValidator->validate($itemTransfers, $sku);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     */
    protected function validatePackagingUnit(array $itemTransfers, string $sku): ?ErrorTransfer
    {
        if (!$this->orderExperienceManagementConfig->isPackagingUnitProductAdditionRestricted()) {
            return null;
        }

        return $this->addedItemProductPackagingUnitValidator->validate($itemTransfers, $sku);
    }
}
