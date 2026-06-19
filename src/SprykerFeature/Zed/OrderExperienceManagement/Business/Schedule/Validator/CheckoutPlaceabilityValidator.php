<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator;

use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use Spryker\Zed\Checkout\Business\CheckoutFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\RecurringOrderQuoteBuilderInterface;

class CheckoutPlaceabilityValidator implements CheckoutPlaceabilityValidatorInterface
{
    public function __construct(
        protected readonly CheckoutFacadeInterface $checkoutFacade,
        protected readonly RecurringOrderQuoteBuilderInterface $quoteBuilder,
        protected readonly CheckoutValidationResultBuilderInterface $checkoutValidationResultBuilder,
    ) {
    }

    public function validate(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleValidationResultTransfer {
        if ($recurringScheduleTransfer->getQuoteData() === null) {
            return $recurringScheduleValidationResultTransfer;
        }

        $checkoutResponseTransfer = $this->checkoutFacade->isPlaceableOrder(
            $this->quoteBuilder->buildPlaceableQuote($recurringScheduleTransfer),
        );

        if ($checkoutResponseTransfer->getErrors()->count() === 0) {
            return $recurringScheduleValidationResultTransfer;
        }

        return $this->checkoutValidationResultBuilder->buildValidationResult(
            $checkoutResponseTransfer,
            $recurringScheduleTransfer,
            $recurringScheduleValidationResultTransfer,
        );
    }
}
