<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement;

use Spryker\Shared\Application\ApplicationConstants;
use Spryker\Zed\Kernel\AbstractBundleConfig;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

/**
 * @method \SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig getSharedConfig()
 */
class OrderExperienceManagementConfig extends AbstractBundleConfig
{
    /**
     * Specification:
     * - Name of the StateMachine process that manages the recurring schedule lifecycle.
     *
     * @api
     *
     * @var string
     */
    public const string STATE_MACHINE_NAME = 'RecurringOrder';

    /**
     * Specification:
     * - Value written to `QuoteTransfer.source` by the order-intake pipeline.
     * - Identifies an API-originated quote to consumers that discriminate by source.
     *
     * @api
     *
     * @uses \Pyz\Zed\QuoteCheckoutConnector\QuoteCheckoutConnectorConfig::getQuoteCheckoutLockExemptSources()
     *
     * @var string
     */
    public const string SOURCE_API = 'api';

    /**
     * Specification:
     * - Returns the locale checkout errors are translated into when the caller resolved none, or
     *   resolved one order intake carries no translations for.
     *
     * @api
     */
    public function getFallbackLocaleName(): string
    {
        return SharedOrderExperienceManagementConfig::LOCALE_NAME_EN_US;
    }

    /**
     * Specification:
     * - Returns the statuses selectable in the Back Office Recurring Order Schedules filter, as [glossary key => status value].
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getBackOfficeFilterStatuses(): array
    {
        return [
            'Active' => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
            'Paused' => SharedOrderExperienceManagementConfig::STATUS_PAUSED,
            'Review required' => SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED,
            'Cancelled' => SharedOrderExperienceManagementConfig::STATUS_CANCELLED,
            'Failed' => SharedOrderExperienceManagementConfig::STATUS_FAILED,
            'Processing' => SharedOrderExperienceManagementConfig::STATUS_PROCESSING,
        ];
    }

    /**
     * Specification:
     * - Returns the cadence types selectable in the Back Office Recurring Order Schedules filter, as [glossary key => cadence value].
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getBackOfficeFilterCadenceTypes(): array
    {
        return [
            'Weekly' => SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            'Bi-weekly' => SharedOrderExperienceManagementConfig::CADENCE_TYPE_BI_WEEKLY,
            'Monthly' => SharedOrderExperienceManagementConfig::CADENCE_TYPE_MONTHLY,
            'Every N weeks' => SharedOrderExperienceManagementConfig::CADENCE_TYPE_EVERY_N_WEEKS,
            'By days' => SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS,
        ];
    }

    /**
     * Specification:
     * - Returns the relative date modifier applied to the reference date to build the forecast period's lower bound.
     * - Shared by both forecast inputs: schedules still due to run and orders already placed.
     * - `FORECAST_PERIOD_FROM_MONTH_START` covers the whole calendar month.
     * - `FORECAST_PERIOD_FROM_TODAY` covers only the remainder of the month.
     *
     * @api
     */
    public function getForecastPeriodFrom(): string
    {
        return static::FORECAST_PERIOD_FROM_MONTH_START;
    }

    /**
     * Specification:
     * - Returns the relative date modifier applied to the reference date to build the forecast period's upper bound.
     * - Shared by both forecast inputs: schedules still due to run and orders already placed.
     * - Defaults to the last day of the current month, so the forecast covers the current calendar month.
     *
     * @api
     */
    public function getForecastPeriodTo(): string
    {
        return static::FORECAST_PERIOD_TO_MONTH_END;
    }

    /**
     * Specification:
     * - Returns the key under which the monthly forecast snapshot is stored and read.
     *
     * @api
     */
    public function getMonthlyForecastKey(): string
    {
        return static::FORECAST_KEY_MONTHLY;
    }

    /**
     * Specification:
     * - Name of the StateMachine process definition file (without .xml extension).
     *
     * @api
     *
     * @var string
     */
    public const string PROCESS_NAME = 'RecurringOrderStateMachine';

