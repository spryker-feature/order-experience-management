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

class BundlePriceDriftChecker extends AbstractPriceDriftChecker
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
        $repricedBundleItemsByBundleIdentifier = $this->scheduleItemRepricer->repriceBundleItems($originalQuoteTransfer);
        $scheduleItemsByBundleIdentifier = $this->indexScheduleItemsByBundleIdentifier($recurringScheduleTransfer);

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
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleItemTransfer> Parent rows keyed by their bundle identifier.
     */
    protected function indexScheduleItemsByBundleIdentifier(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        $scheduleItemsByBundleIdentifier = [];

        foreach ($recurringScheduleTransfer->getItems() as $scheduleItemTransfer) {
            $bundleItemIdentifier = $scheduleItemTransfer->getBundleItemIdentifier();

            if ($bundleItemIdentifier === null) {
                continue;
            }

            $scheduleItemsByBundleIdentifier[$bundleItemIdentifier] = $scheduleItemTransfer;
        }

        return $scheduleItemsByBundleIdentifier;
    }
}
