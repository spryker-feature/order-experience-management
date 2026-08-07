<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business;

use Spryker\Service\Customer\CustomerServiceInterface;
use Spryker\Service\Shipment\ShipmentServiceInterface;
use Spryker\Service\UtilEncoding\UtilEncodingServiceInterface;
use Spryker\Zed\Calculation\Business\CalculationFacadeInterface;
use Spryker\Zed\Cart\Business\CartFacadeInterface;
use Spryker\Zed\Checkout\Business\CheckoutFacadeInterface;
use Spryker\Zed\CompanyUnitAddress\Business\CompanyUnitAddressFacadeInterface;
use Spryker\Zed\CompanyUser\Business\CompanyUserFacadeInterface;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;
use Spryker\Zed\Mail\Business\MailFacadeInterface;
use Spryker\Zed\MerchantProduct\Business\MerchantProductFacadeInterface;
use Spryker\Zed\Messenger\Business\MessengerFacadeInterface;
use Spryker\Zed\Payment\Business\PaymentFacadeInterface;
use Spryker\Zed\PriceCartConnector\Business\PriceCartConnectorFacadeInterface;
use Spryker\Zed\ProductMeasurementUnit\Business\ProductMeasurementUnitFacadeInterface;
use Spryker\Zed\ProductOffer\Business\ProductOfferFacadeInterface;
use Spryker\Zed\ProductPackagingUnit\Business\ProductPackagingUnitFacadeInterface;
use Spryker\Zed\Quote\Business\QuoteFacadeInterface;
use Spryker\Zed\Shipment\Business\ShipmentFacadeInterface;
use Spryker\Zed\StateMachine\Business\StateMachineFacadeInterface;
use SprykerFeature\Service\OrderExperienceManagement\OrderExperienceManagementServiceInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Checker\RecurringOrderPreConditionChecker;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Checker\RecurringOrderPreConditionCheckerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Forecast\MonthlyForecastCalculator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Forecast\MonthlyForecastCalculatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Forecast\MonthlyOccurrenceCounter;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Forecast\MonthlyOccurrenceCounterInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Forecast\RecurringScheduleForecastReader;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Forecast\RecurringScheduleForecastReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Forecast\RecurringScheduleForecastRefresher;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Forecast\RecurringScheduleForecastRefresherInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Mapper\RecurringOrderNotificationMailMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Mapper\RecurringOrderNotificationMailMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Reader\RecurringScheduleBuyerReader;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Reader\RecurringScheduleBuyerReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\RecurringOrderBuyerMailNotificationSender;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\RecurringOrderBuyerMailNotificationSenderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Resolver\NotificationRecipientResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Resolver\NotificationRecipientResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\BundleItemClassifier;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\BundleItemClassifierInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\ItemShipmentMethodResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\ItemShipmentMethodResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\Mapper\PlaceableItemMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\Mapper\PlaceableItemMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteDeserializer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteDeserializerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteItemBuilder;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteItemBuilderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteReloader;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteReloaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteShipmentExpenseBuilder;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteShipmentExpenseBuilderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlacementCheckoutResponseBuilder;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlacementCheckoutResponseBuilderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlacementQuotePreparer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlacementQuotePreparerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\RecurringOrderPlacer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\RecurringOrderPlacerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\RecurringOrderQuoteBuilder;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\RecurringOrderQuoteBuilderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\StoreContextInitializer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\StoreContextInitializerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\UnpurchasableItemChecker;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\UnpurchasableItemCheckerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\RecurringOrder\RecurringOrderQuoteUpdater;
use SprykerFeature\Zed\OrderExperienceManagement\Business\RecurringOrder\RecurringOrderQuoteUpdaterInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Date\FirstTriggerDateResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Date\FirstTriggerDateResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Due\RecurringScheduleDueChecker;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Due\RecurringScheduleDueCheckerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleCustomerExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleExpanderComposite;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleGroupingExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleHistoryExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleItemExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleItemFlagExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleLastExecutionExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleSkipPreviewExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander\RecurringScheduleStatusCountExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Filter\RecurringScheduleAccessFilter;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Filter\RecurringScheduleAccessFilterInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Grouping\RecurringScheduleItemGrouper;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Grouping\RecurringScheduleItemGrouperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\History\RecurringScheduleHistoryFailureReasonEnricher;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\History\RecurringScheduleHistoryFailureReasonEnricherInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\History\RecurringSchedulePlacementHistoryWriter;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\History\RecurringSchedulePlacementHistoryWriterInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Indexer\RecurringScheduleItemIndexer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Indexer\RecurringScheduleItemIndexerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper\RecurringScheduleItemMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper\RecurringScheduleItemMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper\RecurringScheduleMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Mapper\RecurringScheduleMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Reader\RecurringScheduleReader;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Reader\RecurringScheduleReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\ConfiguredBundleUnavailabilityExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\ConfiguredBundleUnavailabilityExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\AcceptedItemReviewMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\AcceptedItemReviewMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\AddedItemCartAdder;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\AddedItemCartAdderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\AddedItemMerchantReferenceResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\AddedItemMerchantReferenceResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\AddedItemResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\AddedItemResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Mapper\AddedItemProductConcreteMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Mapper\AddedItemProductConcreteMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Mapper\AddedItemShippingAddressMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Mapper\AddedItemShippingAddressMapperInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\ScheduleReviewItemAdder;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\ScheduleReviewItemAdderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\ScheduleReviewItemAdditionValidator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\ScheduleReviewItemAdditionValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\AddedItemShipmentMethodResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\AddedItemShipmentMethodResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\AddedItemShipmentResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\AddedItemShipmentResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\AddedItemShippingAddressResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\AddedItemShippingAddressResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\BusinessUnitAddressReader;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\BusinessUnitAddressReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\OfferedShippingAddressChecker;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\OfferedShippingAddressCheckerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\ScheduleAddressReader;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\ScheduleAddressReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\ScheduleShippingAddressChoiceReader;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\ScheduleShippingAddressChoiceReaderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\ShippingAddressChoiceKeyGenerator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\ShippingAddressChoiceKeyGeneratorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\ShippingAddressChoiceMatcher;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Shipment\ShippingAddressChoiceMatcherInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Validator\AddedItemProductMeasurementUnitValidator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Validator\AddedItemProductMeasurementUnitValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Validator\AddedItemProductPackagingUnitValidator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Validator\AddedItemProductPackagingUnitValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Validator\AddedItemProductUnitValidator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Validator\AddedItemProductUnitValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Plan\ScheduleReviewItemUpdatePlanMerger;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Plan\ScheduleReviewItemUpdatePlanMergerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\ScheduleReviewItemRemover;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\ScheduleReviewItemRemoverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\ScheduleReviewPriceApplier;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\ScheduleReviewPriceApplierInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\ScheduleReviewQuantityApplier;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\ScheduleReviewQuantityApplierInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Quote\RecurringScheduleQuoteDataMerger;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Quote\RecurringScheduleQuoteDataMergerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Quote\StandingScheduleQuoteOverrideApplier;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Quote\StandingScheduleQuoteOverrideApplierInterface;
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
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Scope\OccurrenceScheduleReviewScopeStrategy;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Scope\ScheduleReviewScopeStrategyInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Scope\ScheduleReviewScopeStrategyResolver;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Scope\ScheduleReviewScopeStrategyResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Scope\StandingScheduleReviewScopeStrategy;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Validator\AddedItemsApprovalValidator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Validator\BlockingErrorApprovalValidator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Validator\ItemsRemainingApprovalValidator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Validator\PriceDriftApprovalValidator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Validator\QuantityApprovalValidator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Validator\ScheduleApprovalValidatorComposite;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Validator\ScheduleApprovalValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Validator\ScheduleAwaitingReviewApprovalValidator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Validator\ScopeChosenApprovalValidator;
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
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer\ScheduleResumeWriter;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer\ScheduleResumeWriterInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer\ScheduleStateMachineStateWriter;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer\ScheduleStateMachineStateWriterInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer\ScheduleUpdater;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Writer\ScheduleUpdaterInterface;
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
            $this->getLocaleFacade(),
        );
    }

    public function createQuoteSanitizer(): QuoteSanitizerInterface
    {
        return new QuoteSanitizer();
    }

    public function createRecurringScheduleMapper(): RecurringScheduleMapperInterface
    {
        return new RecurringScheduleMapper(
            $this->createCadenceResolver(),
            $this->getUtilEncodingService(),
            $this->getConfig(),
            $this->createFirstTriggerDateResolver(),
        );
    }

    public function createFirstTriggerDateResolver(): FirstTriggerDateResolverInterface
    {
        return new FirstTriggerDateResolver($this->createCadenceResolver());
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

    public function getCustomerService(): CustomerServiceInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::SERVICE_CUSTOMER);
    }

    public function createCadenceResolver(): CadenceResolverInterface
    {
        return new CadenceResolver(
            $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::PLUGINS_CADENCE_TYPE),
        );
    }

    public function createMonthlyForecastCalculator(): MonthlyForecastCalculatorInterface
    {
        return new MonthlyForecastCalculator(
            $this->getRepository(),
            $this->createMonthlyOccurrenceCounter(),
            $this->getConfig(),
        );
    }

    public function createMonthlyOccurrenceCounter(): MonthlyOccurrenceCounterInterface
    {
        return new MonthlyOccurrenceCounter($this->createCadenceResolver());
    }

    public function createRecurringScheduleForecastRefresher(): RecurringScheduleForecastRefresherInterface
    {
        return new RecurringScheduleForecastRefresher(
            $this->createMonthlyForecastCalculator(),
            $this->getEntityManager(),
            $this->getUtilEncodingService(),
            $this->getConfig(),
        );
    }

    public function createRecurringScheduleForecastReader(): RecurringScheduleForecastReaderInterface
    {
        return new RecurringScheduleForecastReader(
            $this->getRepository(),
            $this->getUtilEncodingService(),
            $this->getConfig(),
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
            $this->getRecurringOrderCheckoutValidatorPlugins(),
        );
    }

    /**
     * @return array<\SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\RecurringOrderCheckoutValidatorPluginInterface>
     */
    public function getRecurringOrderCheckoutValidatorPlugins(): array
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::PLUGINS_RECURRING_ORDER_CHECKOUT_VALIDATOR);
    }

    public function createRecurringSchedulePrePlacementValidator(): RecurringSchedulePrePlacementValidatorInterface
    {
        return new RecurringSchedulePrePlacementValidator(
            $this->getRepository(),
            $this->getEntityManager(),
            $this->createRecurringOrderBuyerMailNotificationSender(),
            $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::PLUGINS_SCHEDULE_VALIDATOR),
            $this->createRecurringScheduleValidationResultExpander(),
            $this->createRecurringOrderQuoteBuilder(),
            $this->getCalculationFacade(),
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
            $this->createCheckoutValidationResultBuilder(),
        );
    }

    public function createCheckoutValidationResultBuilder(): CheckoutValidationResultBuilderInterface
    {
        return new CheckoutValidationResultBuilder(
            $this->getConfig(),
            $this->createRecurringScheduleItemIndexer(),
        );
    }

    public function createRecurringSchedulePriceValidator(): RecurringSchedulePriceValidatorInterface
    {
        return new RecurringSchedulePriceValidator(
            $this->createScheduleItemRepricer(),
            $this->createPriceDriftCheckers(),
        );
    }

    /**
     * @return array<\SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\PriceDrift\PriceDriftCheckerInterface>
     */
    public function createPriceDriftCheckers(): array
    {
        return [
            $this->createItemPriceDriftChecker(),
            $this->createBundlePriceDriftChecker(),
        ];
    }

    public function createItemPriceDriftChecker(): PriceDriftCheckerInterface
    {
        return new ItemPriceDriftChecker(
            $this->createRecurringScheduleItemIndexer(),
        );
    }

    public function createBundlePriceDriftChecker(): PriceDriftCheckerInterface
    {
        return new BundlePriceDriftChecker(
            $this->createRecurringScheduleItemIndexer(),
        );
    }

    public function createRecurringScheduleItemIndexer(): RecurringScheduleItemIndexerInterface
    {
        return new RecurringScheduleItemIndexer();
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

    public function getProductOfferFacade(): ProductOfferFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_PRODUCT_OFFER);
    }

    public function getMerchantProductFacade(): MerchantProductFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_MERCHANT_PRODUCT);
    }

    public function getShipmentFacade(): ShipmentFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_SHIPMENT);
    }

    public function getShipmentService(): ShipmentServiceInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::SERVICE_SHIPMENT);
    }

    public function getCompanyUnitAddressFacade(): CompanyUnitAddressFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_COMPANY_UNIT_ADDRESS);
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

    public function createPlaceableQuoteShipmentExpenseBuilder(): PlaceableQuoteShipmentExpenseBuilderInterface
    {
        return new PlaceableQuoteShipmentExpenseBuilder(
            $this->getShipmentService(),
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
            $this->createBundleItemClassifier(),
        );
    }

    public function createItemShipmentMethodResolver(): ItemShipmentMethodResolverInterface
    {
        return new ItemShipmentMethodResolver($this->createBundleItemClassifier());
    }

    public function createBundleItemClassifier(): BundleItemClassifierInterface
    {
        return new BundleItemClassifier();
    }

    public function createPlaceableItemMapper(): PlaceableItemMapperInterface
    {
        return new PlaceableItemMapper();
    }

    public function createRecurringOrderPlacer(): RecurringOrderPlacerInterface
    {
        return new RecurringOrderPlacer(
            $this->getRepository(),
            $this->createRecurringOrderQuoteBuilder(),
            $this->createStoreContextInitializer(),
            $this->createPlaceableQuoteReloader(),
            $this->createPlacementQuotePreparer(),
            $this->createUnpurchasableItemChecker(),
            $this->getCheckoutFacade(),
            $this->createPlacementCheckoutResponseBuilder(),
            $this->createRecurringSchedulePlacementHistoryWriter(),
            $this->createRecurringOrderBuyerMailNotificationSender(),
        );
    }

    public function createStoreContextInitializer(): StoreContextInitializerInterface
    {
        return new StoreContextInitializer();
    }

    public function createPlaceableQuoteReloader(): PlaceableQuoteReloaderInterface
    {
        return new PlaceableQuoteReloader(
            $this->getCartFacade(),
            $this->getMessengerFacade(),
        );
    }

    public function createPlacementQuotePreparer(): PlacementQuotePreparerInterface
    {
        return new PlacementQuotePreparer(
            $this->getPaymentFacade(),
            $this->createPlaceableQuoteShipmentExpenseBuilder(),
        );
    }

    public function createUnpurchasableItemChecker(): UnpurchasableItemCheckerInterface
    {
        return new UnpurchasableItemChecker();
    }

    public function createPlacementCheckoutResponseBuilder(): PlacementCheckoutResponseBuilderInterface
    {
        return new PlacementCheckoutResponseBuilder();
    }

    public function createRecurringSchedulePlacementHistoryWriter(): RecurringSchedulePlacementHistoryWriterInterface
    {
        return new RecurringSchedulePlacementHistoryWriter(
            $this->getEntityManager(),
        );
    }

    public function getMessengerFacade(): MessengerFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_MESSENGER);
    }

    public function getCartFacade(): CartFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_CART);
    }

    public function getCalculationFacade(): CalculationFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_CALCULATION);
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
        return new RecurringOrderQuoteUpdater(
            $this->getQuoteFacade(),
            $this->createCadenceResolver(),
            $this->createFirstTriggerDateResolver(),
        );
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
            $this->createRecurringScheduleReader(),
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
            $this->createAcceptedItemReviewMapper(),
            $this->createRecurringScheduleQuoteDataMerger(),
            $this->createScheduleShippingAddressChoiceReader(),
        );
    }

    public function createScheduleShippingAddressChoiceReader(): ScheduleShippingAddressChoiceReaderInterface
    {
        return new ScheduleShippingAddressChoiceReader(
            $this->createPlaceableQuoteDeserializer(),
            $this->createAddedItemShippingAddressResolver(),
        );
    }

    public function createRecurringScheduleQuoteDataMerger(): RecurringScheduleQuoteDataMergerInterface
    {
        return new RecurringScheduleQuoteDataMerger($this->getUtilEncodingService());
    }

    public function createScheduleReviewMapper(): ScheduleReviewMapperInterface
    {
        return new ScheduleReviewMapper();
    }

    public function createAcceptedItemReviewMapper(): AcceptedItemReviewMapperInterface
    {
        return new AcceptedItemReviewMapper();
    }

    public function createConfiguredBundleUnavailabilityExpander(): ConfiguredBundleUnavailabilityExpanderInterface
    {
        return new ConfiguredBundleUnavailabilityExpander();
    }

    public function createScheduleReviewSummaryCalculator(): ScheduleReviewSummaryCalculatorInterface
    {
        return new ScheduleReviewSummaryCalculator($this->getConfig());
    }

    public function createScheduleReviewApprover(): ScheduleReviewApproverInterface
    {
        return new ScheduleReviewApprover(
            $this->createScheduleReviewBuilder(),
            $this->createScheduleReviewChangeApplier(),
            $this->createScheduleEventTrigger(),
            $this->createScheduleApprovalValidator(),
            $this->createAddedItemResolver(),
            $this->createStandingScheduleQuoteOverrideApplier(),
        );
    }

    public function createStandingScheduleQuoteOverrideApplier(): StandingScheduleQuoteOverrideApplierInterface
    {
        return new StandingScheduleQuoteOverrideApplier(
            $this->getEntityManager(),
            $this->createRecurringScheduleQuoteDataMerger(),
        );
    }

    public function createScheduleApprovalValidator(): ScheduleApprovalValidatorInterface
    {
        return new ScheduleApprovalValidatorComposite([
            // Runs before the scope check so an invalid quantity is reported as such instead of
            // surfacing as "scope required" for a change that would be rejected anyway.
            $this->createQuantityApprovalValidator(),
            $this->createScopeChosenApprovalValidator(),
            $this->createScheduleAwaitingReviewApprovalValidator(),
            $this->createPriceDriftApprovalValidator(),
            $this->createItemsRemainingApprovalValidator(),
            $this->createAddedItemsApprovalValidator(),
            $this->createBlockingErrorApprovalValidator(),
        ]);
    }

    public function createBlockingErrorApprovalValidator(): ScheduleApprovalValidatorInterface
    {
        return new BlockingErrorApprovalValidator();
    }

    public function createQuantityApprovalValidator(): ScheduleApprovalValidatorInterface
    {
        return new QuantityApprovalValidator();
    }

    public function createScopeChosenApprovalValidator(): ScheduleApprovalValidatorInterface
    {
        return new ScopeChosenApprovalValidator();
    }

    public function createScheduleAwaitingReviewApprovalValidator(): ScheduleApprovalValidatorInterface
    {
        return new ScheduleAwaitingReviewApprovalValidator();
    }

    public function createPriceDriftApprovalValidator(): ScheduleApprovalValidatorInterface
    {
        return new PriceDriftApprovalValidator();
    }

    public function createItemsRemainingApprovalValidator(): ScheduleApprovalValidatorInterface
    {
        return new ItemsRemainingApprovalValidator();
    }

    public function createAddedItemsApprovalValidator(): ScheduleApprovalValidatorInterface
    {
        return new AddedItemsApprovalValidator(
            $this->createScheduleReviewItemAdditionValidator(),
        );
    }

    public function createScheduleReviewChangeApplier(): ScheduleReviewChangeApplierInterface
    {
        return new ScheduleReviewChangeApplier(
            $this->createScheduleReviewScopeStrategyResolver(),
            $this->createScheduleReviewItemAdder(),
            $this->createAcceptedItemReviewMapper(),
            $this->getRepository(),
        );
    }

    public function createScheduleReviewScopeStrategyResolver(): ScheduleReviewScopeStrategyResolverInterface
    {
        return new ScheduleReviewScopeStrategyResolver(
            $this->createStandingScheduleReviewScopeStrategy(),
            $this->createOccurrenceScheduleReviewScopeStrategy(),
        );
    }

    public function createStandingScheduleReviewScopeStrategy(): ScheduleReviewScopeStrategyInterface
    {
        return new StandingScheduleReviewScopeStrategy(
            $this->createScheduleReviewItemRemover(),
            $this->createScheduleReviewPriceApplier(),
            $this->createScheduleReviewQuantityApplier(),
            $this->createScheduleReviewItemUpdatePlanMerger(),
            $this->getEntityManager(),
        );
    }

    public function createOccurrenceScheduleReviewScopeStrategy(): ScheduleReviewScopeStrategyInterface
    {
        return new OccurrenceScheduleReviewScopeStrategy(
            $this->createScheduleReviewItemRemover(),
            $this->createScheduleReviewPriceApplier(),
            $this->createScheduleReviewQuantityApplier(),
            $this->createScheduleReviewItemUpdatePlanMerger(),
            $this->getEntityManager(),
        );
    }

    public function createScheduleReviewItemAdder(): ScheduleReviewItemAdderInterface
    {
        return new ScheduleReviewItemAdder(
            $this->createRecurringScheduleItemMapper(),
            $this->getEntityManager(),
        );
    }

    public function createScheduleReviewItemAdditionValidator(): ScheduleReviewItemAdditionValidatorInterface
    {
        return new ScheduleReviewItemAdditionValidator(
            $this->createPlaceableQuoteDeserializer(),
            $this->getCheckoutFacade(),
            $this->createBundleItemClassifier(),
            $this->getCalculationFacade(),
            $this->createAddedItemProductUnitValidator(),
            $this->getAddedItemValidatorPlugins(),
        );
    }

    public function createAddedItemProductUnitValidator(): AddedItemProductUnitValidatorInterface
    {
        return new AddedItemProductUnitValidator(
            $this->createAddedItemProductMeasurementUnitValidator(),
            $this->createAddedItemProductPackagingUnitValidator(),
            $this->getConfig(),
        );
    }

    public function createAddedItemProductMeasurementUnitValidator(): AddedItemProductMeasurementUnitValidatorInterface
    {
        return new AddedItemProductMeasurementUnitValidator(
            $this->getProductMeasurementUnitFacade(),
            $this->createAddedItemProductConcreteMapper(),
        );
    }

    public function createAddedItemProductPackagingUnitValidator(): AddedItemProductPackagingUnitValidatorInterface
    {
        return new AddedItemProductPackagingUnitValidator(
            $this->getProductPackagingUnitFacade(),
            $this->createAddedItemProductConcreteMapper(),
        );
    }

    public function createAddedItemProductConcreteMapper(): AddedItemProductConcreteMapperInterface
    {
        return new AddedItemProductConcreteMapper();
    }

    public function getProductMeasurementUnitFacade(): ProductMeasurementUnitFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_PRODUCT_MEASUREMENT_UNIT);
    }

    /**
     * @return array<\SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\AddedItemValidatorPluginInterface>
     */
    public function getAddedItemValidatorPlugins(): array
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::PLUGINS_ADDED_ITEM_VALIDATOR);
    }

    public function createAddedItemResolver(): AddedItemResolverInterface
    {
        return new AddedItemResolver(
            $this->createPlaceableQuoteDeserializer(),
            $this->createAddedItemMerchantReferenceResolver(),
            $this->createAddedItemCartAdder(),
            $this->createAddedItemShipmentResolver(),
        );
    }

    public function createAddedItemMerchantReferenceResolver(): AddedItemMerchantReferenceResolverInterface
    {
        return new AddedItemMerchantReferenceResolver(
            $this->getProductOfferFacade(),
            $this->getMerchantProductFacade(),
        );
    }

    public function createAddedItemCartAdder(): AddedItemCartAdderInterface
    {
        return new AddedItemCartAdder(
            $this->getCartFacade(),
        );
    }

    public function createAddedItemShipmentResolver(): AddedItemShipmentResolverInterface
    {
        return new AddedItemShipmentResolver(
            $this->createAddedItemShippingAddressResolver(),
            $this->createShippingAddressChoiceMatcher(),
            $this->createAddedItemShipmentMethodResolver(),
        );
    }

    public function createAddedItemShippingAddressResolver(): AddedItemShippingAddressResolverInterface
    {
        return new AddedItemShippingAddressResolver(
            $this->createBusinessUnitAddressReader(),
            $this->createScheduleAddressReader(),
            $this->createShippingAddressChoiceKeyGenerator(),
            $this->createOfferedShippingAddressChecker(),
            $this->createAddedItemShippingAddressMapper(),
        );
    }

    public function createBusinessUnitAddressReader(): BusinessUnitAddressReaderInterface
    {
        return new BusinessUnitAddressReader(
            $this->getCompanyUserFacade(),
            $this->getCompanyUnitAddressFacade(),
            $this->createAddedItemShippingAddressMapper(),
        );
    }

    public function createScheduleAddressReader(): ScheduleAddressReaderInterface
    {
        return new ScheduleAddressReader($this->createAddedItemShippingAddressMapper());
    }

    public function createShippingAddressChoiceKeyGenerator(): ShippingAddressChoiceKeyGeneratorInterface
    {
        return new ShippingAddressChoiceKeyGenerator($this->getCustomerService());
    }

    public function createOfferedShippingAddressChecker(): OfferedShippingAddressCheckerInterface
    {
        return new OfferedShippingAddressChecker();
    }

    public function createShippingAddressChoiceMatcher(): ShippingAddressChoiceMatcherInterface
    {
        return new ShippingAddressChoiceMatcher($this->createShippingAddressChoiceKeyGenerator());
    }

    public function createAddedItemShippingAddressMapper(): AddedItemShippingAddressMapperInterface
    {
        return new AddedItemShippingAddressMapper();
    }

    public function createAddedItemShipmentMethodResolver(): AddedItemShipmentMethodResolverInterface
    {
        return new AddedItemShipmentMethodResolver(
            $this->getShipmentFacade(),
            $this->getShipmentService(),
            $this->getConfig()->getSupportedAddedItemShipmentTypeKeys(),
        );
    }

    public function createScheduleReviewItemRemover(): ScheduleReviewItemRemoverInterface
    {
        return new ScheduleReviewItemRemover($this->getEntityManager());
    }

    public function createScheduleReviewPriceApplier(): ScheduleReviewPriceApplierInterface
    {
        return new ScheduleReviewPriceApplier();
    }

    public function createScheduleReviewQuantityApplier(): ScheduleReviewQuantityApplierInterface
    {
        return new ScheduleReviewQuantityApplier();
    }

    public function createScheduleReviewItemUpdatePlanMerger(): ScheduleReviewItemUpdatePlanMergerInterface
    {
        return new ScheduleReviewItemUpdatePlanMerger();
    }

    public function createSmStateStatusResolver(): SmStateStatusResolverInterface
    {
        return new SmStateStatusResolver($this->getConfig());
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
            $this->createRecurringScheduleAccessFilter(),
            $this->createRecurringScheduleExpander(),
        );
    }

    public function createRecurringScheduleExpander(): RecurringScheduleExpanderInterface
    {
        return new RecurringScheduleExpanderComposite([
            $this->createRecurringScheduleItemExpander(),
            $this->createRecurringScheduleGroupingExpander(),
            $this->createRecurringScheduleItemFlagExpander(),
            $this->createRecurringScheduleHistoryExpander(),
            $this->createRecurringScheduleCustomerExpander(),
            $this->createRecurringScheduleLastExecutionExpander(),
            $this->createRecurringScheduleSkipPreviewExpander(),
            $this->createRecurringScheduleStatusCountExpander(),
        ]);
    }

    public function createRecurringScheduleStatusCountExpander(): RecurringScheduleExpanderInterface
    {
        return new RecurringScheduleStatusCountExpander($this->getRepository());
    }

    public function createRecurringScheduleLastExecutionExpander(): RecurringScheduleExpanderInterface
    {
        return new RecurringScheduleLastExecutionExpander($this->getRepository());
    }

    public function createRecurringScheduleItemFlagExpander(): RecurringScheduleExpanderInterface
    {
        return new RecurringScheduleItemFlagExpander();
    }

    public function createRecurringScheduleSkipPreviewExpander(): RecurringScheduleExpanderInterface
    {
        return new RecurringScheduleSkipPreviewExpander($this->createCadenceResolver());
    }

    public function createRecurringScheduleGroupingExpander(): RecurringScheduleExpanderInterface
    {
        return new RecurringScheduleGroupingExpander(
            $this->createRecurringScheduleItemGrouper(),
        );
    }

    public function createRecurringScheduleItemGrouper(): RecurringScheduleItemGrouperInterface
    {
        return new RecurringScheduleItemGrouper();
    }

    public function createRecurringScheduleAccessFilter(): RecurringScheduleAccessFilterInterface
    {
        return new RecurringScheduleAccessFilter();
    }

    public function createRecurringScheduleItemExpander(): RecurringScheduleExpanderInterface
    {
        return new RecurringScheduleItemExpander($this->getRepository());
    }

    public function createRecurringScheduleHistoryExpander(): RecurringScheduleExpanderInterface
    {
        return new RecurringScheduleHistoryExpander(
            $this->getRepository(),
            $this->createRecurringScheduleHistoryFailureReasonEnricher(),
        );
    }

    public function createRecurringScheduleHistoryFailureReasonEnricher(): RecurringScheduleHistoryFailureReasonEnricherInterface
    {
        return new RecurringScheduleHistoryFailureReasonEnricher(
            $this->getUtilEncodingService(),
        );
    }

    public function createRecurringScheduleDueChecker(): RecurringScheduleDueCheckerInterface
    {
        return new RecurringScheduleDueChecker(
            $this->getRepository(),
            $this->getConfig(),
        );
    }

    public function createRecurringScheduleCustomerExpander(): RecurringScheduleExpanderInterface
    {
        return new RecurringScheduleCustomerExpander($this->getCustomerFacade());
    }

    public function getCustomerFacade(): CustomerFacadeInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::FACADE_CUSTOMER);
    }

    public function createScheduleResumeWriter(): ScheduleResumeWriterInterface
    {
        return new ScheduleResumeWriter(
            $this->createRecurringScheduleReader(),
            $this->getEntityManager(),
            $this->createScheduleEventTrigger(),
        );
    }

    public function createScheduleUpdater(): ScheduleUpdaterInterface
    {
        return new ScheduleUpdater(
            $this->createRecurringScheduleReader(),
            $this->createRecurringScheduleQuoteDataMerger(),
            $this->createRecurringScheduleMapper(),
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
