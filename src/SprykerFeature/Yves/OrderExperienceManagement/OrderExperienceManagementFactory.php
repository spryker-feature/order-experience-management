<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Client\CompanyBusinessUnit\CompanyBusinessUnitClientInterface;
use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Client\Glossary\GlossaryClientInterface;
use Spryker\Client\Quote\QuoteClientInterface;
use Spryker\Shared\Application\ApplicationConstants;
use Spryker\Yves\Kernel\AbstractFactory;
use SprykerFeature\Client\OrderExperienceManagement\OrderExperienceManagementClientInterface;
use SprykerFeature\Service\OrderExperienceManagement\OrderExperienceManagementServiceInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Form\DataProvider\RecurringOrderApproveFormDataProvider;
use SprykerFeature\Yves\OrderExperienceManagement\Form\DataProvider\RecurringOrderSearchFormDataProvider;
use SprykerFeature\Yves\OrderExperienceManagement\Form\DataProvider\RecurringOrderSelectorFormDataProvider;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderActionForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderApproveForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderResumeForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderSearchForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderSelectorForm;
use SprykerFeature\Yves\OrderExperienceManagement\FormHandler\RecurringOrderSearchFormHandler;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\RecurringOrderAttentionBannerReader;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\RecurringScheduleQuoteDataDeserializer;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\RecurringScheduleQuoteDataDeserializerInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\RecurringScheduleReader;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\RecurringScheduleReaderInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Updater\QuoteSessionUpdater;
use SprykerFeature\Yves\OrderExperienceManagement\Updater\QuoteSessionUpdaterInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Updater\RecurringOrderQuoteUpdater;
use SprykerFeature\Yves\OrderExperienceManagement\Updater\RecurringOrderQuoteUpdaterInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Updater\RecurringOrderScheduleResumeUpdater;
use SprykerFeature\Yves\OrderExperienceManagement\Updater\RecurringOrderScheduleResumeUpdaterInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 * @method \SprykerFeature\Client\OrderExperienceManagement\OrderExperienceManagementClientInterface getClient()
 */
class OrderExperienceManagementFactory extends AbstractFactory
{
    public function createRecurringOrderSelectorForm(QuoteTransfer $quoteTransfer): FormInterface
    {
        $dataProvider = $this->createRecurringOrderSelectorFormDataProvider();

        return $this->createRecurringOrderSelectorFormFromDataAndOptions(
            $dataProvider->getData($quoteTransfer),
            $dataProvider->getOptions(),
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createRecurringOrderSelectorFormFromDataAndOptions(mixed $data, array $options): FormInterface
    {
        return $this->getFormFactory()->create(RecurringOrderSelectorForm::class, $data, $options);
    }

    public function createRecurringOrderSelectorFormDataProvider(): RecurringOrderSelectorFormDataProvider
    {
        return new RecurringOrderSelectorFormDataProvider($this->getConfig());
    }

    public function createRecurringOrderResumeForm(?string $uuid = null): FormInterface
    {
        return $this->getFormFactory()->create(
            RecurringOrderResumeForm::class,
            [RecurringOrderResumeForm::FIELD_UUID => $uuid],
        );
    }

    public function createRecurringOrderActionForm(?string $uuid = null): FormInterface
    {
        return $this->getFormFactory()->create(
            RecurringOrderActionForm::class,
            [RecurringOrderActionForm::FIELD_UUID => $uuid],
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createRecurringOrderApproveForm(array $data = []): FormInterface
    {
        return $this->getFormFactory()->create(RecurringOrderApproveForm::class, $data);
    }

    public function createRecurringOrderApproveFormDataProvider(): RecurringOrderApproveFormDataProvider
    {
        return new RecurringOrderApproveFormDataProvider();
    }

    public function getOrderExperienceManagementService(): OrderExperienceManagementServiceInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::SERVICE_ORDER_EXPERIENCE_MANAGEMENT);
    }

    public function createQuoteSessionUpdater(): QuoteSessionUpdaterInterface
    {
        return new QuoteSessionUpdater($this->getQuoteClient());
    }

    public function createRecurringOrderScheduleResumeUpdater(): RecurringOrderScheduleResumeUpdaterInterface
    {
        return new RecurringOrderScheduleResumeUpdater($this->getOrderExperienceManagementClient());
    }

    public function createRecurringOrderQuoteUpdater(): RecurringOrderQuoteUpdaterInterface
    {
        return new RecurringOrderQuoteUpdater(
            $this->getOrderExperienceManagementClient(),
            $this->getCustomerClient(),
            $this->createQuoteSessionUpdater(),
        );
    }

    public function getOrderExperienceManagementClient(): OrderExperienceManagementClientInterface
    {
        return $this->getClient();
    }

    public function getCustomerClient(): CustomerClientInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::CLIENT_CUSTOMER);
    }

    public function getGlossaryClient(): GlossaryClientInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::CLIENT_GLOSSARY);
    }

    public function getQuoteClient(): QuoteClientInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::CLIENT_QUOTE);
    }

    public function getFormFactory(): FormFactory
    {
        return $this->getProvidedDependency(ApplicationConstants::FORM_FACTORY);
    }

    public function getCsrfTokenManager(): CsrfTokenManagerInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::SERVICE_FORM_CSRF_PROVIDER);
    }

    public function createRecurringOrderSearchForm(CustomerTransfer $customerTransfer): FormInterface
    {
        $dataProvider = $this->createRecurringOrderSearchFormDataProvider();

        return $this->getFormFactory()->create(
            RecurringOrderSearchForm::class,
            null,
            $dataProvider->getOptions($customerTransfer),
        );
    }

    public function createRecurringOrderSearchFormDataProvider(): RecurringOrderSearchFormDataProvider
    {
        return new RecurringOrderSearchFormDataProvider(
            $this->getConfig(),
            $this->getCompanyBusinessUnitClient(),
        );
    }

    public function getCompanyBusinessUnitClient(): CompanyBusinessUnitClientInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::CLIENT_COMPANY_BUSINESS_UNIT);
    }

    public function createRecurringOrderSearchFormHandler(): RecurringOrderSearchFormHandler
    {
        return new RecurringOrderSearchFormHandler();
    }

    public function createRecurringScheduleReader(): RecurringScheduleReaderInterface
    {
        return new RecurringScheduleReader($this->getOrderExperienceManagementClient());
    }

    public function createRecurringScheduleQuoteDataDeserializer(): RecurringScheduleQuoteDataDeserializerInterface
    {
        return new RecurringScheduleQuoteDataDeserializer();
    }

    public function createRecurringOrderAttentionBannerReader(): RecurringOrderAttentionBannerReader
    {
        return new RecurringOrderAttentionBannerReader($this->getOrderExperienceManagementClient(), $this->getConfig());
    }
}
