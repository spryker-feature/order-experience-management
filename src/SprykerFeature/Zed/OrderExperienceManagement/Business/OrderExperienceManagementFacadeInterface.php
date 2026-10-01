<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business;

use Generated\Shared\Transfer\ItemCollectionTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderItemTransitionRequestTransfer;
use Generated\Shared\Transfer\OrderItemTransitionResponseTransfer;
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

interface OrderExperienceManagementFacadeInterface
{
    /**
     * Specification:
     * - Retrieves recurring schedule entities filtered by criteria from Persistence.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.uuids` to filter by schedule UUIDs.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.idRecurringSchedules` to filter by schedule IDs.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.customerIds` to filter by customer.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.companyIds` to filter by company.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.companyBusinessUnitIds` to filter by company business unit.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.statuses` to filter by schedule status.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.names` to search by schedule name (LIKE).
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.isWithItems` to load schedule items and compute estimated total.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.isGroupedByGroupKey` to group loaded items by their groupKey, summing quantities for items that share the same key, items with a null groupKey are never grouped.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.isWithHistory` to load execution history with order references and failure reasons.
     * - Uses `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.isWithCustomer` to load the customer full name via CustomerFacade.
     * - When `RecurringScheduleCriteriaTransfer.customer` is set, derives ownership filters via `PermissionAwareTrait`: checks `SeeCompanyOrdersPermissionPlugin` → adds companyId to conditions, then `SeeBusinessUnitOrdersPermissionPlugin` → adds companyBusinessUnitId, otherwise falls back to adding customerId. Omit `customer` only for trusted server-side (back-office) callers that supply their own conditions.
     * - Uses `RecurringScheduleCriteriaTransfer.sortCollection.field` to set the order-by field.
     * - Uses `RecurringScheduleCriteriaTransfer.sortCollection.isAscending` to set ascending or descending order.
     * - Applies default sorting by next trigger date ascending when no sort is provided.
     * - Uses `RecurringScheduleCriteriaTransfer.pagination.{limit, offset}` to paginate results with limit and offset.
     * - Uses `RecurringScheduleCriteriaTransfer.pagination.{page, maxPerPage}` to paginate results with page and maxPerPage.
     * - Uses `RecurringScheduleCriteriaTransfer.statusCountConditions` to additionally return per-status schedule counts in `RecurringScheduleCollectionTransfer.statusCounts`, the counts query is skipped entirely when the property is null.
     * - Applies `statusCountConditions` as an independent filter set: it does not inherit `recurringScheduleConditions`, is neither paginated nor sorted, ignores `estimatedTotalMin`/`estimatedTotalMax`, and MUST NOT set the `isWith*` relation-load flags — `isWithCompany` in particular would add an INNER JOIN on `spy_company` plus a non-aggregated column to the `GROUP BY status` query.
     * - Returns `RecurringScheduleCollectionTransfer` filled with found recurring schedules.
     *
     * @api
     */
    public function getRecurringScheduleCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
    ): RecurringScheduleCollectionTransfer;

    /**
     * Specification:
     * - Returns per-status counts for recurring schedules matching the given criteria.
     * - Supports filtering by `RecurringScheduleConditionsTransfer` (customer, status, etc.).
     *
     * @api
     */
    public function getRecurringScheduleStatusCountCollection(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer
    ): RecurringScheduleStatusCountCollectionTransfer;

    /**
     * Specification:
     * - Requires `RecurringOrderQuoteUpdateRequestTransfer.idQuote`.
     * - Loads the quote identified by `RecurringOrderQuoteUpdateRequestTransfer.idQuote`.
     * - Returns `isSuccessful=false` with an error message when the quote cannot be found.
     * - Returns `isSuccessful=false` with the same error message when the quote already belongs to a customer other
     *   than `RecurringOrderQuoteUpdateRequestTransfer.customer`, without persisting anything.
     * - Sets `RecurringOrderQuoteUpdateRequestTransfer.recurringOrderSettings` on the quote, pass `null` to clear.
     * - Derives `RecurringOrderSettings.firstOrderDate` from `RecurringOrderSettings.startDate`: a future start
     *   date is used as-is, while today advances by one cadence period.
     * - Leaves `RecurringOrderSettings.firstOrderDate` as `null` when the cadence type is missing or unsupported.
     * - Returns `isSuccessful=false` with an error message, without persisting anything, when the cadence type
     *   requires a cadence value and `RecurringOrderSettings.cadenceValue` is missing or less than 1.
     * - Sets `RecurringOrderQuoteUpdateRequestTransfer.customer` on the quote when the quote does not already have one.
     * - Persists the updated quote.
     * - Maps errors from the quote persistence response to `RecurringOrderQuoteUpdateResponseTransfer.errors`.
     * - Returns the updated quote in `RecurringOrderQuoteUpdateResponseTransfer.quote` with `isSuccessful=true` on success.
     * - Returns `isSuccessful=false` with errors when persistence fails.
     *
     * @api
     */
    public function updateRecurringOrderSettingsOnQuote(
        RecurringOrderQuoteUpdateRequestTransfer $recurringOrderQuoteUpdateRequestTransfer
    ): RecurringOrderQuoteUpdateResponseTransfer;

    /**
     * Specification:
     * - Runs the full pre-placement validator plugin stack against the schedule.
     * - When validation failed, sends a notification email to the buyer before returning false.
     * - Used by `IsScheduleValidCondition` to route `validation` → `confirmed` vs `review_required`.
     *
     * @api
     */
    public function isRecurringScheduleValid(int $idRecurringSchedule): bool;

    /**
     * Specification:
     * - Requires `RecurringScheduleEventRequestTransfer.uuid`.
     * - Requires `RecurringScheduleEventRequestTransfer.event`.
     * - Requires `RecurringScheduleEventRequestTransfer.idCustomer`.
     * - Authorizes access through the recurring schedule access filter using `RecurringScheduleEventRequestTransfer.customer` when provided, otherwise scopes to `RecurringScheduleEventRequestTransfer.idCustomer` ownership.
     * - Grants access to a company user holding the `SeeCompanyOrders` or `SeeBusinessUnitOrders` permission for schedules within the respective company or business unit.
     * - Returns `isSuccessful=false` when the schedule is not found or the customer is not allowed to access it.
     * - Fires the given StateMachine event for the schedule.
     * - Returns `isSuccessful=true` on success.
     *
     * @api
     */
    public function triggerManualEventForSchedule(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
    ): RecurringScheduleEventResponseTransfer;

    /**
     * Specification:
     * - Looks up the schedule by `RecurringScheduleEventRequestTransfer.uuid`.
     * - Authorizes access through the recurring schedule access filter using `RecurringScheduleEventRequestTransfer.customer` when provided, granting company users with the `SeeCompanyOrders` or `SeeBusinessUnitOrders` permission access to schedules within their company or business unit, otherwise scopes to `RecurringScheduleEventRequestTransfer.idCustomer` ownership.
     * - Returns `isSuccessful=false` when the schedule is not found or the customer is not allowed to access it.
     * - Validates that the schedule is in `paused` state, returns `isSuccessful=false` otherwise.
     * - Sets `spy_recurring_schedule.next_trigger_date` to `RecurringScheduleEventRequestTransfer.nextExecutionDate`.
     * - Fires the `resume` StateMachine event.
     * - All future execution dates are computed from this new date per cadence.
     *
     * @api
     */
    public function resumeScheduleWithDate(
        RecurringScheduleEventRequestTransfer $requestTransfer,
    ): RecurringScheduleEventResponseTransfer;

    /**
     * Specification:
     * - Updates each persisted schedule in `RecurringScheduleCollectionRequestTransfer.recurringSchedules`, looked up by `RecurringScheduleTransfer.uuid`.
     * - Authorizes access through the recurring schedule access filter using `RecurringScheduleCollectionRequestTransfer.customer`, granting company users with the `SeeCompanyOrders` or `SeeBusinessUnitOrders` permission access to schedules within their company or business unit, otherwise scopes to `RecurringScheduleCollectionRequestTransfer.customer.idCustomer` ownership.
     * - Allowed in any schedule status, does NOT fire a StateMachine event.
     * - Updates only the properties set on each `RecurringScheduleTransfer` (e.g. `name`, `cadenceType`, `cadenceValue`, `nextTriggerDate`) in a single write per schedule.
     * - Merges the `RecurringScheduleTransfer.quote` cost center / budget overrides into `spy_recurring_schedule.quote_data`.
     * - Adds an error to the response for each schedule that is not found or not accessible, identified by `ErrorTransfer.entityIdentifier` holding the requested `RecurringScheduleTransfer.uuid`, and returns the updated schedules.
     *
     * @api
     */
    public function updateRecurringScheduleCollection(
        RecurringScheduleCollectionRequestTransfer $requestTransfer,
    ): RecurringScheduleCollectionResponseTransfer;

    /**
     * Specification:
     * - Builds the Review Required view model for a single schedule, scoped by `RecurringScheduleCriteriaTransfer.recurringScheduleConditions.uuids` and `RecurringScheduleCriteriaTransfer.customer` (ownership).
     * - Re-validates the schedule against the current catalogue and prices at read time without persisting anything.
     * - Runs the re-validation only while the schedule awaits review, any other status returns the schedule without review data.
     * - Groups the schedule items into flagged and unchanged sets with the detected per-item reasons, and collects any non-item blocking errors.
     * - Provides the original and updated order totals and the per-reason summary counters shown on the page.
     * - Returns an empty review (`null` recurringSchedule) when no schedule matches the criteria.
     *
     * @api
     */
    public function getRecurringScheduleReview(
        RecurringScheduleCriteriaTransfer $recurringScheduleCriteriaTransfer,
    ): RecurringScheduleReviewResponseTransfer;

    /**
     * Specification:
     * - Approves the reviewed changes for a schedule identified by `RecurringScheduleEventRequestTransfer.uuid`, then places the order immediately.
     * - Authorizes access through the recurring schedule access filter using `RecurringScheduleEventRequestTransfer.customer` when provided, granting company users with the `SeeCompanyOrders` or `SeeBusinessUnitOrders` permission access to schedules within their company or business unit, otherwise scopes to `RecurringScheduleEventRequestTransfer.idCustomer` ownership.
     * - Proceeds only while the schedule awaits review, otherwise returns an unsuccessful response without any changes.
     * - Re-baselines each item to the price the buyer accepted on the page (`RecurringScheduleEventRequestTransfer.acceptedItems`, matched by group key) and re-validates against the live catalogue.
     * - Returns an unsuccessful response without any changes when any `RecurringScheduleEventRequestTransfer.acceptedItems.acceptedQuantity` or `RecurringScheduleEventRequestTransfer.addedItems.quantity` is below 1, a `null` accepted quantity leaves the line unchanged.
     * - Applies `RecurringScheduleEventRequestTransfer.acceptedItems.acceptedQuantity` as the new standing quantity or as the next delivery quantity only, depending on `RecurringScheduleEventRequestTransfer.scope`.
     * - Returns an unsuccessful response without any changes when the live price drifted above the accepted price, so the buyer can review the new prices.
     * - Otherwise applies the changes in a single transaction: removes unpurchasable items (from this order and future executions) and writes the accepted prices as the new reference price.
     * - Fires the `confirm` StateMachine event to place the order from the updated schedule.
     * - Returns a successful response once the order is placed, otherwise an unsuccessful one.
     *
     * @api
     */
    public function approveScheduleReview(
        RecurringScheduleEventRequestTransfer $requestTransfer,
    ): RecurringScheduleEventResponseTransfer;

    /**
     * Specification:
     * - Creates a Spryker order from an external system's payload without the storefront checkout flow.
     * - Assembles a quote from the payload, runs the checkout pre-conditions, recalculates, and places the order.
     * - Reports rejected input as structured validation issues on the response, does not throw.
     * - Returns the created order reference plus a mapping from each submitted line to its order item.
     *
     * @api
     */
    public function createOrderFromIntake(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer
    ): OrderIntakeResponseTransfer;

    /**
     * Specification:
     * - Resolves the OMS events a client may currently fire on each of the given order items.
     * - Reports only `manual="true"` events: events OMS raises itself when a state is entered are
     *   excluded, because no client can fire one.
     * - Returns event names keyed by idSalesOrderItem, an item with no id, process or state resolves
     *   to an empty set rather than being omitted.
     * - This is the same resolution `applyOrderItemTransition()` admits against, so an event reported
     *   here is never rejected there.
     *
     * @api
     *
     * @return array<int, array<int, string>>
     */
    public function getAvailableOrderItemTransitions(ItemCollectionTransfer $itemCollectionTransfer): array;

    /**
     * Specification:
     * - Resolves the OMS events a client may currently fire on one order item, from its current state.
     * - Reports only `manual="true"` events, see `getAvailableOrderItemTransitions()`.
     *
     * @api
     *
     * @return array<int, string>
     */
    public function getAvailableOrderItemTransitionsForItem(ItemTransfer $itemTransfer): array;

    /**
     * Specification:
     * - Fires one OMS event over a chosen set of a placed order's line items.
     * - Checks the order exists, that every given item uuid belongs to THAT order, and that the event
     *   is currently available for it, before anything side-effecting runs.
     * - Treats an explicitly given `itemUuids` list as an assertion: one ineligible item rejects
     *   the whole request and fires nothing. Treats an omitted list as a scope selector: the eligible
     *   subset is triggered and ineligible items are reported as `skipped`.
     * - Observes the resulting state per item by re-reading it, rather than inferring it from the
     *   event, and reports one outcome per item in scope.
     * - Surfaces an OMS internal failure, a lock conflict, and a trigger that advanced nothing as
     *   distinct `result` values, never reports a bare success for any of them.
     * - Copies OMS command messages onto the response on success as well as failure.
     * - Does not throw for a domain outcome.
     *
     * @api
     */
    public function applyOrderItemTransition(
        OrderItemTransitionRequestTransfer $orderItemTransitionRequestTransfer
    ): OrderItemTransitionResponseTransfer;
}
