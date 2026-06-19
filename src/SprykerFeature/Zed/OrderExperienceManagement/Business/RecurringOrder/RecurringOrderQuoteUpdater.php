<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\RecurringOrder;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\QuoteResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringOrderQuoteUpdateRequestTransfer;
use Generated\Shared\Transfer\RecurringOrderQuoteUpdateResponseTransfer;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;

class RecurringOrderQuoteUpdater implements RecurringOrderQuoteUpdaterInterface
{
    protected const string GLOSSARY_KEY_QUOTE_NOT_FOUND = 'recurring_orders.error.quote_not_found';

    public function __construct(protected readonly QuoteFacadeInterface $quoteFacade)
    {
    }

    public function updateRecurringOrderSettingsOnQuote(
        RecurringOrderQuoteUpdateRequestTransfer $recurringOrderQuoteUpdateRequestTransfer
    ): RecurringOrderQuoteUpdateResponseTransfer {
        $quoteResponseTransfer = $this->quoteFacade->findQuoteById($recurringOrderQuoteUpdateRequestTransfer->getIdQuoteOrFail());

        if (!$quoteResponseTransfer->getIsSuccessful() || $quoteResponseTransfer->getQuoteTransfer() === null) {
            return (new RecurringOrderQuoteUpdateResponseTransfer())
                ->setIsSuccessful(false)
                ->addError((new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_QUOTE_NOT_FOUND));
        }

        $quoteTransfer = $this->applyRequestToQuote(
            $recurringOrderQuoteUpdateRequestTransfer,
            $quoteResponseTransfer->getQuoteTransferOrFail(),
        );

        $savedQuoteResponseTransfer = $this->quoteFacade->updateQuote($quoteTransfer);

        return $this->mapQuoteResponseToUpdateResponse($savedQuoteResponseTransfer, new RecurringOrderQuoteUpdateResponseTransfer());
    }

    protected function applyRequestToQuote(
        RecurringOrderQuoteUpdateRequestTransfer $recurringOrderQuoteUpdateRequestTransfer,
        QuoteTransfer $quoteTransfer,
    ): QuoteTransfer {
        $quoteTransfer->setRecurringOrderSettings($recurringOrderQuoteUpdateRequestTransfer->getRecurringOrderSettings());

        if ($quoteTransfer->getCustomer() === null) {
            $quoteTransfer->setCustomer($recurringOrderQuoteUpdateRequestTransfer->getCustomer());
        }

        return $quoteTransfer;
    }

    protected function mapQuoteResponseToUpdateResponse(
        QuoteResponseTransfer $quoteResponseTransfer,
        RecurringOrderQuoteUpdateResponseTransfer $recurringOrderQuoteUpdateResponseTransfer,
    ): RecurringOrderQuoteUpdateResponseTransfer {
        $recurringOrderQuoteUpdateResponseTransfer->setIsSuccessful($quoteResponseTransfer->getIsSuccessful() ?? false);

        foreach ($quoteResponseTransfer->getErrors() as $quoteErrorTransfer) {
            $recurringOrderQuoteUpdateResponseTransfer->addError(
                (new ErrorTransfer())->setMessage($quoteErrorTransfer->getMessage()),
            );
        }

        if ($quoteResponseTransfer->getIsSuccessful()) {
            $recurringOrderQuoteUpdateResponseTransfer->setQuote($quoteResponseTransfer->getQuoteTransfer());
        }

        return $recurringOrderQuoteUpdateResponseTransfer;
    }
}