    /**
     * Specification:
     * - Initial StateMachine state assigned when a new schedule is registered.
     *
     * @api
     *
     * @var string
     */
    public const string INITIAL_STATE = 'draft';

    /**
     * Specification:
     * - Default number of hours before the next trigger date when the pre-trigger notification is sent.
     * - Per-schedule overrides are stored in spy_recurring_schedule.notification_window_hours.
     *
     * @api
     *
     * @var int
     */
    public const int DEFAULT_NOTIFICATION_WINDOW_HOURS = 48;

    /**
     * Specification:
     * - Forecast period origin (relative date modifier): cover the whole current calendar month.
     *
     * @api
     *
     * @var string
     */
    public const string FORECAST_PERIOD_FROM_MONTH_START = 'first day of this month';

    /**
     * Specification:
     * - Forecast period origin (relative date modifier): cover only the remainder of the current month.
     *
     * @api
     *
     * @var string
     */
    public const string FORECAST_PERIOD_FROM_TODAY = 'today';

    /**
     * Specification:
     * - Forecast period boundary (relative date modifier): last date included in the forecast window.
     *
     * @api
     *
     * @var string
     */
    public const string FORECAST_PERIOD_TO_MONTH_END = 'last day of this month';

    /**
     * Specification:
     * - Identifies the monthly forecast snapshot row persisted in spy_recurring_schedule_forecast.
     * - The scheduled refresh writes to this key; the Back Office reads from it.
     *
     * @api
     *
     * @var string
     */
    public const string FORECAST_KEY_MONTHLY = 'monthly';

    /**
     * @uses \Spryker\Zed\Availability\AvailabilityConfig::ERROR_TYPE_AVAILABILITY
     */
    protected const string CHECKOUT_ERROR_TYPE_AVAILABILITY = 'Availability';

    /**
     * @uses \Spryker\Zed\Merchant\MerchantConfig::CHECKOUT_ERROR_TYPE
     */
    protected const string CHECKOUT_ERROR_TYPE_MERCHANT = 'MerchantUnavailable';

    /**
     * @uses \Spryker\Zed\MerchantProductOption\MerchantProductOptionConfig::CHECKOUT_ERROR_TYPE
     */
    protected const string CHECKOUT_ERROR_TYPE_MERCHANT_PRODUCT_OPTION = 'MerchantProductOptionUnavailable';

    /**
     * @uses \Spryker\Zed\MerchantSwitcher\MerchantSwitcherConfig::CHECKOUT_ERROR_TYPE
     */
    protected const string CHECKOUT_ERROR_TYPE_MERCHANT_SWITCHER = 'MerchantProductUnavailable';

    /**
     * @uses \Spryker\Zed\ProductApproval\ProductApprovalConfig::CHECKOUT_ERROR_TYPE
     */
    protected const string CHECKOUT_ERROR_TYPE_PRODUCT_APPROVAL = 'ProductApprovalUnavailable';

    /**
     * @uses \Spryker\Zed\ProductBundle\ProductBundleConfig::CHECKOUT_ERROR_TYPE
     */
    protected const string CHECKOUT_ERROR_TYPE_PRODUCT_BUNDLE = 'ProductBundleUnavailable';

    /**
     * @uses \Spryker\Zed\ProductCartConnector\ProductCartConnectorConfig::CHECKOUT_ERROR_TYPE
     */
    protected const string CHECKOUT_ERROR_TYPE_PRODUCT_CART_CONNECTOR = 'ProductUnavailable';

    /**
     * @uses \Spryker\Zed\ProductConfigurationCart\ProductConfigurationCartConfig::CHECKOUT_ERROR_TYPE
     */
    protected const string CHECKOUT_ERROR_TYPE_PRODUCT_CONFIGURATION_CART = 'ProductConfigurationCartUnavailable';

