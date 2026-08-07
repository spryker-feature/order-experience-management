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
    public function buildShipmentMethodIdMapByMerchantReference(QuoteTransfer $quoteTransfer): array;

    /**
     * @param array<string, int> $shipmentMethodIdMap
     */
    public function applyShipmentMethodId(ItemTransfer $itemTransfer, array $shipmentMethodIdMap): void;

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<\Generated\Shared\Transfer\ItemTransfer>
     */
    public function applyFallbackShipments(array $itemTransfers, QuoteTransfer $quoteTransfer): array;

    /**
     * Copies each bundle child's shipment onto its bundle parent, so a rebuilt bundle ships as one unit.
     *
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<\Generated\Shared\Transfer\ItemTransfer>
     */
    public function alignBundleShipments(array $itemTransfers): array;
}
