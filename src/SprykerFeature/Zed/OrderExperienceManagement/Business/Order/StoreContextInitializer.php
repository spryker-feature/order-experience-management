<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order;

use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Shared\Kernel\Container\GlobalContainer;

class StoreContextInitializer implements StoreContextInitializerInterface
{
    protected const string SERVICE_STORE = 'store';

    public function initialize(QuoteTransfer $quoteTransfer): void
    {
        $storeName = $quoteTransfer->getStore()?->getName();

        if ($storeName === null) {
            return;
        }

        $globalContainer = new GlobalContainer();

        if ($globalContainer->has(static::SERVICE_STORE)) {
            return;
        }

        $globalContainer->getContainer()->set(static::SERVICE_STORE, function () use ($storeName) {
            return $storeName;
        });
    }
}