    /**
     * @uses \Spryker\Zed\ProductDiscontinued\ProductDiscontinuedConfig::CHECKOUT_ERROR_TYPE
     */
    protected const string CHECKOUT_ERROR_TYPE_PRODUCT_DISCONTINUED = 'ProductDiscontinued';

    /**
     * @uses \Spryker\Zed\ProductOffer\ProductOfferConfig::CHECKOUT_ERROR_TYPE
     */
    protected const string CHECKOUT_ERROR_TYPE_PRODUCT_OFFER = 'ProductOfferUnavailable';

    /**
     * @uses \Spryker\Zed\ProductPackagingUnit\ProductPackagingUnitConfig::CHECKOUT_ERROR_TYPE
     */
    protected const string CHECKOUT_ERROR_TYPE_PRODUCT_PACKAGING_UNIT = 'ProductPackagingUnitUnavailable';

    /**
     * @uses \Spryker\Zed\ProductQuantity\ProductQuantityConfig::CHECKOUT_ERROR_TYPE
     */
    protected const string CHECKOUT_ERROR_TYPE_PRODUCT_QUANTITY = 'ProductQuantityIncorrect';

    protected const string CONFIGURATION_KEY_RECURRING_ORDERS_GENERAL_SCHEDULE_GRACE_PERIOD_HOURS = 'recurring_orders:general:schedule:grace_period_hours';

    protected const string SM_STATE_PRE_TRIGGER_NOTIFIED = 'pre_trigger_notified';

    protected const string SM_STATE_NOTIFYING = 'notifying';

    protected const string SM_STATE_ORDER_PLACED = 'order_placed';

    protected const string SM_STATE_CONFIRMED = 'confirmed';

    protected const string SM_STATE_COMPLETING = 'completing';

    protected const string SM_STATE_SKIPPED = 'skipped';

    protected const string SM_EVENT_NOTIFY_BUYER = 'notify-buyer';

    protected const string SM_EVENT_FINALISE_PLACEMENT = 'finalise-placement';

    /**
     * Specification:
     * - Returns the state machine name used to manage recurring schedule lifecycle.
     *
     * @api
     */
    public function getStateMachineName(): string
    {
        return static::STATE_MACHINE_NAME;
    }

    /**
     * Specification:
     * - Returns the state machine process name for the recurring order process.
     *
     * @api
     */
    public function getProcessName(): string
    {
        return static::PROCESS_NAME;
    }

    /**
     * Specification:
     * - Returns the initial state name when a new recurring schedule is registered with the state machine.
     *
     * @api
     */
    public function getInitialState(): string
    {
        return static::INITIAL_STATE;
    }

    /**
     * Specification:
     * - Returns the initial status assigned to a newly created recurring schedule.
     *
     * @api
     */
    public function getDefaultScheduleStatus(): string
    {
        return $this->getSharedConfig()->getStatusDraft();
    }

    /**
     * Specification:
     * - Returns the default number of hours before the trigger date when a notification is sent.
     * - Value is configurable via Back Office > Configuration > Recurring Orders > General > Schedule Grace Period.
     *
     * @api
     */
    public function getDefaultNotificationWindowHours(): int
    {
        return (int)$this->getModuleConfig(
            static::CONFIGURATION_KEY_RECURRING_ORDERS_GENERAL_SCHEDULE_GRACE_PERIOD_HOURS,
            static::DEFAULT_NOTIFICATION_WINDOW_HOURS,
        );
    }

    /**
     * Specification:
     * - Returns the base URL for the Yves storefront, used to build links in notification emails.
     *
     * @api
     */
    public function getBaseUrlYves(): string
    {
        return $this->get(ApplicationConstants::BASE_URL_YVES);
    }

    /**
     * Specification:
     * - Returns the URL path pattern for the recurring order detail page.
     * - Accepts one sprintf placeholder for the recurring schedule UUID.
     *
     * @api
     */
    public function getRecurringOrderDetailUrlPath(): string
    {
        return '/recurring-orders/%s';
    }

