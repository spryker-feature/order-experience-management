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
     *
     * @var string
     */
    public const string CADENCE_TYPE_WEEKLY = 'weekly';

    /**
     * Specification:
     * - Cadence type that triggers a recurring order every 14 days.
     *
     * @api
     *
     * @var string
     */
    public const string CADENCE_TYPE_BI_WEEKLY = 'bi_weekly';

    /**
     * Specification:
     * - Cadence type that triggers a recurring order once per calendar month.
     *
     * @api
     *
     * @var string
     */
    public const string CADENCE_TYPE_MONTHLY = 'monthly';

    /**
     * Specification:
     * - Cadence type that triggers a recurring order every N weeks.
     * - Requires a positive integer cadence value to define N.
     *
     * @api
     *
     * @var string
     */
    public const string CADENCE_TYPE_EVERY_N_WEEKS = 'every_n_weeks';

    /**
     * Specification:
     * - Schedule status indicating a newly created schedule not yet activated by the buyer.
     *
     * @api
     *
     * @var string
     */
    public const string STATUS_DRAFT = 'draft';

    /**
     * Specification:
     * - Schedule status indicating the schedule is running and will trigger orders on its cadence.
     *
     * @api
     *
     * @var string
     */
    public const string STATUS_ACTIVE = 'active';

    /**
     * Specification:
     * - Schedule status indicating the buyer has temporarily suspended order triggering.
     *
     * @api
     *
     * @var string
     */
    public const string STATUS_PAUSED = 'paused';

    /**
     * Specification:
     * - Schedule status indicating the schedule has been permanently stopped.
     * - Terminal state: no further transitions are possible.
     *
     * @api
     *
     * @var string
     */
    public const string STATUS_CANCELLED = 'cancelled';

    /**
     * Specification:
     * - Schedule status indicating the schedule is on hold pending buyer review.
     * - Entered when a product is discontinued or prices have drifted at pre-placement validation.
     *
     * @api
     *
     * @var string
     */
    public const string STATUS_REVIEW_REQUIRED = 'review_required';

    /**
     * Specification:
     * - Schedule status indicating the last order placement attempt failed.
     * - Retries are attempted up to max_retries times before escalating.
     *
     * @api
     *
     * @var string
     */
    public const string STATUS_FAILED = 'failed';

    /**
     * Specification:
     * - Per-item review reason group indicating the current unit price is higher than the stored reference price.
     * - Surfaced on the Review Required page; only price increases require review.
     *
     * @api
     *
     * @var string
     */
    public const string REVIEW_REASON_GROUP_PRICE_INCREASED = 'price_increased';

    /**
     * Specification:
     * - Per-item review reason group indicating the scheduled product has been discontinued.
     *
     * @api
     *
     * @var string
     */
    public const string REVIEW_REASON_GROUP_DISCONTINUED = 'discontinued';

    /**
     * Specification:
     * - Per-item review reason group indicating the scheduled product has been substituted by another product.
     *
     * @api
     *
     * @var string
     */
    public const string REVIEW_REASON_GROUP_SUBSTITUTED = 'substituted';

    /**
     * Specification:
     * - Per-item review reason group indicating the scheduled product is no longer available for purchase.
     *
     * @api
     *
     * @var string
     */
    public const string REVIEW_REASON_GROUP_UNAVAILABLE = 'unavailable';

    /**
     * Specification:
     * - Per-item review reason group indicating the item is removed because another member of its configurable
     *   bundle is unpurchasable, so the whole bundle is dropped (all-or-nothing).
     *
     * @api
     *
     * @var string
     */
    public const string REVIEW_REASON_GROUP_CONFIGURABLE_BUNDLE_UNAVAILABLE = 'configurable_bundle_unavailable';

    /**
     * Specification:
     * - Per-item review reason group indicating the scheduled product is not approved for purchase.
     *
     * @api
     *
     * @var string
     */
    public const string REVIEW_REASON_GROUP_NOT_APPROVED = 'not_approved';

    /**
     * Specification:
     * - Per-item review reason group indicating no current price could be resolved for the scheduled product.
     *
     * @api
     *
     * @var string
     */
    public const string REVIEW_REASON_GROUP_PRICE_UNAVAILABLE = 'price_unavailable';

    /**
     * Specification:
     * - StateMachine event name that resumes a paused schedule.
     * - Transitions the schedule from paused to active.
     *
     * @api
     *
     * @var string
     */
    public const string SM_EVENT_RESUME = 'resume';

    /**
     * Specification:
     * - StateMachine event name that pauses an active schedule.
     * - Transitions the schedule from active to paused.
     *
     * @api
     *
     * @var string
     */
    public const string SM_EVENT_PAUSE = 'pause';

    /**
     * Specification:
     * - StateMachine event name that skips the next scheduled execution.
     * - Advances the next trigger date by 2× the cadence interval.
     *
     * @api
     *
     * @var string
     */
    public const string SM_EVENT_SKIP = 'skip';

    /**
     * Specification:
     * - StateMachine event name that permanently cancels a schedule.
     * - Terminal transition: the schedule cannot be reactivated after cancellation.
     *
     * @api
     *
     * @var string
     */
    public const string SM_EVENT_CANCEL = 'cancel';

    /**
     * Specification:
     * - StateMachine event name that confirms and releases a schedule from review_required.
     * - May also be fired from pre_trigger_notified to place the order early.
     *
     * @api
     *
     * @var string
     */
    public const string SM_EVENT_CONFIRM = 'confirm';

    /**
     * Specification:
     * - StateMachine event name that retries a failed schedule by moving it to review_required.
     * - Manual transition fired by the buyer from the schedule detail page when the last order attempt failed.
     *
     * @api
     *
     * @var string
     */
    public const string SM_EVENT_RETRY = 'retry';

    /**
     * Specification:
     * - StateMachine event name that activates a schedule from draft state.
     * - Fired automatically by ScheduleWriter after initial schedule creation.
     *
     * @api
     *
     * @var string
     */
    public const string SM_EVENT_ACTIVATE = 'activate';

    /**
     * Specification:
     * - History event type recorded when a recurring order was successfully placed.
     *
     * @api
     *
     * @var string
     */
    public const string HISTORY_EVENT_TYPE_PLACED = 'placed';

    /**
     * Specification:
     * - History event type recorded when a recurring order placement attempt failed.
     *
     * @api
     *
     * @var string
     */
    public const string HISTORY_EVENT_TYPE_FAILED = 'failed';

    /**
     * Specification:
     * - History event type recorded when the buyer skipped the next scheduled execution.
     *
     * @api
     *
     * @var string
     */
    public const string HISTORY_EVENT_TYPE_SKIPPED = 'skipped';

    /**
     * Specification:
     * - History event type recorded when the schedule was paused by the buyer.
     *
     * @api
     *
     * @var string
     */
    public const string HISTORY_EVENT_TYPE_PAUSED = 'paused';

    /**
     * Specification:
     * - History event type recorded when the schedule was resumed from a paused state.
     *
     * @api
     *
     * @var string
     */
    public const string HISTORY_EVENT_TYPE_RESUMED = 'resumed';

    /**
     * Specification:
     * - History event type recorded when the schedule was permanently cancelled.
     *
     * @api
     *
     * @var string
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
}
