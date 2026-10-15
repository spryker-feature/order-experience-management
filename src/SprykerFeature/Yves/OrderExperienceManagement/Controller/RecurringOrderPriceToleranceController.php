<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Controller;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\DecimalObject\Decimal;
use Spryker\Yves\Kernel\View\View;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderPriceToleranceForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderPriceToleranceItemForm;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 */
class RecurringOrderPriceToleranceController extends AbstractRecurringOrderController
{
    protected const string REQUEST_PARAM_UUID = 'uuid';

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

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Plugin\Router\RecurringOrderRouteProviderPlugin::ROUTE_NAME_RECURRING_ORDER_DETAIL
     *
     * @var string
     */
    protected const string ROUTE_NAME_RECURRING_ORDER_DETAIL = 'recurring-orders/detail';

    /**
     * @uses \SprykerFeature\Yves\OrderExperienceManagement\Plugin\Router\RecurringOrderRouteProviderPlugin::ROUTE_NAME_RECURRING_ORDER_PRICE_TOLERANCES
     *
     * @var string
     */
    protected const string ROUTE_NAME_RECURRING_ORDER_PRICE_TOLERANCES = 'recurring-orders/price-tolerances';

    protected const string GLOSSARY_KEY_ACCESS_DENIED = 'recurring_orders.detail.access_denied';

    protected const string GLOSSARY_KEY_SAVE_SUCCESS = 'recurring_orders.price_tolerance.success';

    protected const string GLOSSARY_KEY_SAVE_ERROR = 'recurring_orders.price_tolerance.error.save';

    /**
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer\ScheduleUpdater::GLOSSARY_KEY_PRICE_TOLERANCE_LOCKED_IN_REVIEW
     */
    protected const string GLOSSARY_KEY_LOCKED_IN_REVIEW = 'recurring_orders.price_tolerance.locked_notice';

    public function indexAction(Request $request): View|RedirectResponse
    {
        $customerTransfer = $this->resolveAuthenticatedCustomer();

        if ($customerTransfer === null) {
            return $this->redirectResponseInternal(static::ROUTE_NAME_LOGIN);
        }

        $uuid = (string)$request->attributes->get(static::REQUEST_PARAM_UUID);
        $recurringScheduleTransfer = $this->getFactory()
            ->createRecurringScheduleReader()
            ->findScheduleWithItems($uuid, $customerTransfer);

        if ($recurringScheduleTransfer === null) {
            $this->addErrorMessage(static::GLOSSARY_KEY_ACCESS_DENIED);

            return $this->redirectResponseInternal(static::ROUTE_NAME_RECURRING_ORDER_LIST);
        }

        $isLocked = $recurringScheduleTransfer->getStatus() === SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED;

        if ($isLocked && $request->isMethod(Request::METHOD_POST)) {
            $this->addErrorMessage(static::GLOSSARY_KEY_LOCKED_IN_REVIEW);

            return $this->redirectToPriceTolerances($uuid);
        }

        $form = $this->getFactory()->createRecurringOrderPriceToleranceForm(
            $this->buildFormData($recurringScheduleTransfer),
            ['disabled' => $isLocked],
        )->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->saveTolerances($form, $customerTransfer, $recurringScheduleTransfer)) {
            return $this->redirectResponseInternal(static::ROUTE_NAME_RECURRING_ORDER_DETAIL, [static::REQUEST_PARAM_UUID => $uuid]);
        }

