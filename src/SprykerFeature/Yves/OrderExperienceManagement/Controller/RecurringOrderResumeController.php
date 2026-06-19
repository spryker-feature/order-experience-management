<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Controller;

use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderResumeForm;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 */
class RecurringOrderResumeController extends AbstractRecurringOrderActionController
{
    public function indexAction(Request $request): RedirectResponse
    {
        $customerTransfer = $this->resolveAuthenticatedCustomer();

        if ($customerTransfer === null) {
            return $this->redirectResponseInternal(static::ROUTE_NAME_LOGIN);
        }

        $form = $this->getFactory()->createRecurringOrderResumeForm()->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFormErrorMessages($form);

            return $this->createDetailRedirectResponse($form);
        }

        /** @var \DateTime $nextExecutionDate */
        $nextExecutionDate = $form->get(RecurringOrderResumeForm::FIELD_NEXT_EXECUTION_DATE)->getData();

        $recurringScheduleEventResponseTransfer = $this->getFactory()
            ->createRecurringOrderScheduleResumeUpdater()
            ->resumeWithDate(
                $request->attributes->get(static::REQUEST_PARAM_UUID),
                $customerTransfer,
                $nextExecutionDate,
            );

        if (!$recurringScheduleEventResponseTransfer->getIsSuccessful()) {
            $this->addErrorMessage(static::MESSAGE_ACTION_ERROR);
        }

        return $this->createDetailRedirectResponse($form);
    }

    protected function addFormErrorMessages(FormInterface $form): void
    {
        foreach ($form->getErrors(true) as $formError) {
            if (!$formError instanceof FormError) {
                continue;
            }

            $this->addErrorMessage($formError->getMessage());
        }
    }
}
