<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business;

use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Zed\Cart\Business\CartFacadeInterface;
use Spryker\Zed\Checkout\Business\CheckoutFacadeInterface;
use Spryker\Zed\CompanyUser\Business\CompanyUserFacadeInterface;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\Mail\Business\MailFacadeInterface;
use Spryker\Zed\Payment\Business\PaymentFacadeInterface;
use Spryker\Zed\PriceCartConnector\Business\PriceCartConnectorFacadeInterface;
use Spryker\Zed\ProductPackagingUnit\Business\ProductPackagingUnitFacadeInterface;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
use Spryker\Zed\StateMachine\Business\StateMachineFacadeInterface;
use SprykerFeature\Service\OrderExperienceManagement\OrderExperienceManagementServiceInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Checker\RecurringOrderPreConditionChecker;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Checker\RecurringOrderPreConditionCheckerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Mapper\RecurringOrderNotificationMailMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Mapper\RecurringOrderNotificationMailMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Reader\RecurringScheduleBuyerReader;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Reader\RecurringScheduleBuyerReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\RecurringOrderBuyerMailNotificationSender;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\RecurringOrderBuyerMailNotificationSenderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Resolver\NotificationRecipientResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Resolver\NotificationRecipientResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\ItemShipmentMethodResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\ItemShipmentMethodResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\Mapper\PlaceableItemMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\Mapper\PlaceableItemMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteDeserializer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteDeserializerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteItemBuilder;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteItemBuilderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\RecurringOrderPlacer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\RecurringOrderPlacerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\RecurringOrderQuoteBuilder;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\RecurringOrderQuoteBuilderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\RecurringOrder\RecurringOrderQuoteUpdater;
use SprykerFeature\Zed\OrderExperienceManagement\Business\RecurringOrder\RecurringOrderQuoteUpdaterInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleCustomerExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleCustomerExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleHistoryExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleHistoryExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleItemExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleItemExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleQuoteDataExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleQuoteDataExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleSkipPreviewExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleSkipPreviewExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Filter\RecurringScheduleAccessFilter;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Filter\RecurringScheduleAccessFilterInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Grouper\RecurringScheduleItemGrouper;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Grouper\RecurringScheduleItemGrouperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper\RecurringScheduleItemMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper\RecurringScheduleItemMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper\RecurringScheduleMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper\RecurringScheduleMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Reader\AccessibleRecurringScheduleReader;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Reader\AccessibleRecurringScheduleReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Reader\RecurringScheduleReader;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Reader\RecurringScheduleReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\ConfiguredBundleUnavailabilityExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\ConfiguredBundleUnavailabilityExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\ScheduleReviewApprover;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\ScheduleReviewApproverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\ScheduleReviewBuilder;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\ScheduleReviewBuilderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\ScheduleReviewChangeApplier;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\ScheduleReviewChangeApplierInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\ScheduleReviewMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\ScheduleReviewMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\ScheduleReviewSummaryCalculator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\ScheduleReviewSummaryCalculatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Sanitizer\QuoteSanitizer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Sanitizer\QuoteSanitizerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\ScheduleAdvancer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\ScheduleAdvancerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\ScheduleEventTrigger;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\ScheduleEventTriggerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\ScheduleSkipper;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\ScheduleSkipperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\SmStateStatusResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\SmStateStatusResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\CheckoutPlaceabilityValidator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\CheckoutPlaceabilityValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\CheckoutValidationResultBuilder;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\CheckoutValidationResultBuilderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift\BundlePriceDriftChecker;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift\ItemPriceDriftChecker;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift\PriceDriftCheckerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\RecurringScheduleCheckoutValidator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\RecurringScheduleCheckoutValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\RecurringSchedulePrePlacementValidator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\RecurringSchedulePrePlacementValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\RecurringSchedulePriceValidator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\RecurringSchedulePriceValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\RecurringScheduleValidationResultExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\RecurringScheduleValidationResultExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\ScheduleItemRepricer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\ScheduleItemRepricerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer\ScheduleItemUpdater;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer\ScheduleItemUpdaterInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer\ScheduleResumeWriter;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer\ScheduleResumeWriterInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer\ScheduleStateMachineStateWriter;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer\ScheduleStateMachineStateWriterInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer\ScheduleWriter;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer\ScheduleWriterInterface;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementDependencyProvider;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface getEntityManager()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface getRepository()
 */
