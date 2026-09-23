<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin;

use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

interface OrderIntakeQuoteExpanderPluginInterface
{
    /**
     * Specification:
     * - Reads the properties the plugin's OWN module contributed to `OrderIntakeRequest` and `Quote`;
     *   OrderExperienceManagement neither declares nor inspects them.
     * - Returns the given quote unchanged when the request carries nothing this plugin owns.
     * - Expands `$quoteTransfer` with whatever the plugin resolved and returns it — the returned quote
     *   is what the next plugin and the rest of the intake pipeline receive.
     * - Adds one `OrderIntakeValidationIssue` to `$orderIntakeResponseTransfer` per problem, each
     *   naming the request field it belongs to, a message, and optionally a machine-readable code.
     *   Issues reject the order before it is priced or placed.
     * - Runs before pricing, calculation and the checkout pre-conditions.
     *
     * @api
     */
    public function expandQuote(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        QuoteTransfer $quoteTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): QuoteTransfer;
}
