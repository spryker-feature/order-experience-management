<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Updater;

use Generated\Shared\Transfer\RecurringOrderQuoteUpdateRequestTransfer;
use Generated\Shared\Transfer\RecurringOrderQuoteUpdateResponseTransfer;
use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use Spryker\Client\Customer\CustomerClientInterface;
use SprykerFeature\Client\OrderExperienceManagement\OrderExperienceManagementClientInterface;

class RecurringOrderQuoteUpdater implements RecurringOrderQuoteUpdaterInterface
{
    public function __construct(
        protected OrderExperienceManagementClientInterface $subscriptionClient,
        protected CustomerClientInterface $customerClient,
        protected QuoteSessionUpdaterInterface $quoteSessionUpdater,
    ) {
    }

    public function updateRecurringOrderSettingsOnQuote(
        int $idQuote,
        ?RecurringOrderSettingsTransfer $recurringOrderSettingsTransfer,
    ): RecurringOrderQuoteUpdateResponseTransfer {
        $requestTransfer = (new RecurringOrderQuoteUpdateRequestTransfer())
            ->setIdQuote($idQuote)
            ->setRecurringOrderSettings($recurringOrderSettingsTransfer)
            ->setCustomer($this->customerClient->getCustomer());

        $responseTransfer = $this->subscriptionClient->updateRecurringOrderSettingsOnQuote($requestTransfer);

        if ($responseTransfer->getIsSuccessful()) {
            $this->quoteSessionUpdater->updateFromResponse($responseTransfer);
        }

        return $responseTransfer;
    }
}