    /**
     * Specification:
     * - Returns the URL path pattern for the recurring order review page.
     * - Accepts one sprintf placeholder for the recurring schedule UUID.
     *
     * @api
     */
    public function getRecurringOrderReviewUrlPath(): string
    {
        return '/recurring-orders/%s/review-required';
    }

    /**
     * Specification:
     * - Returns a map of per-item review reason group to the checkout error types that resolve to it.
     * - The key is a SharedOrderExperienceManagementConfig::REVIEW_REASON_* group; the value is the list of raw checkout
     *   error types reported by the checkout facade that belong to that group.
     * - Used to translate individual checkout error types into the review reason group surfaced to the buyer.
     *
     * @api
     *
     * @return array<string, array<string>>
     */
    public function getReviewReasonGroupMap(): array
    {
        return [
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_OUT_OF_STOCK => [
                static::CHECKOUT_ERROR_TYPE_AVAILABILITY,
            ],
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_UNAVAILABLE => [
                static::CHECKOUT_ERROR_TYPE_MERCHANT,
                static::CHECKOUT_ERROR_TYPE_MERCHANT_PRODUCT_OPTION,
                static::CHECKOUT_ERROR_TYPE_MERCHANT_SWITCHER,
                static::CHECKOUT_ERROR_TYPE_PRODUCT_BUNDLE,
                static::CHECKOUT_ERROR_TYPE_PRODUCT_CART_CONNECTOR,
                static::CHECKOUT_ERROR_TYPE_PRODUCT_CONFIGURATION_CART,
                static::CHECKOUT_ERROR_TYPE_PRODUCT_OFFER,
                static::CHECKOUT_ERROR_TYPE_PRODUCT_PACKAGING_UNIT,
                static::CHECKOUT_ERROR_TYPE_PRODUCT_QUANTITY,
                static::CHECKOUT_ERROR_TYPE_PRODUCT_APPROVAL,
            ],
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_DISCONTINUED => [
                static::CHECKOUT_ERROR_TYPE_PRODUCT_DISCONTINUED,
            ],
        ];
    }

    /**
     * Specification:
     * - Returns the review reason groups that mark a recurring schedule item as non-purchasable.
     * - Items whose reviewReasons contain any of the returned groups will have isPurchasable set to false.
     * - Delegates to getReviewReasonGroupMap() so the error type definitions are managed in one place: every
     *   group in that map is derived from a checkout error that already blocks placement.
     *
     * @api
     *
     * @return array<string>
     */
    public function getNonPurchasableReviewReasonGroups(): array
    {
        return array_keys($this->getReviewReasonGroupMap());
    }

    /**
     * Specification:
     * - Returns the fallback review reason group used when a checkout error type does not match any known group.
     *
     * @api
     */
    public function getDefaultReviewReasonGroup(): string
    {
        return SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_UNAVAILABLE;
    }

    /**
     * Specification:
     * - Maps each stable recurring-schedule state machine state name to the public schedule status it represents.
     * - A stable state is one the schedule rests in and the buyer can act on; every state the buyer can trigger an event from must be listed.
     * - State names not present in the map are intermediate states and resolve to `getTransitionalScheduleStatus()`.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getSmStateNameToStatusMap(): array
    {
        return [
            SharedOrderExperienceManagementConfig::STATUS_ACTIVE => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
            static::SM_STATE_PRE_TRIGGER_NOTIFIED => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
            SharedOrderExperienceManagementConfig::STATUS_PAUSED => SharedOrderExperienceManagementConfig::STATUS_PAUSED,
            SharedOrderExperienceManagementConfig::STATUS_CANCELLED => SharedOrderExperienceManagementConfig::STATUS_CANCELLED,
            SharedOrderExperienceManagementConfig::STATUS_FAILED => SharedOrderExperienceManagementConfig::STATUS_FAILED,
            SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED => SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED,
            static::INITIAL_STATE => SharedOrderExperienceManagementConfig::STATUS_DRAFT,
        ];
    }

    /**
     * Specification:
     * - Returns the schedule status persisted while the schedule passes through a state machine state not listed in `getSmStateNameToStatusMap()`.
     *
     * @api
     */
    public function getTransitionalScheduleStatus(): string
    {
        return SharedOrderExperienceManagementConfig::STATUS_PROCESSING;
    }

