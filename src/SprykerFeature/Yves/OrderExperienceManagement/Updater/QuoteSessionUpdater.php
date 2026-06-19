<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Updater;

use Generated\Shared\Transfer\RecurringOrderQuoteUpdateResponseTransfer;
use Spryker\Client\Quote\QuoteClientInterface;

class QuoteSessionUpdater implements QuoteSessionUpdaterInterface
{
    public function __construct(protected readonly QuoteClientInterface $quoteClient)
    {
    }

    public function updateFromResponse(RecurringOrderQuoteUpdateResponseTransfer $recurringOrderQuoteUpdateResponseTransfer): void
    {
        $quoteTransfer = $this->quoteClient->getQuote();
        $quoteTransfer->fromArray($recurringOrderQuoteUpdateResponseTransfer->getQuoteOrFail()->modifiedToArray(), true);
        $this->quoteClient->setQuote($quoteTransfer);
    }
}
