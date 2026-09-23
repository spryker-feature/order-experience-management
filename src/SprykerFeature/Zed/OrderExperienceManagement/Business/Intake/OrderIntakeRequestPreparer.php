<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake;

use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\OrderIntakeAddressResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\OrderIntakeCustomerResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeLocaleValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeRequestValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeStoreValidatorInterface;

class OrderIntakeRequestPreparer implements OrderIntakeRequestPreparerInterface
{
    public function __construct(
        protected readonly OrderIntakeRequestValidatorInterface $orderIntakeRequestValidator,
        protected readonly OrderIntakeStoreValidatorInterface $orderIntakeStoreValidator,
        protected readonly OrderIntakeLocaleValidatorInterface $orderIntakeLocaleValidator,
        protected readonly OrderIntakeCustomerResolverInterface $orderIntakeCustomerResolver,
        protected readonly OrderIntakeAddressResolverInterface $orderIntakeAddressResolver,
    ) {
    }

    public function prepareRequest(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): OrderIntakeRequestTransfer {
        $this->orderIntakeRequestValidator->validate($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        $this->orderIntakeStoreValidator->validateStore($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        $this->orderIntakeLocaleValidator->validateLocale($orderIntakeRequestTransfer, $orderIntakeResponseTransfer);

        $orderIntakeRequestTransfer = $this->orderIntakeCustomerResolver->resolveCustomer(
            $orderIntakeRequestTransfer,
            $orderIntakeResponseTransfer,
        );

        return $this->orderIntakeAddressResolver->resolveAddresses(
            $orderIntakeRequestTransfer,
            $orderIntakeResponseTransfer,
        );
    }
}
