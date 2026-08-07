<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Quote;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;

class RecurringScheduleQuoteDataMerger implements RecurringScheduleQuoteDataMergerInterface
{
    public function __construct(
        protected readonly UtilEncodingServiceInterface $utilEncodingService,
    ) {
    }

    public function applyQuoteOverride(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        ?QuoteTransfer $quoteOverrideTransfer,
    ): RecurringScheduleTransfer {
        if ($quoteOverrideTransfer === null) {
            return $recurringScheduleTransfer;
        }

        $overrideData = $quoteOverrideTransfer->modifiedToArray(true, true);
        $quoteData = $recurringScheduleTransfer->getQuoteData();

        if ($overrideData === [] || $quoteData === null) {
            return $recurringScheduleTransfer;
        }

        $decodedQuoteData = (array)$this->utilEncodingService->decodeJson($quoteData, true);
        $decodedQuoteData = array_replace($decodedQuoteData, $overrideData);

        return $recurringScheduleTransfer->setQuoteData(
            (string)$this->utilEncodingService->encodeJson($decodedQuoteData),
        );
    }
}
