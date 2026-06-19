<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Plugin\Router;

use Spryker\Yves\Router\Plugin\RouteProvider\AbstractRouteProviderPlugin;
use Spryker\Yves\Router\Route\RouteCollection;

class RecurringOrderRouteProviderPlugin extends AbstractRouteProviderPlugin
{
    public const string ROUTE_NAME_RECURRING_ORDER_SAVE = 'recurring-order/save';

    public const string ROUTE_NAME_RECURRING_ORDER_CLEAR = 'recurring-order/clear';

    public const string ROUTE_NAME_RECURRING_ORDER_PAUSE = 'recurring-order/pause';

    public const string ROUTE_NAME_RECURRING_ORDER_RESUME = 'recurring-order/resume';

    public const string ROUTE_NAME_RECURRING_ORDER_SKIP = 'recurring-order/skip';

    public const string ROUTE_NAME_RECURRING_ORDER_CANCEL = 'recurring-order/cancel';

    public const string ROUTE_NAME_RECURRING_ORDER_CONFIRM = 'recurring-order/confirm';

    public const string ROUTE_NAME_RECURRING_ORDER_RETRY = 'recurring-order/retry';

    public const string ROUTE_NAME_RECURRING_ORDER_LIST = 'recurring-orders';

    public const string ROUTE_NAME_RECURRING_ORDER_DETAIL = 'recurring-orders/detail';

    public const string ROUTE_NAME_RECURRING_ORDER_FORM = 'recurring-order/form';

    public const string ROUTE_NAME_RECURRING_ORDER_REVIEW = 'recurring-orders/review-required';

    public const string ROUTE_NAME_RECURRING_ORDER_APPROVE_REVIEW = 'recurring-order/approve-review';

    protected const string PATTERN_RECURRING_ORDER_SAVE = '/recurring-order/save';

    protected const string PATTERN_RECURRING_ORDER_CLEAR = '/recurring-order/clear';

    protected const string PATTERN_RECURRING_ORDER_PAUSE = '/recurring-order/{uuid}/pause';

    protected const string PATTERN_RECURRING_ORDER_RESUME = '/recurring-order/{uuid}/resume';

    protected const string PATTERN_RECURRING_ORDER_SKIP = '/recurring-order/{uuid}/skip';

    protected const string PATTERN_RECURRING_ORDER_CANCEL = '/recurring-order/{uuid}/cancel';

    protected const string PATTERN_RECURRING_ORDER_CONFIRM = '/recurring-order/{uuid}/confirm';

    protected const string PATTERN_RECURRING_ORDER_RETRY = '/recurring-order/{uuid}/retry';

    protected const string PATTERN_RECURRING_ORDER_LIST = '/recurring-orders';

    protected const string PATTERN_RECURRING_ORDER_DETAIL = '/recurring-orders/{uuid}';

    protected const string PATTERN_RECURRING_ORDER_FORM = '/recurring-order-form';

    protected const string PATTERN_RECURRING_ORDER_REVIEW = '/recurring-orders/{uuid}/review-required';

    protected const string PATTERN_RECURRING_ORDER_APPROVE_REVIEW = '/recurring-order/{uuid}/approve-review';

    public function addRoutes(RouteCollection $routeCollection): RouteCollection
    {
        $routeCollection = $this->addSaveRoute($routeCollection);
        $routeCollection = $this->addClearRoute($routeCollection);
        $routeCollection = $this->addPauseRoute($routeCollection);
        $routeCollection = $this->addResumeRoute($routeCollection);
        $routeCollection = $this->addSkipRoute($routeCollection);
        $routeCollection = $this->addCancelRoute($routeCollection);
        $routeCollection = $this->addConfirmRoute($routeCollection);
        $routeCollection = $this->addRetryRoute($routeCollection);
        $routeCollection = $this->addListRoute($routeCollection);
        $routeCollection = $this->addDetailRoute($routeCollection);
        $routeCollection = $this->addFormRoute($routeCollection);
        $routeCollection = $this->addReviewRoute($routeCollection);
        $routeCollection = $this->addApproveReviewRoute($routeCollection);

        return $routeCollection;
    }

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Controller\RecurringOrderReviewController::indexAction()
     */
    protected function addReviewRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildRoute(
            static::PATTERN_RECURRING_ORDER_REVIEW,
            'OrderExperienceManagement',
            'RecurringOrderReview',
            'index',
        );

        $routeCollection->add(static::ROUTE_NAME_RECURRING_ORDER_REVIEW, $route);

