<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Controller;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Spryker\Yves\Kernel\View\View;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderApproveForm;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class RecurringOrderReviewController extends AbstractRecurringOrderController
{
    protected const string REQUEST_PARAM_UUID = 'uuid';

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Plugin\Router\RecurringOrderRouteProviderPlugin::ROUTE_NAME_RECURRING_ORDER_DETAIL
     *
     * @var string
     */
    protected const string ROUTE_NAME_RECURRING_ORDER_DETAIL = 'recurring-orders/detail';

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Plugin\Router\RecurringOrderRouteProviderPlugin::ROUTE_NAME_RECURRING_ORDER_LIST
     *
     * @var string
     */
    protected const string ROUTE_NAME_RECURRING_ORDER_LIST = 'recurring-orders';

    protected const string GLOSSARY_KEY_REVIEW_NOT_AVAILABLE = 'recurring_orders.review.not_available';

    protected const string GLOSSARY_KEY_INVALID_FORM = 'recurring_orders.review.invalid_form';

    protected const string GLOSSARY_KEY_APPROVE_ERROR = 'recurring_orders.review.approve_error';

    protected const string GLOSSARY_KEY_APPROVE_SUCCESS = 'recurring_orders.review.approve_success';

    protected const string GLOSSARY_KEY_CURRENCY_MISMATCH = 'recurring_orders.review.currency_mismatch';

    protected const string GLOSSARY_KEY_PRICE_MODE_MISMATCH = 'recurring_orders.review.price_mode_mismatch';

    /**
     * @uses \SprykerShop\Yves\AgentPage\Plugin\Router\AgentPageRouteProviderPlugin::ROUTE_NAME_LOGIN
     *
     * @var string
     */
    protected const string ROUTE_NAME_LOGIN = 'login';

    public function indexAction(Request $request): View|RedirectResponse
    {
        $customerTransfer = $this->resolveAuthenticatedCustomer();

        if ($customerTransfer === null) {
            return $this->redirectResponseInternal(static::ROUTE_NAME_LOGIN);
        }

        $uuid = $request->attributes->get(static::REQUEST_PARAM_UUID);

        if (!$request->isMethod(Request::METHOD_POST)) {
            return $this->renderReviewView($uuid, $customerTransfer);
        }

        $form = $this->getFactory()->createRecurringOrderApproveForm()->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->renderReviewView($uuid, $customerTransfer);
        }

        if (!$form->isValid()) {
            $this->addErrorMessage(static::GLOSSARY_KEY_INVALID_FORM);

            return $this->renderReviewView($uuid, $customerTransfer, $form);
        }

        /** @var array<string, mixed> $formData */
        $formData = $form->getData();

        $recurringScheduleEventRequestTransfer = $this->getFactory()
            ->createRecurringScheduleEventRequestMapper()
            ->mapApproveFormDataToRecurringScheduleEventRequest($formData, $customerTransfer, new RecurringScheduleEventRequestTransfer());

        $recurringScheduleEventResponseTransfer = $this->getFactory()
            ->getOrderExperienceManagementClient()
            ->approveScheduleReview($recurringScheduleEventRequestTransfer);

        if ($recurringScheduleEventResponseTransfer->getIsSuccessful()) {
            $this->addSuccessMessage(static::GLOSSARY_KEY_APPROVE_SUCCESS);

            return $this->redirectResponseInternal(
                static::ROUTE_NAME_RECURRING_ORDER_DETAIL,
                [RecurringOrderApproveForm::FIELD_UUID => $uuid],
            );
        }

        $this->addResponseErrorMessages(
            $recurringScheduleEventResponseTransfer->getErrors(),
            static::GLOSSARY_KEY_APPROVE_ERROR,
        );

        return $this->renderReviewView($uuid, $customerTransfer, $form);
    }

    protected function renderReviewView(
        string $uuid,
        CustomerTransfer $customerTransfer,
        ?FormInterface $approveForm = null,
    ): View|RedirectResponse {
        $recurringScheduleReviewResponseTransfer = $this->getFactory()
            ->createRecurringScheduleReader()
            ->findScheduleReview($uuid, $customerTransfer);

        $recurringScheduleReviewResponseTransfer = $this->getFactory()
            ->createRecurringScheduleSubstituteOptionExpander()
            ->expandWithSubstituteOptions($recurringScheduleReviewResponseTransfer);

        $recurringScheduleTransfer = $recurringScheduleReviewResponseTransfer->getRecurringSchedule();

        if ($recurringScheduleTransfer === null) {
            $this->addErrorMessage(static::GLOSSARY_KEY_REVIEW_NOT_AVAILABLE);

            return $this->redirectResponseInternal(static::ROUTE_NAME_RECURRING_ORDER_LIST);
        }

        if ($recurringScheduleTransfer->getStatus() !== SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED) {
            return $this->redirectResponseInternal(
                static::ROUTE_NAME_RECURRING_ORDER_DETAIL,
                [RecurringOrderApproveForm::FIELD_UUID => $uuid],
            );
        }

        $scheduleReviewContextMismatchResolver = $this->getFactory()->createScheduleReviewContextMismatchResolver();

        if ($scheduleReviewContextMismatchResolver->isCurrencyMismatch($recurringScheduleTransfer)) {
            $this->addErrorMessage(static::GLOSSARY_KEY_CURRENCY_MISMATCH);

            return $this->redirectResponseInternal(
                static::ROUTE_NAME_RECURRING_ORDER_DETAIL,
                [RecurringOrderApproveForm::FIELD_UUID => $uuid],
            );
        }

        if ($scheduleReviewContextMismatchResolver->isPriceModeMismatch($recurringScheduleTransfer)) {
            $this->addErrorMessage(static::GLOSSARY_KEY_PRICE_MODE_MISMATCH);

            return $this->redirectResponseInternal(
                static::ROUTE_NAME_RECURRING_ORDER_DETAIL,
                [RecurringOrderApproveForm::FIELD_UUID => $uuid],
            );
        }

        if ($approveForm === null) {
            $approveFormData = $this->getFactory()
                ->createRecurringOrderApproveFormDataProvider()
                ->getData($uuid, $recurringScheduleReviewResponseTransfer);

            $approveForm = $this->getFactory()->createRecurringOrderApproveForm(
                $approveFormData,
                [RecurringOrderApproveForm::OPTION_RECURRING_SCHEDULE_REVIEW => $recurringScheduleReviewResponseTransfer],
            );
        }

        return $this->view(
            [
                'review' => $recurringScheduleReviewResponseTransfer,
                'schedule' => $recurringScheduleTransfer,
                'reviewReasonLabelMap' => $this->getFactory()->getConfig()->getReviewReasonLabelMap(),
                'reviewReasonBadgeMap' => $this->getFactory()->getConfig()->getReviewReasonBadgeMap(),
                'priceChangeReviewReasons' => $this->getFactory()->getConfig()->getPriceChangeReviewReasons(),
                'itemFlagLabelMap' => $this->getFactory()->getConfig()->getItemFlagLabelMap(),
                'itemFlagBadgeMap' => $this->getFactory()->getConfig()->getItemFlagBadgeMap(),
                'approveForm' => $approveForm->createView(),
                'scopeChoices' => $this->getFactory()->getConfig()->getReviewScopeChoices(),
                'shipmentAddressChoices' => $this->getFactory()
                    ->createAddedItemAddressChoicesReader()
                    ->getAddressChoices($recurringScheduleReviewResponseTransfer),
            ],
            [],
            '@OrderExperienceManagement/views/schedule-review/schedule-review.twig',
        );
    }
}
