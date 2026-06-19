<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Controller;

use Spryker\Yves\Kernel\Controller\AbstractController;
use Spryker\Yves\Kernel\View\View;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderActionForm;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class RecurringOrderReviewController extends AbstractController
{
    protected const string REQUEST_PARAM_UUID = 'uuid';

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
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Plugin\Router\RecurringOrderRouteProviderPlugin::ROUTE_NAME_RECURRING_ORDER_LIST
     *
     * @var string
     */
    protected const string ROUTE_NAME_RECURRING_ORDER_LIST = 'recurring-orders';

    protected const string MESSAGE_REVIEW_NOT_AVAILABLE = 'recurring_orders.review.not_available';

    public function indexAction(Request $request): View|RedirectResponse
    {
        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();

        if ($customerTransfer === null || $customerTransfer->getIdCustomer() === null) {
            return $this->redirectResponseInternal(static::ROUTE_NAME_LOGIN);
        }

        $uuid = $request->attributes->get(static::REQUEST_PARAM_UUID);

        $recurringScheduleReviewResponseTransfer = $this->getFactory()
            ->createRecurringScheduleReader()
            ->findScheduleReview($uuid, $customerTransfer);

        $recurringScheduleTransfer = $recurringScheduleReviewResponseTransfer->getRecurringSchedule();

        if ($recurringScheduleTransfer === null) {
            $this->addErrorMessage(static::MESSAGE_REVIEW_NOT_AVAILABLE);

            return $this->redirectResponseInternal(static::ROUTE_NAME_RECURRING_ORDER_LIST);
        }

        if ($recurringScheduleTransfer->getStatus() !== SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED) {
            return $this->redirectResponseInternal(
                static::ROUTE_NAME_RECURRING_ORDER_DETAIL,
                [RecurringOrderActionForm::FIELD_UUID => $uuid],
            );
        }

        $approveFormData = $this->getFactory()
            ->createRecurringOrderApproveFormDataProvider()
            ->getData($uuid, $recurringScheduleReviewResponseTransfer);

        return $this->view(
            [
                'review' => $recurringScheduleReviewResponseTransfer,
                'schedule' => $recurringScheduleTransfer,
                'reviewReasonLabelMap' => $this->getFactory()->getConfig()->getReviewReasonLabelMap(),
                'reviewReasonBadgeMap' => $this->getFactory()->getConfig()->getReviewReasonBadgeMap(),
                'approveForm' => $this->getFactory()->createRecurringOrderApproveForm($approveFormData)->createView(),
            ],
            [],
            '@OrderExperienceManagement/views/schedule-review/schedule-review.twig',
        );
    }
}
