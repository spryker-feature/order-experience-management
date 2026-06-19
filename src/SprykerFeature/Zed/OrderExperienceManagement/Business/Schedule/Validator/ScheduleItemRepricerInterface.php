<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator;

use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

interface ScheduleItemRepricerInterface
{
    public function repriceItems(QuoteTransfer $quoteTransfer): CartChangeTransfer;

    /**
     * @return array<string, \Generated\Shared\Transfer\ItemTransfer> Re-priced bundle parents keyed by bundle identifier.
     */
    public function repriceBundleItems(QuoteTransfer $quoteTransfer): array;
}