        return $routeCollection;
    }

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Controller\RecurringOrderApproveReviewController::indexAction()
     */
    protected function addApproveReviewRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildPostRoute(
            static::PATTERN_RECURRING_ORDER_APPROVE_REVIEW,
            'OrderExperienceManagement',
            'RecurringOrderApproveReview',
            'index',
        );

        $routeCollection->add(static::ROUTE_NAME_RECURRING_ORDER_APPROVE_REVIEW, $route);

        return $routeCollection;
    }

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Controller\RecurringOrderController::saveAction()
     */
    protected function addSaveRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildPostRoute(
            static::PATTERN_RECURRING_ORDER_SAVE,
            'OrderExperienceManagement',
            'RecurringOrder',
            'save',
        );

        $routeCollection->add(static::ROUTE_NAME_RECURRING_ORDER_SAVE, $route);

        return $routeCollection;
    }

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Controller\RecurringOrderController::clearAction()
     */
    protected function addClearRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildPostRoute(
            static::PATTERN_RECURRING_ORDER_CLEAR,
            'OrderExperienceManagement',
            'RecurringOrder',
            'clear',
        );

        $routeCollection->add(static::ROUTE_NAME_RECURRING_ORDER_CLEAR, $route);

        return $routeCollection;
    }

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Controller\RecurringOrderPauseController::indexAction()
     */
    protected function addPauseRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildPostRoute(
            static::PATTERN_RECURRING_ORDER_PAUSE,
            'OrderExperienceManagement',
            'RecurringOrderPause',
            'index',
        );

        $routeCollection->add(static::ROUTE_NAME_RECURRING_ORDER_PAUSE, $route);

        return $routeCollection;
    }

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Controller\RecurringOrderResumeController::indexAction()
     */
    protected function addResumeRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildPostRoute(
            static::PATTERN_RECURRING_ORDER_RESUME,
            'OrderExperienceManagement',
            'RecurringOrderResume',
            'index',
        );

        $routeCollection->add(static::ROUTE_NAME_RECURRING_ORDER_RESUME, $route);

        return $routeCollection;
    }

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Controller\RecurringOrderSkipController::indexAction()
     */
    protected function addSkipRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildPostRoute(
            static::PATTERN_RECURRING_ORDER_SKIP,
            'OrderExperienceManagement',
            'RecurringOrderSkip',
            'index',
        );

        $routeCollection->add(static::ROUTE_NAME_RECURRING_ORDER_SKIP, $route);

        return $routeCollection;
    }

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Controller\RecurringOrderCancelController::indexAction()
     */
    protected function addCancelRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildPostRoute(
            static::PATTERN_RECURRING_ORDER_CANCEL,
            'OrderExperienceManagement',
            'RecurringOrderCancel',
            'index',
        );

        $routeCollection->add(static::ROUTE_NAME_RECURRING_ORDER_CANCEL, $route);

        return $routeCollection;
    }

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Controller\RecurringOrderConfirmController::indexAction()
     */
    protected function addConfirmRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildPostRoute(
            static::PATTERN_RECURRING_ORDER_CONFIRM,
            'OrderExperienceManagement',
            'RecurringOrderConfirm',
            'index',
        );

        $routeCollection->add(static::ROUTE_NAME_RECURRING_ORDER_CONFIRM, $route);

        return $routeCollection;
    }

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Controller\RecurringOrderRetryController::indexAction()
     */
    protected function addRetryRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildPostRoute(
            static::PATTERN_RECURRING_ORDER_RETRY,
            'OrderExperienceManagement',
            'RecurringOrderRetry',
            'index',
        );

        $routeCollection->add(static::ROUTE_NAME_RECURRING_ORDER_RETRY, $route);

        return $routeCollection;
    }

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Controller\RecurringOrderListController::indexAction()
     */
    protected function addListRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildRoute(
            static::PATTERN_RECURRING_ORDER_LIST,
            'OrderExperienceManagement',
            'RecurringOrderList',
            'index',
        );

        $routeCollection->add(static::ROUTE_NAME_RECURRING_ORDER_LIST, $route);

        return $routeCollection;
    }

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Controller\RecurringOrderFormController::indexAction()
     */
    protected function addFormRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildRoute(
            static::PATTERN_RECURRING_ORDER_FORM,
            'OrderExperienceManagement',
            'RecurringOrderForm',
            'index',
        );

        $routeCollection->add(static::ROUTE_NAME_RECURRING_ORDER_FORM, $route);

        return $routeCollection;
    }

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Controller\RecurringOrderDetailController::indexAction()
     */
    protected function addDetailRoute(RouteCollection $routeCollection): RouteCollection
    {
        $route = $this->buildRoute(
            static::PATTERN_RECURRING_ORDER_DETAIL,
            'OrderExperienceManagement',
            'RecurringOrderDetail',
            'index',
        );

        $routeCollection->add(static::ROUTE_NAME_RECURRING_ORDER_DETAIL, $route);

        return $routeCollection;
    }
}
