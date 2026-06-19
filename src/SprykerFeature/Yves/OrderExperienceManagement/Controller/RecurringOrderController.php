<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Controller;

use Generated\Shared\Transfer\RecurringOrderQuoteUpdateResponseTransfer;
use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use Spryker\Yves\Kernel\Controller\AbstractController;
use Spryker\Yves\Kernel\View\View;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class RecurringOrderController extends AbstractController
{
    protected const string FORM_TEMPLATE = '@OrderExperienceManagement/views/recurring-order-form/recurring-order-form.twig';

    protected const string CONFIRMED_TEMPLATE = '@OrderExperienceManagement/views/recurring-order-confirmed/recurring-order-confirmed.twig';

    protected const string VIEW_PARAM_FORM = 'form';

    protected const string VIEW_PARAM_RECURRING_ORDER_SETTINGS = 'recurringOrderSettings';

    protected const string VIEW_PARAM_CADENCE_TYPE_EVERY_N_WEEKS = 'cadenceTypeEveryNWeeks';

    protected const string CSRF_TOKEN_ID_CLEAR = 'recurring-order-clear';

    protected const string REQUEST_PARAM_CSRF_TOKEN = '_token';

    public function saveAction(Request $request): View
    {
        $quoteTransfer = $this->getFactory()->getQuoteClient()->getQuote();
        $form = $this->getFactory()->createRecurringOrderSelectorForm($quoteTransfer);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->createFormView($form);
        }

        /** @var \Generated\Shared\Transfer\RecurringOrderSettingsTransfer $recurringOrderSettingsTransfer */
        $recurringOrderSettingsTransfer = $form->getData();
        $responseTransfer = $this->updateQuote($quoteTransfer->getIdQuoteOrFail(), $recurringOrderSettingsTransfer);

        if (!$responseTransfer->getIsSuccessful()) {
            $this->addResponseErrorsToForm($form, $responseTransfer);

            return $this->createFormView($form);
        }

        return $this->createConfirmedView($responseTransfer->getQuoteOrFail()->getRecurringOrderSettingsOrFail());
    }

    public function clearAction(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid($request)) {
            return $this->jsonResponse([], Response::HTTP_FORBIDDEN);
        }

        $quoteTransfer = $this->getFactory()->getQuoteClient()->getQuote();
        $this->updateQuote($quoteTransfer->getIdQuoteOrFail(), null);

        return $this->jsonResponse([]);
    }

    protected function isCsrfTokenValid(Request $request): bool
    {
        $token = $request->request->get(static::REQUEST_PARAM_CSRF_TOKEN);

        if (!is_string($token)) {
            return false;
        }

        return $this->getFactory()
            ->getCsrfTokenManager()
            ->isTokenValid(new CsrfToken(static::CSRF_TOKEN_ID_CLEAR, $token));
    }

    protected function updateQuote(int $idQuote, ?RecurringOrderSettingsTransfer $recurringOrderSettingsTransfer): RecurringOrderQuoteUpdateResponseTransfer
    {
        return $this->getFactory()
            ->createRecurringOrderQuoteUpdater()
            ->updateRecurringOrderSettingsOnQuote($idQuote, $recurringOrderSettingsTransfer);
    }

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

    protected function addResponseErrorsToForm(FormInterface $form, RecurringOrderQuoteUpdateResponseTransfer $responseTransfer): void
    {
        foreach ($responseTransfer->getErrors() as $errorTransfer) {
            $form->addError(new FormError($errorTransfer->getMessageOrFail()));
        }
    }
}
