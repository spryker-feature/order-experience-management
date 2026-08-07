<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Client\Catalog\CatalogClientInterface;
use Spryker\Client\CompanyBusinessUnit\CompanyBusinessUnitClientInterface;
use Spryker\Client\Currency\CurrencyClientInterface;
use Spryker\Client\Customer\CustomerClientInterface;
use Spryker\Client\GlossaryStorage\GlossaryStorageClientInterface;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\MerchantStorage\MerchantStorageClientInterface;
use Spryker\Client\Price\PriceClientInterface;
use Spryker\Client\ProductAlternativeStorage\ProductAlternativeStorageClientInterface;
use Spryker\Client\ProductOfferStorage\ProductOfferStorageClientInterface;
use Spryker\Client\ProductPackagingUnitStorage\ProductPackagingUnitStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use Spryker\Client\Quote\QuoteClientInterface;
use Spryker\Client\Shipment\ShipmentClientInterface;
use Spryker\Client\ShipmentTypeStorage\ShipmentTypeStorageClientInterface;
use Spryker\Shared\Application\ApplicationConstants;
use Spryker\Yves\Kernel\AbstractFactory;
use SprykerFeature\Client\OrderExperienceManagement\OrderExperienceManagementClientInterface;
use SprykerFeature\Service\OrderExperienceManagement\OrderExperienceManagementServiceInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Builder\AddedItemProbeQuoteBuilder;
use SprykerFeature\Yves\OrderExperienceManagement\Builder\AddedItemProbeQuoteBuilderInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Checker\AddedProductConcreteRestrictionChecker;
use SprykerFeature\Yves\OrderExperienceManagement\Checker\AddedProductConcreteRestrictionCheckerInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Checker\AddedProductMeasurementUnitChecker;
use SprykerFeature\Yves\OrderExperienceManagement\Checker\AddedProductMeasurementUnitCheckerInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Checker\AddedProductPackagingUnitChecker;
use SprykerFeature\Yves\OrderExperienceManagement\Checker\AddedProductPackagingUnitCheckerInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Expander\RecurringScheduleSubstituteOptionExpander;
use SprykerFeature\Yves\OrderExperienceManagement\Expander\RecurringScheduleSubstituteOptionExpanderInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Extractor\ProductConcreteIdExtractor;
use SprykerFeature\Yves\OrderExperienceManagement\Extractor\ProductConcreteIdExtractorInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Filter\ProductConcreteAvailabilityFilter;
use SprykerFeature\Yves\OrderExperienceManagement\Filter\ProductConcreteAvailabilityFilterInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Filter\ProductConcreteRestrictionFilter;
use SprykerFeature\Yves\OrderExperienceManagement\Filter\ProductConcreteRestrictionFilterInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Filter\ProductOfferAvailabilityFilter;
use SprykerFeature\Yves\OrderExperienceManagement\Filter\ProductOfferAvailabilityFilterInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Form\DataProvider\RecurringOrderApproveFormDataProvider;
use SprykerFeature\Yves\OrderExperienceManagement\Form\DataProvider\RecurringOrderSearchFormDataProvider;
use SprykerFeature\Yves\OrderExperienceManagement\Form\DataProvider\RecurringOrderSelectorFormDataProvider;
use SprykerFeature\Yves\OrderExperienceManagement\Form\DataProvider\RecurringScheduleEditFormDataProvider;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderActionForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderApproveForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderResumeForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderSearchForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderSelectorForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringScheduleEditForm;
use SprykerFeature\Yves\OrderExperienceManagement\FormHandler\RecurringOrderSearchFormHandler;
use SprykerFeature\Yves\OrderExperienceManagement\Mapper\RecurringScheduleEventRequestMapper;
use SprykerFeature\Yves\OrderExperienceManagement\Mapper\RecurringScheduleEventRequestMapperInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedItemAddressChoicesReader;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedItemAddressChoicesReaderInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedItemShipmentMethodReader;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedItemShipmentMethodReaderInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedMerchantProductReader;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedMerchantProductReaderInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedProductConcreteViewReader;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedProductConcreteViewReaderInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedProductOfferReader;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedProductOfferReaderInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedProductSearchReader;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedProductSearchReaderInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\ProductConcreteAvailabilityReader;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\ProductConcreteAvailabilityReaderInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\RecurringOrderAttentionBannerReader;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\RecurringOrderAttentionBannerReaderInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\RecurringScheduleQuoteDataDeserializer;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\RecurringScheduleQuoteDataDeserializerInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\RecurringScheduleReader;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\RecurringScheduleReaderInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Resolver\AddedItemShippingAddressResolver;
use SprykerFeature\Yves\OrderExperienceManagement\Resolver\AddedItemShippingAddressResolverInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Resolver\DeliveryShipmentTypeResolver;
use SprykerFeature\Yves\OrderExperienceManagement\Resolver\DeliveryShipmentTypeResolverInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Resolver\ProductOfferStorageResolver;
use SprykerFeature\Yves\OrderExperienceManagement\Resolver\ProductOfferStorageResolverInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Resolver\ScheduleReviewContextMismatchResolver;
use SprykerFeature\Yves\OrderExperienceManagement\Resolver\ScheduleReviewContextMismatchResolverInterface;
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
     * @param array<string, mixed> $options
     */
    public function createRecurringOrderApproveForm(array $data = [], array $options = []): FormInterface
    {
        return $this->getFormFactory()->create(RecurringOrderApproveForm::class, $data, $options);
    }

    /**
     * @return array<\SprykerFeature\Yves\OrderExperienceManagement\Dependency\Plugin\RecurringOrderApproveFormExpanderPluginInterface>
     */
    public function getRecurringOrderApproveFormExpanderPlugins(): array
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::PLUGINS_RECURRING_ORDER_APPROVE_FORM_EXPANDER);
    }

    public function createRecurringOrderApproveFormDataProvider(): RecurringOrderApproveFormDataProvider
    {
        return new RecurringOrderApproveFormDataProvider($this->getConfig());
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $options
     */
    public function createRecurringScheduleEditForm(array $data = [], array $options = []): FormInterface
    {
        return $this->getFormFactory()->create(RecurringScheduleEditForm::class, $data, $options);
    }

    /**
     * @return array<\SprykerFeature\Yves\OrderExperienceManagement\Dependency\Plugin\RecurringScheduleEditFormExpanderPluginInterface>
     */
    public function getRecurringScheduleEditFormExpanderPlugins(): array
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::PLUGINS_RECURRING_SCHEDULE_EDIT_FORM_EXPANDER);
    }

    public function createRecurringScheduleEditFormDataProvider(): RecurringScheduleEditFormDataProvider
    {
        return new RecurringScheduleEditFormDataProvider();
    }

    public function createRecurringScheduleEventRequestMapper(): RecurringScheduleEventRequestMapperInterface
    {
        return new RecurringScheduleEventRequestMapper();
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

    public function getGlossaryStorageClient(): GlossaryStorageClientInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::CLIENT_GLOSSARY_STORAGE);
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

    public function getProductOfferStorageClient(): ProductOfferStorageClientInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::CLIENT_PRODUCT_OFFER_STORAGE);
    }

    public function getShipmentClient(): ShipmentClientInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::CLIENT_SHIPMENT);
    }

    public function getShipmentTypeStorageClient(): ShipmentTypeStorageClientInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::CLIENT_SHIPMENT_TYPE_STORAGE);
    }

    public function createAddedItemAddressChoicesReader(): AddedItemAddressChoicesReaderInterface
    {
        return new AddedItemAddressChoicesReader();
    }

    public function createAddedItemShipmentMethodReader(): AddedItemShipmentMethodReaderInterface
    {
        return new AddedItemShipmentMethodReader(
            $this->createRecurringScheduleReader(),
            $this->createAddedItemShippingAddressResolver(),
            $this->createProductOfferStorageResolver(),
            $this->createDeliveryShipmentTypeResolver(),
            $this->createAddedItemProbeQuoteBuilder(),
            $this->getShipmentClient(),
        );
    }

    public function createAddedItemShippingAddressResolver(): AddedItemShippingAddressResolverInterface
    {
        return new AddedItemShippingAddressResolver();
    }

    public function createProductOfferStorageResolver(): ProductOfferStorageResolverInterface
    {
        return new ProductOfferStorageResolver($this->getProductOfferStorageClient());
    }

    public function createScheduleReviewContextMismatchResolver(): ScheduleReviewContextMismatchResolverInterface
    {
        return new ScheduleReviewContextMismatchResolver($this->getCurrencyClient(), $this->getPriceClient());
    }

    public function createDeliveryShipmentTypeResolver(): DeliveryShipmentTypeResolverInterface
    {
        return new DeliveryShipmentTypeResolver(
            $this->getShipmentTypeStorageClient(),
            $this->getConfig()->getSupportedAddedItemShipmentTypeKeys(),
        );
    }

    public function createAddedItemProbeQuoteBuilder(): AddedItemProbeQuoteBuilderInterface
    {
        return new AddedItemProbeQuoteBuilder();
    }

    public function createRecurringOrderSearchFormHandler(): RecurringOrderSearchFormHandler
    {
        return new RecurringOrderSearchFormHandler();
    }

    public function createRecurringScheduleReader(): RecurringScheduleReaderInterface
    {
        return new RecurringScheduleReader($this->getOrderExperienceManagementClient());
    }

    public function createRecurringScheduleSubstituteOptionExpander(): RecurringScheduleSubstituteOptionExpanderInterface
    {
        return new RecurringScheduleSubstituteOptionExpander(
            $this->getProductAlternativeStorageClient(),
            $this->getLocaleClient(),
            $this->getConfig(),
        );
    }

    public function getProductAlternativeStorageClient(): ProductAlternativeStorageClientInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::CLIENT_PRODUCT_ALTERNATIVE_STORAGE);
    }

    public function getLocaleClient(): LocaleClientInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::CLIENT_LOCALE);
    }

    public function getCurrencyClient(): CurrencyClientInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::CLIENT_CURRENCY);
    }

    public function getPriceClient(): PriceClientInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::CLIENT_PRICE);
    }

    public function createRecurringScheduleQuoteDataDeserializer(): RecurringScheduleQuoteDataDeserializerInterface
    {
        return new RecurringScheduleQuoteDataDeserializer();
    }

    public function createRecurringOrderAttentionBannerReader(): RecurringOrderAttentionBannerReaderInterface
    {
        return new RecurringOrderAttentionBannerReader($this->getConfig());
    }

    public function createAddedProductSearchReader(): AddedProductSearchReaderInterface
    {
        return new AddedProductSearchReader(
            $this->getCatalogClient(),
            $this->createProductConcreteAvailabilityFilter(),
            $this->getConfig(),
            $this->createProductConcreteRestrictionFilter(),
        );
    }

    public function createProductConcreteAvailabilityFilter(): ProductConcreteAvailabilityFilterInterface
    {
        return new ProductConcreteAvailabilityFilter(
            $this->createProductConcreteAvailabilityReader(),
            $this->createProductConcreteIdExtractor(),
        );
    }

    public function createProductConcreteIdExtractor(): ProductConcreteIdExtractorInterface
    {
        return new ProductConcreteIdExtractor();
    }

    public function createProductConcreteAvailabilityReader(): ProductConcreteAvailabilityReaderInterface
    {
        return new ProductConcreteAvailabilityReader(
            $this->getProductStorageClient(),
            $this->getLocaleClient(),
        );
    }

    public function createProductConcreteRestrictionFilter(): ProductConcreteRestrictionFilterInterface
    {
        return new ProductConcreteRestrictionFilter(
            $this->createAddedProductConcreteViewReader(),
            $this->createAddedProductConcreteRestrictionChecker(),
            $this->createProductConcreteIdExtractor(),
        );
    }

    public function createAddedProductConcreteRestrictionChecker(): AddedProductConcreteRestrictionCheckerInterface
    {
        return new AddedProductConcreteRestrictionChecker(
            $this->createAddedProductConcreteViewReader(),
            $this->getConfig(),
            $this->createAddedProductMeasurementUnitChecker(),
            $this->createAddedProductPackagingUnitChecker(),
            $this->getAddedProductConcreteRestrictionPlugins(),
        );
    }

    /**
     * @return array<\SprykerFeature\Yves\OrderExperienceManagement\Dependency\Plugin\AddedProductConcreteRestrictionPluginInterface>
     */
    public function getAddedProductConcreteRestrictionPlugins(): array
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::PLUGINS_ADDED_PRODUCT_CONCRETE_RESTRICTION);
    }

    public function createAddedProductMeasurementUnitChecker(): AddedProductMeasurementUnitCheckerInterface
    {
        return new AddedProductMeasurementUnitChecker();
    }

    public function createAddedProductPackagingUnitChecker(): AddedProductPackagingUnitCheckerInterface
    {
        return new AddedProductPackagingUnitChecker(
            $this->getProductPackagingUnitStorageClient(),
            $this->createProductConcreteIdExtractor(),
        );
    }

    public function getProductPackagingUnitStorageClient(): ProductPackagingUnitStorageClientInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::CLIENT_PRODUCT_PACKAGING_UNIT_STORAGE);
    }

    public function createAddedProductConcreteViewReader(): AddedProductConcreteViewReaderInterface
    {
        return new AddedProductConcreteViewReader(
            $this->getProductStorageClient(),
            $this->getLocaleClient(),
        );
    }

    public function getCatalogClient(): CatalogClientInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::CLIENT_CATALOG);
    }

    public function getProductStorageClient(): ProductStorageClientInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::CLIENT_PRODUCT_STORAGE);
    }

    public function createAddedProductOfferReader(): AddedProductOfferReaderInterface
    {
        return new AddedProductOfferReader(
            $this->getProductOfferStorageClient(),
            $this->getMerchantStorageClient(),
            $this->createProductOfferAvailabilityFilter(),
            $this->createAddedMerchantProductReader(),
            $this->getConfig(),
            $this->createAddedProductConcreteRestrictionChecker(),
        );
    }

    public function createAddedMerchantProductReader(): AddedMerchantProductReaderInterface
    {
        return new AddedMerchantProductReader(
            $this->getProductStorageClient(),
            $this->getMerchantStorageClient(),
            $this->getLocaleClient(),
            $this->createProductConcreteAvailabilityReader(),
            $this->getConfig(),
        );
    }

    public function createProductOfferAvailabilityFilter(): ProductOfferAvailabilityFilterInterface
    {
        return new ProductOfferAvailabilityFilter();
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createAddedProductOfferForm(array $options): FormInterface
    {
        return $this->getFormFactory()->create($this->getAddedProductOfferFormType(), null, $options);
    }

    /**
     * @return class-string<\Symfony\Component\Form\FormTypeInterface>
     */
    public function getAddedProductOfferFormType(): string
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FORM_TYPE_ADDED_PRODUCT_OFFER);
    }

    public function getMerchantStorageClient(): MerchantStorageClientInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::CLIENT_MERCHANT_STORAGE);
    }
}
