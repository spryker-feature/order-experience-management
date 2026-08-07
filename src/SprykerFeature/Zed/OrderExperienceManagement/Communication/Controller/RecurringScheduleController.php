<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller;

use DateTimeImmutable;
use Spryker\Zed\Kernel\Communication\BusinessFactoryResolverAwareTrait;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Communication\OrderExperienceManagementCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface getRepository()
 */
class RecurringScheduleController extends AbstractController
{
    use BusinessFactoryResolverAwareTrait;

    protected const string PARAM_ID_RECURRING_SCHEDULE = 'id-recurring-schedule';

    /**
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller\RecurringScheduleController::indexAction()
     */
    protected const string REDIRECT_URL_INDEX = '/order-experience-management/recurring-schedule';

    protected const string MESSAGE_SCHEDULE_NOT_FOUND = 'Recurring order schedule was not found.';

    protected const string FORMAT_FORECAST_MONTH_LABEL = 'F Y';

    /**
     * @return array<string, mixed>
     */
    public function indexAction(Request $request): array
    {
        [$recurringScheduleTable, $recurringScheduleTableFilterForm] = $this->prepareTableWithFilterForm($request);

        $recurringScheduleForecastCollectionTransfer = $this->getBusinessFactory()
            ->createRecurringScheduleForecastReader()
            ->getMonthlyForecastCollection();

        $forecastMonthLabel = $recurringScheduleForecastCollectionTransfer->getLabel()
            ?? (new DateTimeImmutable())->format(static::FORMAT_FORECAST_MONTH_LABEL);

        return [
            'recurringScheduleTable' => $recurringScheduleTable->render(),
            'recurringScheduleTableFilterForm' => $recurringScheduleTableFilterForm->createView(),
            'forecastCollection' => $recurringScheduleForecastCollectionTransfer,
            'forecastMonthLabel' => $forecastMonthLabel,
        ];
    }

    public function tableAction(Request $request): JsonResponse
    {
        [$recurringScheduleTable] = $this->prepareTableWithFilterForm($request);

        return $this->jsonResponse($recurringScheduleTable->fetchData());
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|array<string, mixed>
     */
    public function viewAction(Request $request): array|RedirectResponse
    {
        $idRecurringSchedule = $this->castId($request->query->get(static::PARAM_ID_RECURRING_SCHEDULE));

        $recurringScheduleTransfer = $this->getFactory()
            ->createRecurringScheduleReader()
            ->findRecurringSchedule($idRecurringSchedule);

        if ($recurringScheduleTransfer === null) {
            $this->addErrorMessage(static::MESSAGE_SCHEDULE_NOT_FOUND);

            return $this->redirectResponse(static::REDIRECT_URL_INDEX);
        }

        $sourceOrderReference = null;
        $idSourceSalesOrder = $recurringScheduleTransfer->getIdSourceSalesOrder();

        if ($idSourceSalesOrder !== null) {
            $orderTransfer = $this->getFactory()->getSalesFacade()->findOrderByIdSalesOrder($idSourceSalesOrder);
            $sourceOrderReference = $orderTransfer?->getOrderReference();
        }

        $merchantNamesByReference = $this->getFactory()
            ->createMerchantNameResolver()
            ->getMerchantNamesByReference($recurringScheduleTransfer);

        $configurableBundleNamesByGlossaryKey = $this->getFactory()
            ->createConfigurableBundleNameResolver()
            ->getTranslatedNamesByGlossaryKey($recurringScheduleTransfer);

        return [
            'recurringSchedule' => $recurringScheduleTransfer,
            'idSourceSalesOrder' => $idSourceSalesOrder,
            'sourceOrderReference' => $sourceOrderReference,
            'merchantNamesByReference' => $merchantNamesByReference,
            'configurableBundleNamesByGlossaryKey' => $configurableBundleNamesByGlossaryKey,
        ];
    }

    /**
     * @return array{0: \SprykerFeature\Zed\OrderExperienceManagement\Communication\Table\RecurringScheduleTable, 1: \Symfony\Component\Form\FormInterface}
     */
    protected function prepareTableWithFilterForm(Request $request): array
    {
        $recurringScheduleTableFilterForm = $this->getFactory()
            ->createRecurringScheduleTableFilterForm($request)
            ->handleRequest($request);

        $recurringScheduleTable = $this->getFactory()->createRecurringScheduleTable();
        $recurringScheduleTable->applyCriteria($recurringScheduleTableFilterForm->getData());

        return [$recurringScheduleTable, $recurringScheduleTableFilterForm];
    }
}
