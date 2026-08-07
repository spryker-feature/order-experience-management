<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Quote;

use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface;

class StandingScheduleQuoteOverrideApplier implements StandingScheduleQuoteOverrideApplierInterface
{
    public function __construct(
        protected readonly OrderExperienceManagementEntityManagerInterface $entityManager,
        protected readonly RecurringScheduleQuoteDataMergerInterface $recurringScheduleQuoteDataMerger,
    ) {
    }

    public function applyStandingQuoteOverride(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
    ): void {
        $quoteOverrideTransfer = $recurringScheduleEventRequestTransfer->getQuote();

        if ($quoteOverrideTransfer === null || $quoteOverrideTransfer->modifiedToArray(true, true) === []) {
            return;
        }

        $recurringScheduleTransfer = $this->recurringScheduleQuoteDataMerger->applyQuoteOverride(
            $recurringScheduleTransfer,
            $quoteOverrideTransfer,
        );

        $this->entityManager->updateRecurringScheduleQuoteData(
            $recurringScheduleTransfer->getIdRecurringScheduleOrFail(),
            $recurringScheduleTransfer->getQuoteDataOrFail(),
        );
    }
}