        return $this->createPriceTolerancesView($recurringScheduleTransfer, $form, $isLocked);
    }

    protected function saveTolerances(FormInterface $form, CustomerTransfer $customerTransfer, RecurringScheduleTransfer $recurringScheduleTransfer): bool
    {
        $formData = $this->restrictFormDataToTheSchedule($form, $recurringScheduleTransfer);

        $recurringScheduleCollectionRequestTransfer = $this->getFactory()
            ->createRecurringScheduleEventRequestMapper()
            ->mapPriceToleranceFormDataToRecurringScheduleCollectionRequest(
                $formData,
                $customerTransfer,
                new RecurringScheduleCollectionRequestTransfer(),
            );

        $recurringScheduleCollectionResponseTransfer = $this->getFactory()
            ->getOrderExperienceManagementClient()
            ->updateRecurringScheduleCollection($recurringScheduleCollectionRequestTransfer);

        if ($recurringScheduleCollectionResponseTransfer->getErrors()->count() > 0) {
            $this->addResponseErrorMessages($recurringScheduleCollectionResponseTransfer->getErrors(), static::GLOSSARY_KEY_SAVE_ERROR);

            return false;
        }

        $this->addSuccessMessage(static::GLOSSARY_KEY_SAVE_SUCCESS);

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    protected function restrictFormDataToTheSchedule(FormInterface $form, RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        /** @var array<string, mixed> $formData */
        $formData = $form->getData();
        $editableItemKeys = [];

        foreach ($this->getFactory()->createPriceToleranceRowBuilder()->getEditableItems($recurringScheduleTransfer) as $editableItemTransfer) {
            $editableItemKeys[$editableItemTransfer->getGroupKey() ?? $editableItemTransfer->getBundleItemIdentifier()] = true;
        }

        $formData[RecurringOrderPriceToleranceForm::FIELD_UUID] = $recurringScheduleTransfer->getUuid();
        $formData[RecurringOrderPriceToleranceForm::FIELD_ITEMS] = array_values(array_filter(
            (array)($formData[RecurringOrderPriceToleranceForm::FIELD_ITEMS] ?? []),
            static fn (array $itemFormData): bool => isset($editableItemKeys[$itemFormData[RecurringOrderPriceToleranceItemForm::FIELD_GROUP_KEY] ?? $itemFormData[RecurringOrderPriceToleranceItemForm::FIELD_BUNDLE_ITEM_IDENTIFIER] ?? '']),
        ));

        return $formData;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildFormData(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        return [
            RecurringOrderPriceToleranceForm::FIELD_UUID => $recurringScheduleTransfer->getUuid(),
            RecurringOrderPriceToleranceForm::FIELD_ORDER_TOLERANCE => ($recurringScheduleTransfer->getPriceTolerancePercent() ?? Decimal::create(0))->trim()->toString(),
            RecurringOrderPriceToleranceForm::FIELD_IGNORE_PRODUCT_PRICE_CHANGES => (bool)$recurringScheduleTransfer->getIsProductPriceChangeIgnored(),
            RecurringOrderPriceToleranceForm::FIELD_ITEMS => array_map(
                static fn (RecurringScheduleItemTransfer $recurringScheduleItemTransfer): array => [
                    RecurringOrderPriceToleranceItemForm::FIELD_GROUP_KEY => $recurringScheduleItemTransfer->getGroupKey(),
                    RecurringOrderPriceToleranceItemForm::FIELD_BUNDLE_ITEM_IDENTIFIER => $recurringScheduleItemTransfer->getGroupKey() === null ? $recurringScheduleItemTransfer->getBundleItemIdentifier() : null,
                    RecurringOrderPriceToleranceItemForm::FIELD_TOLERANCE => ($recurringScheduleItemTransfer->getPriceTolerancePercent() ?? Decimal::create(0))->trim()->toString(),
                ],
                $this->getFactory()->createPriceToleranceRowBuilder()->getEditableItems($recurringScheduleTransfer),
            ),
        ];
    }

    protected function redirectToPriceTolerances(string $uuid): RedirectResponse
    {
        return $this->redirectResponseInternal(static::ROUTE_NAME_RECURRING_ORDER_PRICE_TOLERANCES, [static::REQUEST_PARAM_UUID => $uuid]);
    }

    protected function createPriceTolerancesView(RecurringScheduleTransfer $recurringScheduleTransfer, FormInterface $form, bool $isLocked): View
    {
        $orderTotal = (int)$recurringScheduleTransfer->getEstimatedTotal();
        $tolerancePercent = $recurringScheduleTransfer->getPriceTolerancePercent() ?? Decimal::create(0);

        return $this->view(
            [
                'schedule' => $recurringScheduleTransfer,
                'form' => $form->createView(),
                'isLocked' => $isLocked,
                'orderTotal' => $orderTotal,
                'isOrderToleranceSet' => $tolerancePercent->isPositive(),
                'productRows' => $this->getFactory()->createPriceToleranceRowBuilder()->buildProductRows($recurringScheduleTransfer),
                'orderReviewThreshold' => $this->getFactory()
                    ->createOrderTotalReviewThresholdCalculator()
                    ->calculate($orderTotal, $tolerancePercent),
            ],
            [],
            '@OrderExperienceManagement/views/price-tolerances/price-tolerances.twig',
        );
    }
}
