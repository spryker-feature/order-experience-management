<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Shared\OrderExperienceManagement;

use Spryker\Shared\Kernel\AbstractSharedConfig;

class OrderExperienceManagementConfig extends AbstractSharedConfig
{
    /**
     * Specification:
     * - Cadence type that triggers a recurring order every 7 days.
     *
     * @api
     */
    public const string CADENCE_TYPE_WEEKLY = 'weekly';

    /**
     * Specification:
     * - Cadence type that triggers a recurring order every 14 days.
     *
     * @api
     */
    public const string CADENCE_TYPE_BI_WEEKLY = 'bi_weekly';

    /**
     * Specification:
     * - Cadence type that triggers a recurring order once per calendar month.
     *
     * @api
     */
    public const string CADENCE_TYPE_MONTHLY = 'monthly';

    /**
     * Specification:
     * - Cadence type that triggers a recurring order every N weeks.
     * - Requires a positive integer cadence value to define N.
     *
     * @api
     */
    public const string CADENCE_TYPE_EVERY_N_WEEKS = 'every_n_weeks';

    /**
     * Specification:
     * - Schedule status indicating a newly created schedule not yet activated by the buyer.
     *
     * @api
     */
    public const string STATUS_DRAFT = 'draft';

    /**
     * Specification:
     * - Schedule status indicating the schedule is running and will trigger orders on its cadence.
     *
     * @api
     */
    public const string STATUS_ACTIVE = 'active';

    /**
     * Specification:
     * - Schedule status indicating the buyer has temporarily suspended order triggering.
     *
     * @api
     */
    public const string STATUS_PAUSED = 'paused';

    /**
     * Specification:
     * - Schedule status indicating the schedule has been permanently stopped.
     * - Terminal state: no further transitions are possible.
     *
     * @api
     */
    public const string STATUS_CANCELLED = 'cancelled';

    /**
     * Specification:
     * - Schedule status indicating the schedule is on hold pending buyer review.
     * - Entered when a product is discontinued or prices have drifted at pre-placement validation.
     *
     * @api
     */
    public const string STATUS_REVIEW_REQUIRED = 'review_required';

    /**
     * Specification:
     * - Schedule status indicating the last order placement attempt failed.
     * - Retries are attempted up to max_retries times before escalating.
     *
     * @api
     */
    public const string STATUS_FAILED = 'failed';

    /**
     * Specification:
     * - Per-item review reason group indicating the current unit price is higher than the stored reference price.
     * - Surfaced on the Review Required page; only price increases require review.
     *
     * @api
     */
    public const string REVIEW_REASON_GROUP_PRICE_INCREASED = 'price_increased';

    /**
     * Specification:
     * - Per-item review reason group indicating the scheduled product has been discontinued.
     *
     * @api
     */
    public const string REVIEW_REASON_GROUP_DISCONTINUED = 'discontinued';

    /**
     * Specification:
     * - Per-item review reason group indicating the scheduled product has been substituted by another product.
     *
     * @api
     */
    public const string REVIEW_REASON_GROUP_SUBSTITUTED = 'substituted';

    /**
     * Specification:
     * - Per-item review reason group indicating the scheduled product is no longer available for purchase.
     *
     * @api
     */
    public const string REVIEW_REASON_GROUP_UNAVAILABLE = 'unavailable';

    /**
     * Specification:
     * - Per-item review reason group indicating the scheduled product is temporarily out of stock (availability check failed).
     *
     * @api
     */
    public const string REVIEW_REASON_GROUP_OUT_OF_STOCK = 'out_of_stock';

    /**
     * Specification:
     * - Review reason groups for which a substitute product can be offered on the Review Required page.
     *
     * @api
     *
     * @var array<string>
     */
    public const array SUBSTITUTABLE_REVIEW_REASON_GROUPS = [
        self::REVIEW_REASON_GROUP_DISCONTINUED,
        self::REVIEW_REASON_GROUP_SUBSTITUTED,
    ];

    /**
     * Specification:
     * - Review reason groups counted as price changes in the Review Required summary.
     *
     * @api
     *
     * @var array<string>
     */
    public const array PRICE_CHANGE_REVIEW_REASON_GROUPS = [
        self::REVIEW_REASON_GROUP_PRICE_INCREASED,
    ];

    /**
     * Specification:
     * - Review reason groups counted as unavailable items in the Review Required summary.
     *
     * @api
     *
     * @var array<string>
     */
    public const array UNAVAILABLE_REVIEW_REASON_GROUPS = [
        self::REVIEW_REASON_GROUP_UNAVAILABLE,
        self::REVIEW_REASON_GROUP_OUT_OF_STOCK,
    ];

    /**
     * Specification:
     * - Per-item review reason group indicating the item is removed because another member of its configurable
     *   bundle is unpurchasable, so the whole bundle is dropped (all-or-nothing).
     *
     * @api
     */
    public const string REVIEW_REASON_GROUP_CONFIGURABLE_BUNDLE_UNAVAILABLE = 'configurable_bundle_unavailable';

