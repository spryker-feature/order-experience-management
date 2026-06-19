<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Checker;

use Generated\Shared\Transfer\CheckoutErrorTransfer;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\RecurringScheduleCheckoutValidatorInterface;

class RecurringOrderPreConditionChecker implements RecurringOrderPreConditionCheckerInterface
{
    public function __construct(protected readonly RecurringScheduleCheckoutValidatorInterface $recurringScheduleValidator)
    {
    }

    public function checkCondition(QuoteTransfer $quoteTransfer, CheckoutResponseTransfer $checkoutResponseTransfer): bool
    {
        $errorMessage = $this->recurringScheduleValidator->validateCheckout($quoteTransfer);

        if ($errorMessage === null) {
            return true;
        }

        $checkoutResponseTransfer->addError(
            (new CheckoutErrorTransfer())->setMessage($errorMessage),
        );

        return false;
    }
}
