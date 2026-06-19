<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

interface ItemShipmentMethodResolverInterface
{
    /**
     * @return array<string, int> Shipment method id keyed by the expense merchant reference.
     */
    public function buildExpenseShipmentMethodMap(QuoteTransfer $quoteTransfer): array;

    /**
     * @param array<string, int> $shipmentMethodIdMap
     */
    public function applyShipmentMethodId(ItemTransfer $itemTransfer, array $shipmentMethodIdMap): void;
}
