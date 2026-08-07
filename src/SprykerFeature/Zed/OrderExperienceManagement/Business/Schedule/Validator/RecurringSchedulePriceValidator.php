<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;

class RecurringSchedulePriceValidator implements RecurringSchedulePriceValidatorInterface
{
    /**
     * @param array<\SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift\PriceDriftCheckerInterface> $priceDriftCheckers
     */
    public function __construct(
        protected readonly ScheduleItemRepricerInterface $scheduleItemRepricer,
        protected readonly array $priceDriftCheckers,
    ) {
    }

    public function validate(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        QuoteTransfer $quoteTransfer,
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleValidationResultTransfer {
        $priceMode = (string)$quoteTransfer->getPriceMode();

        $repricedCartChangeTransfer = $this->scheduleItemRepricer->repriceQuoteItems($quoteTransfer);

        foreach ($this->priceDriftCheckers as $priceDriftChecker) {
            $recurringScheduleValidationResultTransfer = $priceDriftChecker->check(
                $recurringScheduleTransfer,
                $quoteTransfer,
                $repricedCartChangeTransfer,
                $priceMode,
                $recurringScheduleValidationResultTransfer,
            );
        }

        return $recurringScheduleValidationResultTransfer;
    }
}
