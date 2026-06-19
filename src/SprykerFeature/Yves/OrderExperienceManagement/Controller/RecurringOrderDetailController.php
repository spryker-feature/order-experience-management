<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Controller;

use Generated\Shared\Transfer\PaginationTransfer;
use Spryker\Yves\Kernel\Controller\AbstractController;
use Spryker\Yves\Kernel\View\View;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class RecurringOrderDetailController extends AbstractController
{
    protected const string REQUEST_PARAM_UUID = 'uuid';

    protected const string REQUEST_PARAM_PAGE = 'page';

    protected const int DEFAULT_PAGE = 1;

    /**
     * @uses \SprykerShop\Yves\AgentPage\Plugin\Router\AgentPageRouteProviderPlugin::ROUTE_NAME_LOGIN
     *
     * @var string
     */
    protected const string ROUTE_NAME_LOGIN = 'login';

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Plugin\Router\RecurringOrderRouteProviderPlugin::ROUTE_NAME_RECURRING_ORDER_LIST
     *
     * @var string
     */
    protected const string ROUTE_NAME_RECURRING_ORDER_LIST = 'recurring-orders';

    protected const string MESSAGE_ACCESS_DENIED = 'recurring_orders.detail.access_denied';

    public function indexAction(Request $request): View|RedirectResponse
    {
        $customerTransfer = $this->getFactory()->getCustomerClient()->getCustomer();

        if ($customerTransfer === null || $customerTransfer->getIdCustomer() === null) {
            return $this->redirectResponseInternal(static::ROUTE_NAME_LOGIN);
        }

        $uuid = $request->attributes->get(static::REQUEST_PARAM_UUID);

        $recurringScheduleTransfer = $this->getFactory()
            ->createRecurringScheduleReader()
            ->findScheduleDetail(
                $uuid,
                $customerTransfer,
                $this->buildHistoryPaginationTransfer($request),
            );

        if ($recurringScheduleTransfer === null) {
            $this->addErrorMessage(static::MESSAGE_ACCESS_DENIED);

            return $this->redirectResponseInternal(static::ROUTE_NAME_RECURRING_ORDER_LIST);
        }

        $config = $this->getFactory()->getConfig();

        $quoteTransfer = $this->getFactory()
            ->createRecurringScheduleQuoteDataDeserializer()
            ->deserialize($recurringScheduleTransfer->getQuoteData());

        return $this->view(
            [
                'schedule' => $recurringScheduleTransfer,
                'quote' => $quoteTransfer,
                'statusClassMap' => $config->getStatusBadgeClassMap(),
                'statusIconMap' => $config->getStatusIconMap(),
                'errorBannerStatuses' => $config->getErrorBannerStatuses(),
                'historyEventTypeBadgeClassMap' => $config->getHistoryEventTypeBadgeClassMap(),
                'statusActive' => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
                'statusPaused' => SharedOrderExperienceManagementConfig::STATUS_PAUSED,
                'statusCancelled' => SharedOrderExperienceManagementConfig::STATUS_CANCELLED,
                'statusReviewRequired' => SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED,
                'statusFailed' => SharedOrderExperienceManagementConfig::STATUS_FAILED,
                'historyEventTypeFailed' => SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_FAILED,
                'pauseForm' => $this->getFactory()->createRecurringOrderActionForm($uuid)->createView(),
                'confirmForm' => $this->getFactory()->createRecurringOrderActionForm($uuid)->createView(),
                'skipForm' => $this->getFactory()->createRecurringOrderActionForm($uuid)->createView(),
                'cancelForm' => $this->getFactory()->createRecurringOrderActionForm($uuid)->createView(),
                'resumeForm' => $this->getFactory()->createRecurringOrderResumeForm($uuid)->createView(),
                'retryForm' => $this->getFactory()->createRecurringOrderActionForm($uuid)->createView(),
            ],
            [],
            '@OrderExperienceManagement/views/schedule-detail/schedule-detail.twig',
        );
    }

    protected function buildHistoryPaginationTransfer(Request $request): PaginationTransfer
    {
        return (new PaginationTransfer())
            ->setPage((int)$request->query->get(static::REQUEST_PARAM_PAGE, static::DEFAULT_PAGE))
            ->setMaxPerPage($this->getFactory()->getConfig()->getRecurringScheduleHistoryItemsPerPage());
    }
}