    /**
     * Specification:
     * - Returns the delivery-like shipment type keys accepted for products added on the Review Required page.
     *
     * @api
     *
     * @return array<string>
     */
    public function getSupportedAddedItemShipmentTypeKeys(): array
    {
        return $this->getSharedConfig()->getSupportedAddedItemShipmentTypeKeys();
    }

    /**
     * @api
     *
     * @return array<string>
     */
    public function getSubstitutableReviewReasons(): array
    {
        return $this->getSharedConfig()->getSubstitutableReviewReasons();
    }

    /**
     * Specification:
     * - Returns the highest price tolerance in percent a buyer may set, see the shared config.
     *
     * @api
     */
    public function getPriceToleranceMaxPercent(): string
    {
        return $this->getSharedConfig()->getPriceToleranceMaxPercent();
    }

    /**
     * Specification:
     * - Returns the number of decimal places a price tolerance may have, see the shared config.
     *
     * @api
     */
    public function getPriceToleranceScale(): int
    {
        return $this->getSharedConfig()->getPriceToleranceScale();
    }

    /**
     * Specification:
     * - Returns the step a price tolerance moves in, in percent, see the shared config.
     *
     * @api
     */
    public function getPriceToleranceStep(): string
    {
        return $this->getSharedConfig()->getPriceToleranceStep();
    }

    /**
     * @api
     *
     * @return array<string>
     */
    public function getPriceChangeReviewReasons(): array
    {
        return $this->getSharedConfig()->getPriceChangeReviewReasons();
    }

    /**
     * @api
     *
     * @return array<string>
     */
    public function getUnavailableReviewReasons(): array
    {
        return $this->getSharedConfig()->getUnavailableReviewReasons();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isMeasurementUnitProductAdditionRestricted(): bool
    {
        return $this->getSharedConfig()->isMeasurementUnitProductAdditionRestricted();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isPackagingUnitProductAdditionRestricted(): bool
    {
        return $this->getSharedConfig()->isPackagingUnitProductAdditionRestricted();
    }

    /**
     * Specification:
     * - Returns the weekdays selectable for the by-days cadence, in display order.
     *
     * @api
     *
     * @return array<int>
     */
    public function getSupportedWeekDays(): array
    {
        return $this->getSharedConfig()->getSupportedWeekDays();
    }

    /**
     * Specification:
     * - Returns the transient state machine states a schedule can be safely recovered from, mapped to the `onEnter` event to re-trigger.
     * - Contains only states whose event can run twice without duplicating an order or advancing the schedule twice.
     * - `notify-buyer` runs `RecurringOrders/NotifyBuyer`, which sends the upcoming-order email: a run that died after sending it and before saving the state makes the recovery send it a second time. This is accepted, as a duplicate reminder is cheaper than a schedule that never places its order.
     * - `finalise-placement` has no command, so re-triggering it only moves the schedule on.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getRecoverableStuckScheduleEvents(): array
    {
        return [
            static::SM_STATE_NOTIFYING => static::SM_EVENT_NOTIFY_BUYER,
            static::SM_STATE_ORDER_PLACED => static::SM_EVENT_FINALISE_PLACEMENT,
        ];
    }

    /**
     * Specification:
     * - Returns the transient state machine states that are only reported when a schedule is stuck in them.
     * - Their event runs a command that is not idempotent (places an order, advances the trigger date), so re-triggering it could duplicate work.
     *
     * @api
     *
     * @return array<string>
     */
    public function getReportOnlyStuckScheduleStates(): array
    {
        return [
            static::SM_STATE_CONFIRMED,
            static::SM_STATE_COMPLETING,
            static::SM_STATE_SKIPPED,
        ];
    }
}
