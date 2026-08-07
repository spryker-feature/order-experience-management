<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication;

use Spryker\Zed\Company\Business\CompanyFacadeInterface;
use Spryker\Zed\CompanyBusinessUnit\Business\CompanyBusinessUnitFacadeInterface;
use Spryker\Zed\Glossary\Business\GlossaryFacadeInterface;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\Merchant\Business\MerchantFacadeInterface;
use Spryker\Zed\Sales\Business\SalesFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Form\DataProvider\RecurringScheduleTableFilterFormDataProvider;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Form\RecurringScheduleTableFilterForm;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Command\AdvanceScheduleCommandPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Command\CompletePlacementCommandPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Command\NotifyBuyerCommandPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Command\PlaceOrderCommandPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition\IsOrderPlacedConditionPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition\IsPlacementDueConditionPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition\IsScheduleDueConditionPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\Condition\IsScheduleValidConditionPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Reader\RecurringScheduleReader;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Resolver\ConfigurableBundleNameResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Resolver\MerchantNameResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Table\RecurringScheduleTable;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementDependencyProvider;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface getRepository()
 */
class OrderExperienceManagementCommunicationFactory extends AbstractCommunicationFactory
{
    public function createRecurringScheduleTable(): RecurringScheduleTable
    {
        return new RecurringScheduleTable($this->getFacade());
    }

    public function createRecurringScheduleTableFilterForm(Request $request): FormInterface
    {
        $recurringScheduleTableFilterFormDataProvider = $this->createRecurringScheduleTableFilterFormDataProvider();

        return $this->getFormFactory()->create(
            RecurringScheduleTableFilterForm::class,
            $recurringScheduleTableFilterFormDataProvider->getData(),
            $recurringScheduleTableFilterFormDataProvider->getOptions($request),
        );
    }

    public function createRecurringScheduleTableFilterFormDataProvider(): RecurringScheduleTableFilterFormDataProvider
    {
        return new RecurringScheduleTableFilterFormDataProvider(
            $this->getConfig(),
            $this->getCompanyFacade(),
            $this->getCompanyBusinessUnitFacade(),
        );
    }

    public function getCompanyFacade(): CompanyFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_COMPANY);
    }

    public function getCompanyBusinessUnitFacade(): CompanyBusinessUnitFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_COMPANY_BUSINESS_UNIT);
    }

    public function getSalesFacade(): SalesFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_SALES);
    }

    public function getMerchantFacade(): MerchantFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_MERCHANT);
    }

    public function getGlossaryFacade(): GlossaryFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_GLOSSARY);
    }

    public function getLocaleFacade(): LocaleFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_LOCALE);
    }

    public function createRecurringScheduleReader(): RecurringScheduleReader
    {
        return new RecurringScheduleReader($this->getFacade());
    }

    public function createConfigurableBundleNameResolver(): ConfigurableBundleNameResolver
    {
        return new ConfigurableBundleNameResolver($this->getGlossaryFacade(), $this->getLocaleFacade());
    }

    public function createMerchantNameResolver(): MerchantNameResolver
    {
        return new MerchantNameResolver($this->getMerchantFacade());
    }

    /**
     * @return array<string, \Spryker\Zed\StateMachine\Dependency\Plugin\CommandPluginInterface>
     */
    public function getStateMachineCommandPlugins(): array
    {
        $placeOrderCommandPlugin = $this->createPlaceOrderCommandPlugin();
        $advanceScheduleCommandPlugin = $this->createAdvanceScheduleCommandPlugin();
        $completePlacementCommandPlugin = $this->createCompletePlacementCommandPlugin();
        $notifyBuyerCommandPlugin = $this->createNotifyBuyerCommandPlugin();

        return [
            $placeOrderCommandPlugin->getName() => $placeOrderCommandPlugin,
            $advanceScheduleCommandPlugin->getName() => $advanceScheduleCommandPlugin,
            $completePlacementCommandPlugin->getName() => $completePlacementCommandPlugin,
            $notifyBuyerCommandPlugin->getName() => $notifyBuyerCommandPlugin,
        ];
    }

    public function createPlaceOrderCommandPlugin(): PlaceOrderCommandPlugin
    {
        return new PlaceOrderCommandPlugin();
    }

    public function createAdvanceScheduleCommandPlugin(): AdvanceScheduleCommandPlugin
    {
        return new AdvanceScheduleCommandPlugin();
    }

    public function createCompletePlacementCommandPlugin(): CompletePlacementCommandPlugin
    {
        return new CompletePlacementCommandPlugin();
    }

    public function createNotifyBuyerCommandPlugin(): NotifyBuyerCommandPlugin
    {
        return new NotifyBuyerCommandPlugin();
    }

    /**
     * @return array<string, \Spryker\Zed\StateMachine\Dependency\Plugin\ConditionPluginInterface>
     */
    public function getStateMachineConditionPlugins(): array
    {
        $isScheduleDueConditionPlugin = $this->createIsScheduleDueConditionPlugin();
        $isPlacementDueConditionPlugin = $this->createIsPlacementDueConditionPlugin();
        $isScheduleValidConditionPlugin = $this->createIsScheduleValidConditionPlugin();
        $isOrderPlacedConditionPlugin = $this->createIsOrderPlacedConditionPlugin();

        return [
            $isScheduleDueConditionPlugin->getName() => $isScheduleDueConditionPlugin,
            $isPlacementDueConditionPlugin->getName() => $isPlacementDueConditionPlugin,
            $isScheduleValidConditionPlugin->getName() => $isScheduleValidConditionPlugin,
            $isOrderPlacedConditionPlugin->getName() => $isOrderPlacedConditionPlugin,
        ];
    }

    public function createIsScheduleDueConditionPlugin(): IsScheduleDueConditionPlugin
    {
        return new IsScheduleDueConditionPlugin();
    }

    public function createIsPlacementDueConditionPlugin(): IsPlacementDueConditionPlugin
    {
        return new IsPlacementDueConditionPlugin();
    }

    public function createIsScheduleValidConditionPlugin(): IsScheduleValidConditionPlugin
    {
        return new IsScheduleValidConditionPlugin();
    }

    public function createIsOrderPlacedConditionPlugin(): IsOrderPlacedConditionPlugin
    {
        return new IsOrderPlacedConditionPlugin();
    }
}
