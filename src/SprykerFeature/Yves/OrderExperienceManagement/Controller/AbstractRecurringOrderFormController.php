<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Controller;

use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use Spryker\Yves\Kernel\Controller\AbstractController;
use Spryker\Yves\Kernel\View\View;
use Symfony\Component\Form\FormInterface;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
abstract class AbstractRecurringOrderFormController extends AbstractController
{
    protected const string FORM_TEMPLATE = '@OrderExperienceManagement/views/recurring-order-form/recurring-order-form.twig';

    protected const string CONFIRMED_TEMPLATE = '@OrderExperienceManagement/views/recurring-order-confirmed/recurring-order-confirmed.twig';

    protected const string VIEW_PARAM_FORM = 'form';

    protected const string VIEW_PARAM_RECURRING_ORDER_SETTINGS = 'recurringOrderSettings';

    protected const string VIEW_PARAM_CADENCE_TYPE_EVERY_N_WEEKS = 'cadenceTypeEveryNWeeks';

    protected function createFormView(FormInterface $form): View
    {
        return $this->view(
            [
                static::VIEW_PARAM_FORM => $form->createView(),
                static::VIEW_PARAM_CADENCE_TYPE_EVERY_N_WEEKS => $this->getFactory()->getConfig()->getCadenceTypeEveryNWeeks(),
            ],
            [],
            static::FORM_TEMPLATE,
        );
    }

    protected function createConfirmedView(RecurringOrderSettingsTransfer $recurringOrderSettingsTransfer): View
    {
        return $this->view(
            [
                static::VIEW_PARAM_RECURRING_ORDER_SETTINGS => $recurringOrderSettingsTransfer,
                static::VIEW_PARAM_CADENCE_TYPE_EVERY_N_WEEKS => $this->getFactory()->getConfig()->getCadenceTypeEveryNWeeks(),
            ],
            [],
            static::CONFIRMED_TEMPLATE,
        );
    }
}
