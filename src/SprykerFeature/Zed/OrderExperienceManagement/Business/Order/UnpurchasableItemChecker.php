<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order;

use Generated\Shared\Transfer\QuoteTransfer;

class UnpurchasableItemChecker implements UnpurchasableItemCheckerInterface
{
    /**
     * @return list<string>
     */
    public function getUnpurchasableSkus(QuoteTransfer $expectedQuoteTransfer, QuoteTransfer $reloadedQuoteTransfer): array
    {
        $expectedSkuQuantities = $this->mapItemSkuQuantities($expectedQuoteTransfer);
        $reloadedSkuQuantities = $this->mapItemSkuQuantities($reloadedQuoteTransfer);
        $unpurchasableSkus = [];

        foreach ($expectedSkuQuantities as $sku => $expectedQuantity) {
            if (($reloadedSkuQuantities[$sku] ?? 0) >= $expectedQuantity) {
                continue;
            }

            $unpurchasableSkus[] = $sku;
        }

        return $unpurchasableSkus;
    }

    /**
     * @return array<string, int>
     */
    protected function mapItemSkuQuantities(QuoteTransfer $quoteTransfer): array
    {
        $skuQuantities = [];

        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $sku = $itemTransfer->getSkuOrFail();
            $skuQuantities[$sku] = ($skuQuantities[$sku] ?? 0) + $itemTransfer->getQuantityOrFail();
        }

        return $skuQuantities;
    }
}
