<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Controller;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Spryker\Yves\Kernel\Controller\AbstractController;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderActionForm;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 */
abstract class AbstractRecurringOrderActionController extends AbstractController
{
    protected const string REQUEST_PARAM_UUID = 'uuid';

    protected const string MESSAGE_INVALID_CSRF_TOKEN = 'form.csrf.error.text';

    protected const string MESSAGE_ACTION_ERROR = 'recurring_orders.detail.action.error';

    /**
     * @uses \SprykerShop\Yves\AgentPage\Plugin\Router\AgentPageRouteProviderPlugin::ROUTE_NAME_LOGIN
     *
     * @var string
     */
    protected const string ROUTE_NAME_LOGIN = 'login';

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Plugin\Router\RecurringOrderRouteProviderPlugin::ROUTE_NAME_RECURRING_ORDER_DETAIL
     *
     * @var string
     */
    protected const string ROUTE_NAME_RECURRING_ORDER_DETAIL = 'recurring-orders/detail';

    protected function resolveAuthenticatedCustomer(): ?CustomerTransfer
    {
        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();

        if ($customerTransfer === null || $customerTransfer->getIdCustomer() === null) {
            return null;
        }

        return $customerTransfer;
    }

    protected function triggerScheduleEvent(Request $request, string $event): RedirectResponse
    {
        $customerTransfer = $this->resolveAuthenticatedCustomer();

        if ($customerTransfer === null) {
            return $this->redirectResponseInternal(static::ROUTE_NAME_LOGIN);
        }

        $form = $this->getFactory()->createRecurringOrderActionForm()->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addErrorMessage(static::MESSAGE_INVALID_CSRF_TOKEN);

            return $this->createDetailRedirectResponse($form);
        }

        $responseTransfer = $this->getFactory()->getOrderExperienceManagementClient()->triggerManualEventForSchedule(
            (new RecurringScheduleEventRequestTransfer())
                ->setUuid($request->attributes->get(static::REQUEST_PARAM_UUID))
                ->setEvent($event)
                ->setIdCustomer($customerTransfer->getIdCustomerOrFail())
                ->setCustomer($customerTransfer),
        );

        if (!$responseTransfer->getIsSuccessful()) {
            $this->addErrorMessage(static::MESSAGE_ACTION_ERROR);
        }

        return $this->createDetailRedirectResponse($form);
    }

    protected function createDetailRedirectResponse(FormInterface $form): RedirectResponse
    {
        return $this->redirectResponseInternal(
            static::ROUTE_NAME_RECURRING_ORDER_DETAIL,
            [RecurringOrderActionForm::FIELD_UUID => $form->get(RecurringOrderActionForm::FIELD_UUID)->getData()],
        );
    }
}