    /**
     * Specification:
     * - Per-item review reason group indicating the scheduled product is not approved for purchase.
     *
     * @api
     */
    public const string REVIEW_REASON_GROUP_NOT_APPROVED = 'not_approved';

    /**
     * Specification:
     * - Per-item review reason group indicating no current price could be resolved for the scheduled product.
     *
     * @api
     */
    public const string REVIEW_REASON_GROUP_PRICE_UNAVAILABLE = 'price_unavailable';

    /**
     * Specification:
     * - Machine-readable code for the review blocking error raised when the schedule would be left with no items.
     * - Lets the approval flow recognize and skip this specific blocking error when the buyer is adding items.
     *
     * @api
     */
    public const string REVIEW_ERROR_CODE_EMPTY_ORDER = 'empty_order';

    /**
     * Specification:
     * - Per-item display flag: the item is delivered only in the next order (a "just this order" quantity), not as part of the standing schedule.
     *
     * @api
     */
    public const string ITEM_FLAG_ONE_TIME = 'one_time';

    /**
     * Specification:
     * - Basket-change scope flag: the change applies to all future triggers (the standing schedule).
     *
     * @api
     */
    public const string SCOPE_STANDING = 'standing';

    /**
     * Specification:
     * - Basket-change scope flag: the change applies to the upcoming order only, not the standing schedule.
     *
     * @api
     */
    public const string SCOPE_OCCURRENCE = 'occurrence';

    /**
     * Specification:
     * - StateMachine event name that resumes a paused schedule.
     * - Transitions the schedule from paused to active.
     *
     * @api
     */
    public const string SM_EVENT_RESUME = 'resume';

    /**
     * Specification:
     * - StateMachine event name that pauses an active schedule.
     * - Transitions the schedule from active to paused.
     *
     * @api
     */
    public const string SM_EVENT_PAUSE = 'pause';

    /**
     * Specification:
     * - StateMachine event name that skips the next scheduled execution.
     * - Advances the next trigger date by 2× the cadence interval.
     *
     * @api
     */
    public const string SM_EVENT_SKIP = 'skip';

    /**
     * Specification:
     * - StateMachine event name that permanently cancels a schedule.
     * - Terminal transition: the schedule cannot be reactivated after cancellation.
     *
     * @api
     */
    public const string SM_EVENT_CANCEL = 'cancel';

    /**
     * Specification:
     * - StateMachine event name that confirms and releases a schedule from review_required.
     * - May also be fired from pre_trigger_notified to place the order early.
     *
     * @api
     */
    public const string SM_EVENT_CONFIRM = 'confirm';

    /**
     * Specification:
     * - StateMachine event name that retries a failed schedule by moving it to review_required.
     * - Manual transition fired by the buyer from the schedule detail page when the last order attempt failed.
     *
     * @api
     */
    public const string SM_EVENT_RETRY = 'retry';

    /**
     * Specification:
     * - StateMachine event name that activates a schedule from draft state.
     * - Fired automatically by ScheduleWriter after initial schedule creation.
     *
     * @api
     */
    public const string SM_EVENT_ACTIVATE = 'activate';

    /**
     * Specification:
     * - History event type recorded when a recurring order was successfully placed.
     *
     * @api
     */
    public const string HISTORY_EVENT_TYPE_PLACED = 'placed';

    /**
     * Specification:
     * - History event type recorded when a recurring order placement attempt failed.
     *
     * @api
     */
    public const string HISTORY_EVENT_TYPE_FAILED = 'failed';

    /**
     * Specification:
     * - History event type recorded when the buyer skipped the next scheduled execution.
     *
     * @api
     */
    public const string HISTORY_EVENT_TYPE_SKIPPED = 'skipped';

    /**
     * Specification:
     * - History event type recorded when the schedule was paused by the buyer.
     *
     * @api
     */
    public const string HISTORY_EVENT_TYPE_PAUSED = 'paused';

    /**
     * Specification:
     * - History event type recorded when the schedule was resumed from a paused state.
     *
     * @api
     */
    public const string HISTORY_EVENT_TYPE_RESUMED = 'resumed';

    /**
     * Specification:
     * - History event type recorded when the schedule was permanently cancelled.
     *
     * @api
     */
    public const string HISTORY_EVENT_TYPE_CANCELLED = 'cancelled';

    /**
     * Specification:
     * - Default payment method keys that qualify as invoice-based payment.
     * - Used as the fallback value for getInvoicePaymentMethodKeys().
     *
     * @api
     *
     * @var array<string>
     */
    public const array DEFAULT_INVOICE_PAYMENT_METHOD_KEYS = ['invoice', 'purchaseOnAccount', 'dummyMarketplacePaymentInvoice'];

    /**
     * Specification:
     * - Shipment type key for standard delivery to an address.
     *
     * @api
     *
     * @uses \Spryker\Shared\ShipmentType\ShipmentTypeConfig::SHIPMENT_TYPE_DELIVERY
     */
    public const string SHIPMENT_TYPE_DELIVERY = 'delivery';

