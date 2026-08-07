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
use Spryker\Zed\Checkout\Business\CheckoutFacadeInterface;

class CheckoutPlaceabilityValidator implements CheckoutPlaceabilityValidatorInterface
{
    public function __construct(
        protected readonly CheckoutFacadeInterface $checkoutFacade,
        protected readonly CheckoutValidationResultBuilderInterface $checkoutValidationResultBuilder,
    ) {
    }

    public function validate(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        QuoteTransfer $quoteTransfer,
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleValidationResultTransfer {
        if ($quoteTransfer->getItems()->count() === 0 && $quoteTransfer->getBundleItems()->count() === 0) {
            return $this->checkoutValidationResultBuilder->buildEmptyOrderValidationResult(
                $recurringScheduleValidationResultTransfer,
            );
        }

        $checkoutResponseTransfer = $this->checkoutFacade->isPlaceableOrder($quoteTransfer);

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
