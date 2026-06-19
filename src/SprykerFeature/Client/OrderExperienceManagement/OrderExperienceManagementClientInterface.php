<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\OrderExperienceManagement;

use Generated\Shared\Transfer\RecurringOrderQuoteUpdateRequestTransfer;
use Generated\Shared\Transfer\RecurringOrderQuoteUpdateResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleStatusCountCollectionTransfer;

interface OrderExperienceManagementClientInterface
{
    /**
     * Specification:
     * - Makes a Zed call to retrieve recurring schedule entities filtered by criteria.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.uuids` to filter by schedule UUIDs.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.idRecurringSchedules` to filter by schedule IDs.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.customerIds` to filter by customer.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.companyIds` to filter by company.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.companyBusinessUnitIds` to filter by company business unit.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.statuses` to filter by schedule status.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.names` to search by schedule name (LIKE).
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.isWithItems` to load schedule items and compute estimated total.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.groupItemsByGroupKey` to group loaded items by their groupKey, summing quantities for items that share the same key; items with a null groupKey are never grouped.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.isWithHistory` to load execution history with order references and failure reasons.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.isWithCustomer` to load the customer full name.
     * - When `RecurringScheduleCriteriaTransfer.customer` is set, derives ownership filters via `PermissionAwareTrait` on the Zed side (company → companyId, business unit → companyBusinessUnitId, fallback → customerId).
     * - Uses `RecurringScheduleCriteriaTransfer.sortCollection.field` to set the order-by field.
     * - Uses `RecurringScheduleCriteriaTransfer.sortCollection.isAscending` to set ascending or descending order.
     * - Applies default sorting by next trigger date ascending when no sort is provided.
     * - Uses `RecurringScheduleCriteriaTransfer.pagination.{limit, offset}` to paginate results with limit and offset.
     * - Uses `RecurringScheduleCriteriaTransfer.pagination.{page, maxPerPage}` to paginate results with page and maxPerPage.
     * - Uses `RecurringScheduleCriteriaTransfer.historyPagination.{page, maxPerPage}` to paginate the per-schedule execution history; metadata is written back to `RecurringSchedule.historyPagination`.
     * - Returns `RecurringScheduleCollectionTransfer` filled with found recurring schedules.
     *
     * @api
     *
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller\GatewayController::getRecurringScheduleCollectionAction()
     */
    public function getRecurringScheduleCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
    ): RecurringScheduleCollectionTransfer;

    /**
     * Specification:
     * - Makes a Zed call to update recurring order settings on the quote.
     * - Requires `RecurringOrderQuoteUpdateRequestTransfer.idQuote`.
     * - Accepts `RecurringOrderQuoteUpdateRequestTransfer.recurringOrderSettings`, pass `null` to clear recurring settings.
     * - Accepts `RecurringOrderQuoteUpdateRequestTransfer.customer` to set the customer when absent on the quote.
     * - Returns the updated quote in the response with `isSuccessful=true` on success.
     * - Returns `isSuccessful=false` with errors when the quote cannot be found or persistence fails.
     *
     * @api
     *
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller\GatewayController::updateRecurringOrderSettingsOnQuoteAction()
     */
    public function updateRecurringOrderSettingsOnQuote(
        RecurringOrderQuoteUpdateRequestTransfer $recurringOrderQuoteUpdateRequestTransfer
    ): RecurringOrderQuoteUpdateResponseTransfer;

    /**
     * Specification:
     * - Makes a Zed call to trigger a manual StateMachine event for a recurring schedule.
     * - Requires `RecurringScheduleEventRequestTransfer.uuid`.
     * - Requires `RecurringScheduleEventRequestTransfer.event`.
     * - Requires `RecurringScheduleEventRequestTransfer.idCustomer`.
     * - Authorizes access using `RecurringScheduleEventRequestTransfer.customer` when provided, granting company users with the `SeeCompanyOrders` or `SeeBusinessUnitOrders` permission access to schedules within their company or business unit; otherwise scopes to `RecurringScheduleEventRequestTransfer.idCustomer` ownership.
     * - Returns `isSuccessful=false` when schedule not found or the customer is not allowed to access it.
     * - Returns `isSuccessful=true` on success.
     *
     * @api
     *
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller\GatewayController::triggerManualEventForScheduleAction()
     */
    public function triggerManualEventForSchedule(
        RecurringScheduleEventRequestTransfer $requestTransfer,
    ): RecurringScheduleEventResponseTransfer;

    /**
     * Specification:
     * - Makes a single Zed call to count recurring schedules grouped by status.
     *
     * @api
     *
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller\GatewayController::getRecurringScheduleStatusCountCollectionAction()
     */
    public function getRecurringScheduleStatusCountCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
    ): RecurringScheduleStatusCountCollectionTransfer;

    /**
     * Specification:
     * - Makes a Zed call to set next_trigger_date on the schedule and fire the `resume` SM event.
     * - Authorizes access using `RecurringScheduleEventRequestTransfer.customer` when provided, granting company users with the `SeeCompanyOrders` or `SeeBusinessUnitOrders` permission access to schedules within their company or business unit; otherwise scopes to `RecurringScheduleEventRequestTransfer.idCustomer` ownership.
     * - Uses `RecurringScheduleEventRequestTransfer.nextExecutionDate` as the new trigger date.
     * - Returns `isSuccessful=false` when schedule not found, not accessible, or not in `paused` state.
     *
     * @api
     *
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller\GatewayController::resumeScheduleWithDateAction()
     */
    public function resumeScheduleWithDate(
        RecurringScheduleEventRequestTransfer $requestTransfer,
    ): RecurringScheduleEventResponseTransfer;

    /**
     * Specification:
     * - Makes a Zed call to update recurring schedule item quantities.
     * - Requires `RecurringScheduleCollectionRequestTransfer.recurringSchedules` to contain exactly one item.
     * - Validates ownership via `RecurringScheduleCollectionRequestTransfer.customer.idCustomer`.
     * - Updates `spy_recurring_schedule_item.quantity` per item in `RecurringScheduleTransfer.items`.
     * - Change applies to next AND all following executions.
     * - Returns errors when schedule not found, not owned, or any item ID is invalid.
     *
     * @api
     *
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller\GatewayController::updateRecurringScheduleCollectionAction()
     */
    public function updateRecurringScheduleCollection(
        RecurringScheduleCollectionRequestTransfer $requestTransfer,
    ): RecurringScheduleCollectionResponseTransfer;

    /**
     * Specification:
     * - Makes a Zed call to build the Review Required view model for a single schedule, scoped by `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.uuids` and `RecurringScheduleCriteriaTransfer.customer` (ownership).
     * - Re-validates the schedule against the current catalogue and prices at read time without persisting anything.
     * - Groups the schedule items into flagged and unchanged sets with the detected per-item reasons, and collects any non-item blocking errors.
     * - Provides the original and updated order totals and the per-reason summary counters shown on the page.
     * - Returns an empty review (`null` recurringSchedule) when no schedule matches the criteria.
     *
     * @api
     *
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller\GatewayController::getRecurringScheduleReviewAction()
     */
    public function getRecurringScheduleReview(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleReviewResponseTransfer;

    /**
     * Specification:
     * - Makes a Zed call to approve the reviewed changes for a schedule (identified by `RecurringScheduleEventRequestTransfer.uuid`) and place the order immediately.
     * - Authorizes access using `RecurringScheduleEventRequestTransfer.customer` when provided, granting company users with the `SeeCompanyOrders` or `SeeBusinessUnitOrders` permission access to schedules within their company or business unit; otherwise scopes to `RecurringScheduleEventRequestTransfer.idCustomer` ownership.
     * - Proceeds only while the schedule awaits review; otherwise returns an unsuccessful response without any changes.
     * - Re-baselines each item to the price the buyer accepted on the page (`RecurringScheduleEventRequestTransfer.acceptedItems`, matched by group key) and re-validates against the live catalogue; returns an unsuccessful response without changes when the live price drifted above the accepted price.
     * - Otherwise applies the changes transactionally — removes unpurchasable items (from this order and future executions) and writes the accepted prices as the new reference price — then fires `confirm` to place the order.
     * - Returns a successful response once the order is placed, otherwise an unsuccessful one.
     *
     * @api
     *
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Communication\Controller\GatewayController::approveScheduleReviewAction()
     */
    public function approveScheduleReview(
        RecurringScheduleEventRequestTransfer $requestTransfer,
    ): RecurringScheduleEventResponseTransfer;
}
