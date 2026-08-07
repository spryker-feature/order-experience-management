<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift;

use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;

class BundlePriceDriftChecker extends AbstractPriceDriftChecker
{
    public function check(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        QuoteTransfer $originalQuoteTransfer,
        CartChangeTransfer $repricedCartChangeTransfer,
        string $priceMode,
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleValidationResultTransfer {
        $repricedBundleItemsByBundleIdentifier = $this->indexRepricedItemsByBundleIdentifier($repricedCartChangeTransfer);
        $scheduleItemsByBundleIdentifier = $this->recurringScheduleItemIndexer->indexByBundleItemIdentifier($recurringScheduleTransfer);

        foreach ($originalQuoteTransfer->getBundleItems() as $bundleItemTransfer) {
            $bundleItemIdentifier = $bundleItemTransfer->getBundleItemIdentifier();
            $recurringScheduleItemTransfer = $scheduleItemsByBundleIdentifier[$bundleItemIdentifier] ?? null;
            $repricedBundleItemTransfer = $repricedBundleItemsByBundleIdentifier[$bundleItemIdentifier] ?? null;

            if ($recurringScheduleItemTransfer === null || $repricedBundleItemTransfer === null) {
                continue;
            }

            $currentPrice = $this->resolveItemPriceByMode($repricedBundleItemTransfer, $priceMode);

            if ($currentPrice === null || $currentPrice === 0) {
                continue;
            }

            $recurringScheduleValidationResultTransfer = $this->addPriceReviewWhenDrifted(
                $recurringScheduleValidationResultTransfer,
                $recurringScheduleItemTransfer,
                $this->resolveReferencePriceByMode($recurringScheduleItemTransfer, $priceMode),
                $currentPrice,
            );
        }

        return $recurringScheduleValidationResultTransfer;
    }

    /**
     * @return array<string, \Generated\Shared\Transfer\ItemTransfer> Re-priced bundle parents keyed by bundle identifier.
     */
    protected function indexRepricedItemsByBundleIdentifier(CartChangeTransfer $repricedCartChangeTransfer): array
    {
        $repricedBundleItemsByBundleIdentifier = [];

        foreach ($repricedCartChangeTransfer->getItems() as $repricedItemTransfer) {
            $bundleItemIdentifier = $repricedItemTransfer->getBundleItemIdentifier();

            if ($bundleItemIdentifier === null) {
                continue;
            }

            $repricedBundleItemsByBundleIdentifier[$bundleItemIdentifier] = $repricedItemTransfer;
        }

        return $repricedBundleItemsByBundleIdentifier;
    }
}
