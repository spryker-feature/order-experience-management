<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift;

use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;

class ItemPriceDriftChecker extends AbstractPriceDriftChecker
{
    public function check(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        QuoteTransfer $originalQuoteTransfer,
        CartChangeTransfer $repricedCartChangeTransfer,
        string $priceMode,
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleValidationResultTransfer {
        $scheduleItemsByGroupKey = $this->recurringScheduleItemIndexer->indexByGroupKey($recurringScheduleTransfer);

        foreach ($repricedCartChangeTransfer->getItems() as $repricedItemTransfer) {
            $groupKey = $repricedItemTransfer->getGroupKey();

            if ($this->isBundleRelated($repricedItemTransfer) || $groupKey === null) {
                continue;
            }

            $recurringScheduleItemTransfer = $scheduleItemsByGroupKey[$groupKey] ?? null;

            if ($recurringScheduleItemTransfer === null) {
                continue;
            }

            $recurringScheduleValidationResultTransfer = $this->addPriceReviewWhenDrifted(
                $recurringScheduleValidationResultTransfer,
                $recurringScheduleItemTransfer,
                $this->resolveReferencePriceByMode($recurringScheduleItemTransfer, $priceMode),
                $this->resolveItemPriceByMode($repricedItemTransfer, $priceMode),
            );
        }

        return $recurringScheduleValidationResultTransfer;
    }

    protected function isBundleRelated(ItemTransfer $itemTransfer): bool
    {
        return $itemTransfer->getBundleItemIdentifier() !== null || $itemTransfer->getRelatedBundleItemIdentifier() !== null;
    }
}
