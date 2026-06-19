<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\ScheduleItemRepricerInterface;

class ItemPriceDriftChecker extends AbstractPriceDriftChecker
{
    public function __construct(
        protected readonly ScheduleItemRepricerInterface $scheduleItemRepricer,
    ) {
    }

    public function check(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        QuoteTransfer $originalQuoteTransfer,
        string $priceMode,
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleValidationResultTransfer {
        $repricedCartChangeTransfer = $this->scheduleItemRepricer->repriceItems($originalQuoteTransfer);
        $scheduleItemsByGroupKey = $this->indexScheduleItemsByGroupKey($recurringScheduleTransfer);

        foreach ($repricedCartChangeTransfer->getItems() as $repricedItemTransfer) {
            $groupKey = $repricedItemTransfer->getGroupKey();

            if ($repricedItemTransfer->getRelatedBundleItemIdentifier() !== null || $groupKey === null) {
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

    /**
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    protected function indexScheduleItemsByGroupKey(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        $scheduleItemsByGroupKey = [];

        foreach ($recurringScheduleTransfer->getItems() as $scheduleItemTransfer) {
            $groupKey = $scheduleItemTransfer->getGroupKey();

            if ($groupKey === null) {
                continue;
            }

            $scheduleItemsByGroupKey[$groupKey] = $scheduleItemTransfer;
        }

        return $scheduleItemsByGroupKey;
    }
}
