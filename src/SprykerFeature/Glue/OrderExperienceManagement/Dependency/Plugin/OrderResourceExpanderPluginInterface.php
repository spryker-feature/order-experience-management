<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Dependency\Plugin;

use Generated\Api\Backend\OrdersBackendResource;
use Generated\Shared\Transfer\OrderTransfer;

/**
 * The read-side counterpart of {@see \SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\OrderIntakeQuoteExpanderPluginInterface}:
 * a module that contributes its own properties to the `orders` resource schema registers a plugin
 * here to fill them, so OrderExperienceManagement never has to know they exist.
 */
interface OrderResourceExpanderPluginInterface
{
    /**
     * Specification:
     * - Fills the resource properties the plugin's OWN module contributed to the `orders` schema
     *   (via its own `resources/api/backend/orders.resource.yml`); OrderExperienceManagement neither
     *   declares nor reads them.
     * - Leaves a property it owns null when the order carries no value for it, rather than
     *   substituting a default — `skip_null_values` then drops the key from the payload entirely.
     * - Returns the expanded resource; the returned one is what the next plugin and the response
     *   receive.
     * - MUST NOT overwrite a property another module owns, and MUST NOT clear one it does not fill.
     * - Derives everything from `$orderTransfer` alone: a PURE mapping, no I/O, no queries, no
     *   writes. Anything needing a lookup belongs in a Zed `OrderExpanderPluginInterface` plugin,
     *   which has already run by the time the order reaches here.
     * - Runs on every route that reports an order — `GET /orders/{orderReference}`,
     *   `GET /orders` and the `POST /orders` response — and may run more than once per request, so
     *   it has to stay cheap and repeatable.
     *
     * @api
     */
    public function expand(
        OrdersBackendResource $ordersBackendResource,
        OrderTransfer $orderTransfer,
    ): OrdersBackendResource;
}
