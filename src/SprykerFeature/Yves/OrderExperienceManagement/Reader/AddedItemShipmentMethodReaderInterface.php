<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Reader;

use Generated\Shared\Transfer\CustomerTransfer;

interface AddedItemShipmentMethodReaderInterface
{
    /**
     * @return array<\Generated\Shared\Transfer\ShipmentMethodTransfer>
     */
    public function getShipmentMethods(
        string $uuid,
        string $sku,
        ?string $productOfferReference,
        ?string $shippingAddressKey,
        ?int $idShippingAddress,
        CustomerTransfer $customerTransfer,
    ): array;
}
