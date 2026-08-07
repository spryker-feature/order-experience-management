<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Controller;

use Generated\Shared\Transfer\RecurringScheduleCollectionRequestTransfer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 */
class RecurringScheduleEditController extends AbstractRecurringOrderActionController
{
    protected const string GLOSSARY_KEY_EDIT_SUCCESS = 'recurring_orders.detail.edit.success';

    protected const string GLOSSARY_KEY_EDIT_ERROR = 'recurring_orders.detail.edit.error';

    public function indexAction(Request $request): RedirectResponse
    {
        $customerTransfer = $this->resolveAuthenticatedCustomer();

        if ($customerTransfer === null) {
            return $this->redirectResponseInternal(static::ROUTE_NAME_LOGIN);
        }

        $form = $this->getFactory()->createRecurringScheduleEditForm()->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFormErrorMessages($form);

            return $this->createDetailRedirectResponse($form);
        }

        $recurringScheduleCollectionRequestTransfer = $this->getFactory()
            ->createRecurringScheduleEventRequestMapper()
            ->mapEditFormDataToRecurringScheduleCollectionRequest(
                $form->getData(),
                $customerTransfer,
                new RecurringScheduleCollectionRequestTransfer(),
            );

        $recurringScheduleCollectionResponseTransfer = $this->getFactory()
            ->getOrderExperienceManagementClient()
            ->updateRecurringScheduleCollection($recurringScheduleCollectionRequestTransfer);

        if ($recurringScheduleCollectionResponseTransfer->getErrors()->count() > 0) {
            $this->addResponseErrorMessages(
                $recurringScheduleCollectionResponseTransfer->getErrors(),
                static::GLOSSARY_KEY_EDIT_ERROR,
            );

            return $this->createDetailRedirectResponse($form);
        }

        $this->addSuccessMessage(static::GLOSSARY_KEY_EDIT_SUCCESS);

        return $this->createDetailRedirectResponse($form);
    }
}
