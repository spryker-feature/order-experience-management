<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Sanitizer;

use ArrayObject;
use Generated\Shared\Transfer\QuoteTransfer;

class QuoteSanitizer implements QuoteSanitizerInterface
{
    public function sanitize(QuoteTransfer $quoteTransfer): QuoteTransfer
    {
        return $quoteTransfer
            ->setVoucherCode(null)
            ->setVoucherDiscounts(new ArrayObject())
            ->setUsedNotAppliedVoucherCodes([]);
    }
}
