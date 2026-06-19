<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Sanitizer;

use Generated\Shared\Transfer\QuoteTransfer;

interface QuoteSanitizerInterface
{
    public function sanitize(QuoteTransfer $quoteTransfer): QuoteTransfer;
}
