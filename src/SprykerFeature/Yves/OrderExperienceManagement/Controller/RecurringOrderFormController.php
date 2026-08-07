<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Controller;

use Spryker\Yves\Kernel\View\View;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class RecurringOrderFormController extends AbstractRecurringOrderFormController
{
    protected const string QUERY_PARAM_EDIT = 'edit';

    public function indexAction(Request $request): View
    {
        $quoteTransfer = $this->getFactory()->getQuoteClient()->getQuote();
        $recurringOrderSettingsTransfer = $quoteTransfer->getRecurringOrderSettings();

        if (!$request->query->has(static::QUERY_PARAM_EDIT) && $recurringOrderSettingsTransfer !== null && $recurringOrderSettingsTransfer->getCadenceType() !== null) {
            return $this->createConfirmedView($recurringOrderSettingsTransfer);
        }

        $form = $this->getFactory()->createRecurringOrderSelectorForm($quoteTransfer);

        return $this->createFormView($form);
    }
}
