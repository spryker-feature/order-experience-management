<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator;

use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\RecurringOrderQuoteBuilderInterface;

class RecurringSchedulePriceValidator implements RecurringSchedulePriceValidatorInterface
{
    /**
     * @param array<\SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift\PriceDriftCheckerInterface> $priceDriftCheckers
     */
    public function __construct(
        protected readonly RecurringOrderQuoteBuilderInterface $quoteBuilder,
        protected readonly array $priceDriftCheckers,
    ) {
    }

    public function validate(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleValidationResultTransfer {
        if ($recurringScheduleTransfer->getQuoteData() === null) {
            return $recurringScheduleValidationResultTransfer;
        }

        $originalQuoteTransfer = $this->quoteBuilder->buildPlaceableQuote($recurringScheduleTransfer);
        $priceMode = (string)$originalQuoteTransfer->getPriceMode();

        foreach ($this->priceDriftCheckers as $priceDriftChecker) {
            $recurringScheduleValidationResultTransfer = $priceDriftChecker->check(
                $recurringScheduleTransfer,
                $originalQuoteTransfer,
                $priceMode,
                $recurringScheduleValidationResultTransfer,
            );
        }

        return $recurringScheduleValidationResultTransfer;
    }
}