class OrderExperienceManagementBusinessFactory extends AbstractBusinessFactory
{
    public function createScheduleWriter(): ScheduleWriterInterface
    {
        return new ScheduleWriter(
            $this->getEntityManager(),
            $this->getStateMachineFacade(),
            $this->getConfig(),
            $this->createRecurringScheduleCheckoutValidator(),
            $this->createRecurringScheduleMapper(),
            $this->createRecurringScheduleItemMapper(),
            $this->getRepository(),
            $this->createQuoteSanitizer(),
        );
    }

    public function createQuoteSanitizer(): QuoteSanitizerInterface
    {
        return new QuoteSanitizer();
    }

    public function createRecurringScheduleMapper(): RecurringScheduleMapperInterface
    {
        return new RecurringScheduleMapper($this->createCadenceResolver(), $this->getUtilEncodingService(), $this->getConfig(), $this->getLocaleFacade());
    }

    public function getLocaleFacade(): LocaleFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_LOCALE);
    }

    public function createRecurringScheduleItemMapper(): RecurringScheduleItemMapperInterface
    {
        return new RecurringScheduleItemMapper($this->getUtilEncodingService());
    }

    public function getUtilEncodingService(): UtilEncodingServiceInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::SERVICE_UTIL_ENCODING);
    }

    public function createCadenceResolver(): CadenceResolverInterface
    {
        return new CadenceResolver(
            $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::PLUGINS_CADENCE_TYPE),
        );
    }

    public function createRecurringOrderPreConditionChecker(): RecurringOrderPreConditionCheckerInterface
    {
        return new RecurringOrderPreConditionChecker($this->createRecurringScheduleCheckoutValidator());
    }

    public function createRecurringScheduleCheckoutValidator(): RecurringScheduleCheckoutValidatorInterface
    {
        return new RecurringScheduleCheckoutValidator(
            $this->getOrderExperienceManagementService(),
            $this->createCadenceResolver(),
        );
    }

    public function createRecurringSchedulePrePlacementValidator(): RecurringSchedulePrePlacementValidatorInterface
    {
        return new RecurringSchedulePrePlacementValidator(
            $this->getRepository(),
            $this->getEntityManager(),
            $this->createRecurringOrderBuyerMailNotificationSender(),
            $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::PLUGINS_SCHEDULE_VALIDATOR),
            $this->createRecurringScheduleValidationResultExpander(),
        );
    }

    public function createRecurringScheduleValidationResultExpander(): RecurringScheduleValidationResultExpanderInterface
    {
        return new RecurringScheduleValidationResultExpander(
            $this->getConfig(),
        );
    }

    public function createCheckoutPlaceabilityValidator(): CheckoutPlaceabilityValidatorInterface
    {
        return new CheckoutPlaceabilityValidator(
            $this->getCheckoutFacade(),
            $this->createRecurringOrderQuoteBuilder(),
            $this->createCheckoutValidationResultBuilder(),
        );
    }

    public function createCheckoutValidationResultBuilder(): CheckoutValidationResultBuilderInterface
    {
        return new CheckoutValidationResultBuilder(
            $this->getConfig(),
        );
    }

    public function createRecurringSchedulePriceValidator(): RecurringSchedulePriceValidatorInterface
    {
        return new RecurringSchedulePriceValidator(
            $this->createRecurringOrderQuoteBuilder(),
            $this->getPriceDriftCheckers(),
        );
    }

    /**
     * @return array<\SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift\PriceDriftCheckerInterface>
     */
    public function getPriceDriftCheckers(): array
    {
        return [
            $this->createItemPriceDriftChecker(),
            $this->createBundlePriceDriftChecker(),
        ];
    }

    public function createItemPriceDriftChecker(): PriceDriftCheckerInterface
    {
        return new ItemPriceDriftChecker($this->createScheduleItemRepricer());
    }

    public function createBundlePriceDriftChecker(): PriceDriftCheckerInterface
    {
        return new BundlePriceDriftChecker($this->createScheduleItemRepricer());
    }

    public function createScheduleItemRepricer(): ScheduleItemRepricerInterface
    {
        return new ScheduleItemRepricer(
            $this->getPriceCartConnectorFacade(),
            $this->getProductPackagingUnitFacade(),
        );
    }

    public function getPriceCartConnectorFacade(): PriceCartConnectorFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_PRICE_CART_CONNECTOR);
    }

    public function getProductPackagingUnitFacade(): ProductPackagingUnitFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_PRODUCT_PACKAGING_UNIT);
    }

    public function getOrderExperienceManagementService(): OrderExperienceManagementServiceInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::SERVICE_ORDER_EXPERIENCE_MANAGEMENT);
    }

    public function createRecurringOrderQuoteBuilder(): RecurringOrderQuoteBuilderInterface
    {
        return new RecurringOrderQuoteBuilder(
            $this->createPlaceableQuoteDeserializer(),
            $this->createPlaceableQuoteItemBuilder(),
        );
    }

    public function createPlaceableQuoteDeserializer(): PlaceableQuoteDeserializerInterface
    {
        return new PlaceableQuoteDeserializer();
    }

    public function createPlaceableQuoteItemBuilder(): PlaceableQuoteItemBuilderInterface
    {
        return new PlaceableQuoteItemBuilder(
            $this->createItemShipmentMethodResolver(),
            $this->createPlaceableItemMapper(),
        );
    }

    public function createItemShipmentMethodResolver(): ItemShipmentMethodResolverInterface
    {
        return new ItemShipmentMethodResolver();
    }

    public function createPlaceableItemMapper(): PlaceableItemMapperInterface
    {
        return new PlaceableItemMapper();
    }

    public function createRecurringOrderPlacer(): RecurringOrderPlacerInterface
    {
        return new RecurringOrderPlacer(
            $this->getRepository(),
            $this->getEntityManager(),
            $this->getCheckoutFacade(),
            $this->createRecurringOrderBuyerMailNotificationSender(),
            $this->createRecurringOrderQuoteBuilder(),
            $this->getCartFacade(),
            $this->getPaymentFacade(),
        );
    }

    public function getCartFacade(): CartFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_CART);
    }

    public function getPaymentFacade(): PaymentFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_PAYMENT);
    }

    public function getStateMachineFacade(): StateMachineFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_STATE_MACHINE);
    }

    public function createRecurringOrderQuoteUpdater(): RecurringOrderQuoteUpdaterInterface
    {
        return new RecurringOrderQuoteUpdater($this->getQuoteFacade());
    }

    public function getCheckoutFacade(): CheckoutFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_CHECKOUT);
    }

    public function getQuoteFacade(): QuoteFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_QUOTE);
    }

    public function createScheduleAdvancer(): ScheduleAdvancerInterface
    {
        return new ScheduleAdvancer(
            $this->getRepository(),
            $this->getEntityManager(),
            $this->createCadenceResolver(),
        );
    }

    public function createScheduleSkipper(): ScheduleSkipperInterface
    {
        return new ScheduleSkipper(
            $this->getRepository(),
            $this->getEntityManager(),
            $this->createScheduleAdvancer(),
        );
    }

    public function createScheduleEventTrigger(): ScheduleEventTriggerInterface
    {
        return new ScheduleEventTrigger(
            $this->getRepository(),
            $this->getStateMachineFacade(),
            $this->getConfig(),
            $this->createAccessibleRecurringScheduleReader(),
        );
    }

    public function createAccessibleRecurringScheduleReader(): AccessibleRecurringScheduleReaderInterface
    {
        return new AccessibleRecurringScheduleReader(
            $this->getRepository(),
            $this->createRecurringScheduleAccessFilter(),
        );
    }

    public function createScheduleReviewBuilder(): ScheduleReviewBuilderInterface
    {
        return new ScheduleReviewBuilder(
            $this->createRecurringScheduleReader(),
            $this->createRecurringSchedulePrePlacementValidator(),
            $this->createScheduleReviewMapper(),
            $this->createConfiguredBundleUnavailabilityExpander(),
            $this->createScheduleReviewSummaryCalculator(),
        );
    }

    public function createScheduleReviewMapper(): ScheduleReviewMapperInterface
    {
        return new ScheduleReviewMapper();
    }

    public function createConfiguredBundleUnavailabilityExpander(): ConfiguredBundleUnavailabilityExpanderInterface
    {
        return new ConfiguredBundleUnavailabilityExpander();
    }

    public function createScheduleReviewSummaryCalculator(): ScheduleReviewSummaryCalculatorInterface
    {
        return new ScheduleReviewSummaryCalculator();
    }

    public function createScheduleReviewApprover(): ScheduleReviewApproverInterface
    {
        return new ScheduleReviewApprover(
            $this->createScheduleReviewBuilder(),
            $this->createScheduleReviewChangeApplier(),
            $this->createScheduleEventTrigger(),
        );
    }

    public function createScheduleReviewChangeApplier(): ScheduleReviewChangeApplierInterface
    {
        return new ScheduleReviewChangeApplier(
            $this->getEntityManager(),
        );
    }

    public function createSmStateStatusResolver(): SmStateStatusResolverInterface
    {
        return new SmStateStatusResolver();
    }

    public function createScheduleStateMachineStateWriter(): ScheduleStateMachineStateWriterInterface
    {
        return new ScheduleStateMachineStateWriter(
            $this->getEntityManager(),
            $this->createSmStateStatusResolver(),
        );
    }

    public function createRecurringScheduleReader(): RecurringScheduleReaderInterface
    {
        return new RecurringScheduleReader(
            $this->getRepository(),
            $this->createRecurringScheduleItemExpander(),
            $this->createRecurringScheduleHistoryExpander(),
            $this->createRecurringScheduleCustomerExpander(),
            $this->createRecurringScheduleItemGrouper(),
            $this->createRecurringScheduleAccessFilter(),
            $this->createRecurringScheduleQuoteDataExpander(),
            $this->createRecurringScheduleSkipPreviewExpander(),
        );
    }

    public function createRecurringScheduleSkipPreviewExpander(): RecurringScheduleSkipPreviewExpanderInterface
    {
        return new RecurringScheduleSkipPreviewExpander($this->createCadenceResolver());
    }

    public function createRecurringScheduleItemGrouper(): RecurringScheduleItemGrouperInterface
    {
        return new RecurringScheduleItemGrouper();
    }

    public function createRecurringScheduleAccessFilter(): RecurringScheduleAccessFilterInterface
    {
        return new RecurringScheduleAccessFilter();
    }

    public function createRecurringScheduleItemExpander(): RecurringScheduleItemExpanderInterface
    {
        return new RecurringScheduleItemExpander($this->getRepository());
    }

    public function createRecurringScheduleHistoryExpander(): RecurringScheduleHistoryExpanderInterface
    {
        return new RecurringScheduleHistoryExpander(
            $this->getRepository(),
            $this->getUtilEncodingService(),
        );
    }

    public function createRecurringScheduleCustomerExpander(): RecurringScheduleCustomerExpanderInterface
    {
        return new RecurringScheduleCustomerExpander($this->getCustomerFacade());
    }

    public function createRecurringScheduleQuoteDataExpander(): RecurringScheduleQuoteDataExpanderInterface
    {
        return new RecurringScheduleQuoteDataExpander($this->getUtilEncodingService());
    }

    public function getCustomerFacade(): CustomerFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_CUSTOMER);
    }

    public function createScheduleResumeWriter(): ScheduleResumeWriterInterface
    {
        return new ScheduleResumeWriter(
            $this->createAccessibleRecurringScheduleReader(),
            $this->getEntityManager(),
            $this->createScheduleEventTrigger(),
        );
    }

    public function createScheduleItemUpdater(): ScheduleItemUpdaterInterface
    {
        return new ScheduleItemUpdater(
            $this->getRepository(),
            $this->getEntityManager(),
        );
    }

    public function createRecurringOrderBuyerMailNotificationSender(): RecurringOrderBuyerMailNotificationSenderInterface
    {
        return new RecurringOrderBuyerMailNotificationSender(
            $this->getRepository(),
            $this->createRecurringScheduleBuyerReader(),
            $this->createNotificationRecipientResolver(),
            $this->createRecurringOrderNotificationMailMapper(),
            $this->getMailFacade(),
        );
    }

    public function createRecurringScheduleBuyerReader(): RecurringScheduleBuyerReaderInterface
    {
        return new RecurringScheduleBuyerReader($this->getCustomerFacade());
    }

    public function createNotificationRecipientResolver(): NotificationRecipientResolverInterface
    {
        return new NotificationRecipientResolver($this->getCompanyUserFacade());
    }

    public function createRecurringOrderNotificationMailMapper(): RecurringOrderNotificationMailMapperInterface
    {
        return new RecurringOrderNotificationMailMapper($this->getConfig());
    }

    public function getCompanyUserFacade(): CompanyUserFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_COMPANY_USER);
    }

    public function getMailFacade(): MailFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_MAIL);
    }
}
