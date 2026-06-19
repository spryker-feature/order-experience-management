<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement;

use Spryker\Yves\Kernel\AbstractBundleConfig;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

/**
 * @method \SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig getSharedConfig()
 */
class OrderExperienceManagementConfig extends AbstractBundleConfig
{
    protected const int DEFAULT_RECURRING_SCHEDULE_LIST_ITEMS_PER_PAGE = 10;

    protected const int DEFAULT_RECURRING_SCHEDULE_HISTORY_ITEMS_PER_PAGE = 10;

    /**
     * Specification:
     * - Returns the list of supported cadence types for recurring order scheduling.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getSupportedCadenceTypes(): array
    {
        return [
            'recurring_orders.cadence.weekly' => SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY,
            'recurring_orders.cadence.bi_weekly' => SharedOrderExperienceManagementConfig::CADENCE_TYPE_BI_WEEKLY,
            'recurring_orders.cadence.monthly' => SharedOrderExperienceManagementConfig::CADENCE_TYPE_MONTHLY,
            'recurring_orders.cadence.every_n_weeks' => SharedOrderExperienceManagementConfig::CADENCE_TYPE_EVERY_N_WEEKS,
        ];
    }

    /**
     * Specification:
     * - Returns the cadence type value that requires an additional numeric interval (N weeks).
     *
     * @api
     */
    public function getCadenceTypeEveryNWeeks(): string
    {
        return SharedOrderExperienceManagementConfig::CADENCE_TYPE_EVERY_N_WEEKS;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return array<string>
     */
    public function getInvoicePaymentMethodKeys(): array
    {
        return $this->getSharedConfig()->getInvoicePaymentMethodKeys();
    }

    /**
     * Specification:
     * - Returns the number of recurring schedule items shown per page on the list page.
     *
     * @api
     */
    public function getRecurringScheduleListItemsPerPage(): int
    {
        return static::DEFAULT_RECURRING_SCHEDULE_LIST_ITEMS_PER_PAGE;
    }

    /**
     * Specification:
     * - Returns the number of execution history entries shown per page on the recurring order detail page.
     *
     * @api
     */
    public function getRecurringScheduleHistoryItemsPerPage(): int
    {
        return static::DEFAULT_RECURRING_SCHEDULE_HISTORY_ITEMS_PER_PAGE;
    }

    /**
     * Specification:
     * - Returns a map of history event type → CSS badge modifier class used in the execution history table.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getHistoryEventTypeBadgeClassMap(): array
    {
        return [
            SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_PLACED => 'label--success',
            SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_SKIPPED => 'label--warning',
            SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_FAILED => 'label--danger',
            SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_CANCELLED => 'label--danger',
            SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_PAUSED => 'label--info',
            SharedOrderExperienceManagementConfig::HISTORY_EVENT_TYPE_RESUMED => 'label--info',
        ];
    }

    /**
     * Specification:
     * - Returns a map of schedule status → icon name used on the schedule detail sidebar.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getStatusIconMap(): array
    {
        return [
            SharedOrderExperienceManagementConfig::STATUS_ACTIVE => 'action-success',
            SharedOrderExperienceManagementConfig::STATUS_PAUSED => 'action-warning',
            SharedOrderExperienceManagementConfig::STATUS_CANCELLED => 'cross',
            SharedOrderExperienceManagementConfig::STATUS_FAILED => 'action-warning-triangle',
            SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED => 'action-warning',
        ];
    }

    /**
     * Specification:
     * - Returns a map of per-item review reason → glossary key used as the flag-reason label on the Review Required page.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getReviewReasonLabelMap(): array
    {
        return [
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED => 'recurring_orders.review.reason.price_increased',
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_DISCONTINUED => 'recurring_orders.review.reason.discontinued',
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_SUBSTITUTED => 'recurring_orders.review.reason.substituted',
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_UNAVAILABLE => 'recurring_orders.review.reason.unavailable',
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_CONFIGURABLE_BUNDLE_UNAVAILABLE => 'recurring_orders.review.reason.configurable_bundle_unavailable',
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_NOT_APPROVED => 'recurring_orders.review.reason.not_approved',
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_UNAVAILABLE => 'recurring_orders.review.reason.price_unavailable',
        ];
    }

    /**
     * Specification:
     * - Returns a map of per-item review reason group → CSS badge modifier class used as the flag-reason
     *   badge on the Review Required page.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getReviewReasonBadgeMap(): array
    {
        return [
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED => 'warning',
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_DISCONTINUED => 'alert',
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_SUBSTITUTED => 'info',
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_UNAVAILABLE => 'alert',
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_CONFIGURABLE_BUNDLE_UNAVAILABLE => 'alert',
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_NOT_APPROVED => 'alert',
            SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_UNAVAILABLE => 'alert',
        ];
    }

    /**
     * Specification:
     * - Returns a map of schedule status → CSS badge modifier class used in status badge components.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getStatusBadgeClassMap(): array
    {
        return [
            SharedOrderExperienceManagementConfig::STATUS_ACTIVE => 'success',
            SharedOrderExperienceManagementConfig::STATUS_DRAFT => 'draft',
            SharedOrderExperienceManagementConfig::STATUS_PAUSED => 'warning',
            SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED => 'warning',
            SharedOrderExperienceManagementConfig::STATUS_FAILED => 'alert',
            SharedOrderExperienceManagementConfig::STATUS_CANCELLED => 'cancelled',
        ];
    }

    /**
     * Specification:
     * - Returns status values that cause the error/review banner to be shown on the schedule detail page.
     *
     * @api
     *
     * @return array<string>
     */
    public function getErrorBannerStatuses(): array
    {
        return [
            SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED,
            SharedOrderExperienceManagementConfig::STATUS_FAILED,
        ];
    }

    /**
     * Specification:
     * - Returns status values that require buyer attention and are surfaced in the attention banner.
     *
     * @api
     *
     * @return array<string>
     */
    public function getAttentionBannerStatuses(): array
    {
        return [
            SharedOrderExperienceManagementConfig::STATUS_PAUSED,
            SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED,
            SharedOrderExperienceManagementConfig::STATUS_FAILED,
        ];
    }

    /**
     * Specification:
     * - Returns translation-key → status-value map for the status filter dropdown.
     *
     * @api
     *
     * @return array<string, string>
     */
    public function getRecurringScheduleStatusChoices(): array
    {
        return [
            'recurring_orders.status.draft' => SharedOrderExperienceManagementConfig::STATUS_DRAFT,
            'recurring_orders.status.active' => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
            'recurring_orders.status.paused' => SharedOrderExperienceManagementConfig::STATUS_PAUSED,
            'recurring_orders.status.review_required' => SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED,
            'recurring_orders.status.failed' => SharedOrderExperienceManagementConfig::STATUS_FAILED,
            'recurring_orders.status.cancelled' => SharedOrderExperienceManagementConfig::STATUS_CANCELLED,
        ];
    }
}
