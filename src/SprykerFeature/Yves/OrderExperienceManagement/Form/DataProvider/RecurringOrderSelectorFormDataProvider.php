<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Form\DataProvider;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderSelectorForm;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;

class RecurringOrderSelectorFormDataProvider
{
    public function __construct(protected readonly OrderExperienceManagementConfig $subscriptionConfig)
    {
    }

    public function getData(QuoteTransfer $quoteTransfer): RecurringOrderSettingsTransfer
    {
        $existingRecurringOrderSettings = $quoteTransfer->getRecurringOrderSettings();

        if ($existingRecurringOrderSettings === null) {
            return new RecurringOrderSettingsTransfer();
        }

        return (new RecurringOrderSettingsTransfer())->fromArray($existingRecurringOrderSettings->toArray());
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return [
            RecurringOrderSelectorForm::OPTION_CADENCE_TYPE_CHOICES => $this->subscriptionConfig->getSupportedCadenceTypes(),
        ];
    }
}
