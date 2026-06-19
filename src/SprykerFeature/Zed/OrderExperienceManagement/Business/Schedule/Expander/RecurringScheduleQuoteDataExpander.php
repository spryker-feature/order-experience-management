<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander;

use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;

class RecurringScheduleQuoteDataExpander implements RecurringScheduleQuoteDataExpanderInterface
{
    public function __construct(protected readonly UtilEncodingServiceInterface $utilEncodingService)
    {
    }

    public function expandWithQuoteData(
        RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer,
    ): RecurringScheduleCollectionTransfer {
        foreach ($recurringScheduleCollectionTransfer->getRecurringSchedules() as $recurringScheduleTransfer) {
            $this->expandScheduleWithQuoteData($recurringScheduleTransfer);
        }

        return $recurringScheduleCollectionTransfer;
    }

    protected function expandScheduleWithQuoteData(RecurringScheduleTransfer $recurringScheduleTransfer): void
    {
        $quoteData = $recurringScheduleTransfer->getQuoteData();

        if ($quoteData === null) {
            return;
        }

        $quoteArray = $this->utilEncodingService->decodeJson($quoteData, true);
    }
}
