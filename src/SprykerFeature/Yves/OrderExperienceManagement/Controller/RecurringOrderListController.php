<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Controller;

use Generated\Shared\Transfer\PaginationTransfer;
use Spryker\Yves\Kernel\Controller\AbstractController;
use Spryker\Yves\Kernel\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class RecurringOrderListController extends AbstractController
{
    protected const string REQUEST_PARAM_PAGE = 'page';

    /**
     * @uses \SprykerShop\Yves\AgentPage\Plugin\Router\AgentPageRouteProviderPlugin::ROUTE_NAME_LOGIN
     *
     * @var string
     */
    protected const ROUTE_NAME_LOGIN = 'login';

    public function indexAction(Request $request): View|RedirectResponse
    {
        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();

        if ($customerTransfer === null || $customerTransfer->getIdCustomer() === null) {
            return $this->redirectResponseInternal(static::ROUTE_NAME_LOGIN);
        }

        $form = $this->getFactory()->createRecurringOrderSearchForm($customerTransfer);

        $recurringScheduleCriteriaTransfer = $this->getFactory()
            ->createRecurringOrderSearchFormHandler()
            ->buildRecurringScheduleCriteriaTransfer($request, $form, $customerTransfer);

        $recurringScheduleCriteriaTransfer->setPagination($this->buildPaginationTransfer($request));

        $recurringScheduleCollectionTransfer = $this->getFactory()
            ->createRecurringScheduleReader()
            ->getScheduleCollection($recurringScheduleCriteriaTransfer);

        $attentionStatusCounts = $this->getFactory()
            ->createRecurringOrderAttentionBannerReader()
            ->getAttentionStatusCounts($customerTransfer->getIdCustomerOrFail());

        return $this->view(
            [
                'recurringSchedules' => $recurringScheduleCollectionTransfer->getRecurringSchedules(),
                'pagination' => $recurringScheduleCollectionTransfer->getPagination(),
                'searchForm' => $form->createView(),
                'attentionCount' => array_sum($attentionStatusCounts),
                'attentionStatusCounts' => $attentionStatusCounts,
                'statusClassMap' => $this->getFactory()->getConfig()->getStatusBadgeClassMap(),
            ],
            [],
            '@OrderExperienceManagement/views/recurring-order-list/recurring-order-list.twig',
        );
    }

    protected function buildPaginationTransfer(Request $request): PaginationTransfer
    {
        return (new PaginationTransfer())
            ->setMaxPerPage($this->getFactory()->getConfig()->getRecurringScheduleListItemsPerPage())
            ->setPage((int)$request->query->get(static::REQUEST_PARAM_PAGE, 1));
    }
}