    /**
     * Specification:
     * - Shipment type key for on-site service fulfillment (treated as delivery-like).
     *
     * @api
     */
    public const string SHIPMENT_TYPE_ON_SITE_SERVICE = 'on-site-service';

    /**
     * Specification:
     * - Shipment type keys treated as "delivery-like" and supported for products added on the Review Required page.
     * - Listed in preference order; when an offer/store exposes several supported types, the first one is used.
     *
     * @api
     *
     * @var array<string>
     */
    public const array SUPPORTED_ADDED_ITEM_SHIPMENT_TYPE_KEYS = [
        self::SHIPMENT_TYPE_DELIVERY,
        self::SHIPMENT_TYPE_ON_SITE_SERVICE,
    ];

    /**
     * Specification:
     * - Marks a shipping address choice that comes from the addresses stored with the schedule itself.
     * - Such an address has no database identifier, so it is selected by its content key.
     *
     * @api
     */
    public const string SHIPPING_ADDRESS_SOURCE_SCHEDULE = 'schedule';

    /**
     * Specification:
     * - Marks a shipping address choice that comes from the buyer's company business unit addresses.
     *
     * @api
     */
    public const string SHIPPING_ADDRESS_SOURCE_COMPANY_UNIT_ADDRESS = 'company_unit_address';

    /**
     * Specification:
     * - Separates the source from the identifier in a shipping address choice key.
     * - The key is a cross-layer contract: Yves renders it as the option value, Zed authorizes against it.
     *
     * @api
     */
    public const string SHIPPING_ADDRESS_KEY_SEPARATOR = ':';

    protected const bool DEFAULT_MEASUREMENT_UNIT_PRODUCT_ADDITION_RESTRICTED = true;

    protected const bool DEFAULT_PACKAGING_UNIT_PRODUCT_ADDITION_RESTRICTED = true;

    /**
     * Specification:
     * - Returns the status value for a newly created schedule not yet activated.
     *
     * @api
     */
    public function getStatusDraft(): string
    {
        return static::STATUS_DRAFT;
    }

    /**
     * Specification:
     * - Returns the delivery-like shipment type keys supported for products added on the Review Required page.
     * - Listed in preference order (the first available type wins when resolving the shipment method list).
     *
     * @api
     *
     * @return array<string>
     */
    public function getSupportedAddedItemShipmentTypeKeys(): array
    {
        return static::SUPPORTED_ADDED_ITEM_SHIPMENT_TYPE_KEYS;
    }

    /**
     * Specification:
     * - Returns the review reason groups for which a substitute product can be offered on the Review Required page.
     *
     * @api
     *
     * @return array<string>
     */
    public function getSubstitutableReviewReasons(): array
    {
        return static::SUBSTITUTABLE_REVIEW_REASON_GROUPS;
    }

    /**
     * Specification:
     * - Returns the review reason groups counted as price changes in the Review Required summary.
     *
     * @api
     *
     * @return array<string>
     */
    public function getPriceChangeReviewReasons(): array
    {
        return static::PRICE_CHANGE_REVIEW_REASON_GROUPS;
    }

    /**
     * Specification:
     * - Returns the review reason groups counted as unavailable items in the Review Required summary.
     *
     * @api
     *
     * @return array<string>
     */
    public function getUnavailableReviewReasons(): array
    {
        return static::UNAVAILABLE_REVIEW_REASON_GROUPS;
    }

    /**
     * Specification:
     * - Returns the payment method keys that qualify as invoice-based payment.
     * - Only quotes with a matching payment method may generate a recurring schedule.
     *
     * @api
     *
     * @return array<string>
     */
    public function getInvoicePaymentMethodKeys(): array
    {
        return static::DEFAULT_INVOICE_PAYMENT_METHOD_KEYS;
    }

    /**
     * Specification:
     * - Defines whether products sold in measurement units may be added on the Review Required page.
     * - When enabled, such products are hidden from the add-product picker and rejected on approval.
     * - The picker offers no sales unit selector, so a typed quantity would silently mean "N × the store
     *   default sales unit" instead of N base units.
     *
     * @api
     */
    public function isMeasurementUnitProductAdditionRestricted(): bool
    {
        return static::DEFAULT_MEASUREMENT_UNIT_PRODUCT_ADDITION_RESTRICTED;
    }

    /**
     * Specification:
     * - Defines whether products sold in packaging units may be added on the Review Required page.
     * - When enabled, such products are hidden from the add-product picker and rejected on approval.
     * - The picker offers no amount input, so the resolved item would carry no amount, stay unsplit and
     *   reserve no stock for the lead product.
     *
     * @api
     */
    public function isPackagingUnitProductAdditionRestricted(): bool
    {
        return static::DEFAULT_PACKAGING_UNIT_PRODUCT_ADDITION_RESTRICTED;
    }
}
