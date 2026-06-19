<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Controller;

use Spryker\Yves\Kernel\Controller\AbstractController;
use Spryker\Yves\Kernel\View\View;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class RecurringOrderFormController extends AbstractController
{
    protected const string QUERY_PARAM_EDIT = 'edit';

    protected const string FORM_TEMPLATE = '@OrderExperienceManagement/views/recurring-order-form/recurring-order-form.twig';

    protected const string CONFIRMED_TEMPLATE = '@OrderExperienceManagement/views/recurring-order-confirmed/recurring-order-confirmed.twig';

    protected const string VIEW_PARAM_FORM = 'form';

    protected const string VIEW_PARAM_RECURRING_ORDER_SETTINGS = 'recurringOrderSettings';

    protected const string VIEW_PARAM_CADENCE_TYPE_EVERY_N_WEEKS = 'cadenceTypeEveryNWeeks';

    public function indexAction(Request $request): View
    {
        $quoteTransfer = $this->getFactory()->getQuoteClient()->getQuote();
        $cadenceTypeEveryNWeeks = $this->getFactory()->getConfig()->getCadenceTypeEveryNWeeks();

        if (!$request->query->has(static::QUERY_PARAM_EDIT) && $quoteTransfer->getRecurringOrderSettings() !== null) {
            return $this->view(
                [
                    static::VIEW_PARAM_RECURRING_ORDER_SETTINGS => $quoteTransfer->getRecurringOrderSettings(),
                    static::VIEW_PARAM_CADENCE_TYPE_EVERY_N_WEEKS => $cadenceTypeEveryNWeeks,
                ],
                [],
                static::CONFIRMED_TEMPLATE,
            );
        }

        $form = $this->getFactory()->createRecurringOrderSelectorForm($quoteTransfer);

        return $this->view(
            [
                static::VIEW_PARAM_FORM => $form->createView(),
                static::VIEW_PARAM_CADENCE_TYPE_EVERY_N_WEEKS => $cadenceTypeEveryNWeeks,
            ],
            [],
            static::FORM_TEMPLATE,
        );
    }
}
