<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Controller;

use Generated\Shared\Transfer\PaginationTransfer;
use Spryker\Yves\Kernel\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class RecurringOrderListController extends AbstractRecurringOrderController
{
    protected const string REQUEST_PARAM_PAGE = 'page';

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

        $form = $this->getFactory()->createRecurringOrderSearchForm($customerTransfer);

        $recurringScheduleCriteriaTransfer = $this->getFactory()
            ->createRecurringOrderSearchFormHandler()
            ->buildRecurringScheduleCriteriaTransfer($request, $form, $customerTransfer);

        $recurringScheduleCriteriaTransfer->setPagination($this->buildPaginationTransfer($request));

        $attentionBannerReader = $this->getFactory()->createRecurringOrderAttentionBannerReader();

        $recurringScheduleCriteriaTransfer->setStatusCountConditions(
            $attentionBannerReader->buildStatusCountConditions($customerTransfer->getIdCustomerOrFail()),
        );

        $recurringScheduleCollectionTransfer = $this->getFactory()
            ->createRecurringScheduleReader()
            ->getScheduleCollection($recurringScheduleCriteriaTransfer);

        $attentionStatusCounts = $attentionBannerReader->getAttentionStatusCounts(
            $recurringScheduleCollectionTransfer->getStatusCounts(),
        );

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
