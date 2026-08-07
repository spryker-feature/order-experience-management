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
use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Date\FirstTriggerDateResolverInterface;

class RecurringOrderQuoteUpdater implements RecurringOrderQuoteUpdaterInterface
{
    protected const string GLOSSARY_KEY_QUOTE_NOT_FOUND = 'recurring_orders.error.quote_not_found';

    protected const string DATE_FORMAT = 'Y-m-d';

    public function __construct(
        protected readonly QuoteFacadeInterface $quoteFacade,
        protected readonly CadenceResolverInterface $cadenceResolver,
        protected readonly FirstTriggerDateResolverInterface $firstTriggerDateResolver,
    ) {
    }

    public function updateRecurringOrderSettingsOnQuote(
        RecurringOrderQuoteUpdateRequestTransfer $recurringOrderQuoteUpdateRequestTransfer
    ): RecurringOrderQuoteUpdateResponseTransfer {
        $quoteResponseTransfer = $this->quoteFacade->findQuoteById($recurringOrderQuoteUpdateRequestTransfer->getIdQuoteOrFail());

        if (!$quoteResponseTransfer->getIsSuccessful() || $quoteResponseTransfer->getQuoteTransfer() === null) {
            return $this->createQuoteNotFoundResponse();
        }

        $quoteTransfer = $quoteResponseTransfer->getQuoteTransferOrFail();

        if (!$this->isQuoteOwnedByRequestCustomer($quoteTransfer, $recurringOrderQuoteUpdateRequestTransfer)) {
            return $this->createQuoteNotFoundResponse();
        }

        $quoteTransfer = $this->applyRequestToQuote($recurringOrderQuoteUpdateRequestTransfer, $quoteTransfer);

        $savedQuoteResponseTransfer = $this->quoteFacade->updateQuote($quoteTransfer);

        return $this->mapQuoteResponseToUpdateResponse($savedQuoteResponseTransfer, new RecurringOrderQuoteUpdateResponseTransfer());
    }

    protected function isQuoteOwnedByRequestCustomer(
        QuoteTransfer $quoteTransfer,
        RecurringOrderQuoteUpdateRequestTransfer $recurringOrderQuoteUpdateRequestTransfer,
    ): bool {
        $quoteCustomerReference = $quoteTransfer->getCustomerReference()
            ?? $quoteTransfer->getCustomer()?->getCustomerReference();

        if ($quoteCustomerReference === null) {
            return true;
        }

        return $quoteCustomerReference === $recurringOrderQuoteUpdateRequestTransfer->getCustomer()?->getCustomerReference();
    }

    protected function createQuoteNotFoundResponse(): RecurringOrderQuoteUpdateResponseTransfer
    {
        return (new RecurringOrderQuoteUpdateResponseTransfer())
            ->setIsSuccessful(false)
            ->addError((new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_QUOTE_NOT_FOUND));
    }

    protected function applyRequestToQuote(
        RecurringOrderQuoteUpdateRequestTransfer $recurringOrderQuoteUpdateRequestTransfer,
        QuoteTransfer $quoteTransfer,
    ): QuoteTransfer {
        $recurringOrderSettingsTransfer = $recurringOrderQuoteUpdateRequestTransfer->getRecurringOrderSettings();

        if ($recurringOrderSettingsTransfer !== null) {
            $recurringOrderSettingsTransfer->setFirstOrderDate($this->resolveFirstOrderDate($recurringOrderSettingsTransfer));
        }

        $quoteTransfer->setRecurringOrderSettings($recurringOrderSettingsTransfer);

        if ($quoteTransfer->getCustomer() === null) {
            $quoteTransfer->setCustomer($recurringOrderQuoteUpdateRequestTransfer->getCustomer());
        }

        return $quoteTransfer;
    }

    protected function resolveFirstOrderDate(RecurringOrderSettingsTransfer $recurringOrderSettingsTransfer): ?string
    {
        $cadenceType = $recurringOrderSettingsTransfer->getCadenceType();

        if ($cadenceType === null || !$this->cadenceResolver->isSupported($cadenceType)) {
            return null;
        }

        return $this->firstTriggerDateResolver
            ->resolve($recurringOrderSettingsTransfer->getStartDate(), $cadenceType, $recurringOrderSettingsTransfer->getCadenceValue())
            ->format(static::DATE_FORMAT);
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
