<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Controller;

use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Spryker\Yves\Kernel\Controller\AbstractController;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderAcceptedItemForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderApproveForm;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 */
class RecurringOrderApproveReviewController extends AbstractController
{
    protected const string MESSAGE_INVALID_FORM = 'recurring_orders.review.invalid_form';

    protected const string MESSAGE_APPROVE_ERROR = 'recurring_orders.review.approve_error';

    protected const string MESSAGE_APPROVE_SUCCESS = 'recurring_orders.review.approve_success';

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

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Plugin\Router\RecurringOrderRouteProviderPlugin::ROUTE_NAME_RECURRING_ORDER_REVIEW
     *
     * @var string
     */
    protected const string ROUTE_NAME_RECURRING_ORDER_REVIEW = 'recurring-orders/review-required';

    public function indexAction(Request $request): RedirectResponse
    {
        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();

        if ($customerTransfer === null || $customerTransfer->getIdCustomer() === null) {
            return $this->redirectResponseInternal(static::ROUTE_NAME_LOGIN);
        }

        $form = $this->getFactory()->createRecurringOrderApproveForm()->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addErrorMessage(static::MESSAGE_INVALID_FORM);

            return $this->createReviewRedirectResponse($form);
        }

        /** @var array<string, mixed> $formData */
        $formData = $form->getData();

        $recurringScheduleEventRequestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid((string)$formData[RecurringOrderApproveForm::FIELD_UUID])
            ->setIdCustomer($customerTransfer->getIdCustomerOrFail())
            ->setCustomer($customerTransfer);

        $this->addAcceptedItems($recurringScheduleEventRequestTransfer, $formData[RecurringOrderApproveForm::FIELD_ACCEPTED_ITEMS] ?? []);

        $recurringScheduleEventResponseTransfer = $this->getFactory()
            ->getOrderExperienceManagementClient()
            ->approveScheduleReview($recurringScheduleEventRequestTransfer);

        if ($recurringScheduleEventResponseTransfer->getIsSuccessful()) {
            $this->addSuccessMessage(static::MESSAGE_APPROVE_SUCCESS);

            return $this->createDetailRedirectResponse($form);
        }

        $this->addResponseErrorMessages($recurringScheduleEventResponseTransfer);

        return $this->createReviewRedirectResponse($form);
    }

    /**
     * @param array<int, array<string, mixed>> $acceptedItems
     */
    protected function addAcceptedItems(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
        array $acceptedItems,
    ): void {
        foreach ($acceptedItems as $acceptedItem) {
            $groupKey = $acceptedItem[RecurringOrderAcceptedItemForm::FIELD_GROUP_KEY] ?? null;
            $acceptedPrice = $acceptedItem[RecurringOrderAcceptedItemForm::FIELD_PRICE] ?? null;

            if ($groupKey === null || $acceptedPrice === null) {
                continue;
            }

            $recurringScheduleItemReviewTransfer = (new RecurringScheduleItemReviewTransfer())
                ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey($groupKey))
                ->setCurrentPrice((int)$acceptedPrice);

            $recurringScheduleEventRequestTransfer->addAcceptedItem($recurringScheduleItemReviewTransfer);
        }
    }

    protected function addResponseErrorMessages(RecurringScheduleEventResponseTransfer $recurringScheduleEventResponseTransfer): void
    {
        if ($recurringScheduleEventResponseTransfer->getErrors()->count() === 0) {
            $this->addErrorMessage(static::MESSAGE_APPROVE_ERROR);

            return;
        }

        foreach ($recurringScheduleEventResponseTransfer->getErrors() as $errorTransfer) {
            $this->addErrorMessage($errorTransfer->getMessageOrFail());
        }
    }

    protected function createDetailRedirectResponse(FormInterface $form): RedirectResponse
    {
        return $this->redirectResponseInternal(
            static::ROUTE_NAME_RECURRING_ORDER_DETAIL,
            [RecurringOrderApproveForm::FIELD_UUID => $form->get(RecurringOrderApproveForm::FIELD_UUID)->getData()],
        );
    }

    protected function createReviewRedirectResponse(FormInterface $form): RedirectResponse
    {
        return $this->redirectResponseInternal(
            static::ROUTE_NAME_RECURRING_ORDER_REVIEW,
            [RecurringOrderApproveForm::FIELD_UUID => $form->get(RecurringOrderApproveForm::FIELD_UUID)->getData()],
        );
    }
}
