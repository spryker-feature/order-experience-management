<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator;

use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

class OrderIntakeQuoteValidator implements OrderIntakeQuoteValidatorInterface
{
    public function __construct(
        protected readonly OrderIntakeProductOfferValidatorInterface $orderIntakeProductOfferValidator,
        protected readonly OrderIntakeMerchantValidatorInterface $orderIntakeMerchantValidator,
    ) {
    }

    public function validateQuote(
        QuoteTransfer $quoteTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer
    ): QuoteTransfer {
        $quoteTransfer = $this->orderIntakeProductOfferValidator->validateProductOffers(
            $quoteTransfer,
            $orderIntakeResponseTransfer,
        );

        return $this->orderIntakeMerchantValidator->validateMerchants(
            $quoteTransfer,
            $orderIntakeResponseTransfer,
        );
    }
}
