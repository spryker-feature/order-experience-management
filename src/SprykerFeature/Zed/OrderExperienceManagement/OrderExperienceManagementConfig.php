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
     * - Maps each recurring-schedule state machine state name to the public schedule status it represents.
     * - State names not present in the map resolve to `null` (see `SmStateStatusResolver`).
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getSmStateNameToStatusMap(): array
    {
        return [
            SharedOrderExperienceManagementConfig::STATUS_ACTIVE => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
            SharedOrderExperienceManagementConfig::STATUS_PAUSED => SharedOrderExperienceManagementConfig::STATUS_PAUSED,
            SharedOrderExperienceManagementConfig::STATUS_CANCELLED => SharedOrderExperienceManagementConfig::STATUS_CANCELLED,
            SharedOrderExperienceManagementConfig::STATUS_FAILED => SharedOrderExperienceManagementConfig::STATUS_FAILED,
            SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED => SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED,
            static::INITIAL_STATE => SharedOrderExperienceManagementConfig::STATUS_DRAFT,
        ];
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
}
