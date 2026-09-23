<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake;

use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeItemExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakePackagingAmountExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeProductOptionExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeQuoteExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeSalesUnitExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeShipmentExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\OrderIntakePriceResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeMerchantValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeProductOfferValidatorInterface;

class OrderIntakeQuoteBuilder implements OrderIntakeQuoteBuilderInterface
{
    public function __construct(
        protected readonly OrderIntakeQuoteAssemblerInterface $orderIntakeQuoteAssembler,
        protected readonly OrderIntakeQuoteExpanderInterface $orderIntakeQuoteExpander,
        protected readonly OrderIntakeItemExpanderInterface $orderIntakeItemExpander,
        protected readonly OrderIntakeProductOfferValidatorInterface $orderIntakeProductOfferValidator,
        protected readonly OrderIntakeMerchantValidatorInterface $orderIntakeMerchantValidator,
        protected readonly OrderIntakeSalesUnitExpanderInterface $orderIntakeSalesUnitExpander,
        protected readonly OrderIntakeProductOptionExpanderInterface $orderIntakeProductOptionExpander,
        protected readonly OrderIntakePriceResolverInterface $orderIntakePriceResolver,
        protected readonly OrderIntakePackagingAmountExpanderInterface $orderIntakePackagingAmountExpander,
        protected readonly OrderIntakeShipmentExpanderInterface $orderIntakeShipmentExpander,
    ) {
    }

    public function buildQuote(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): QuoteTransfer {
        $quoteTransfer = $this->orderIntakeQuoteAssembler->assembleQuote($orderIntakeRequestTransfer);

        $quoteTransfer = $this->orderIntakeQuoteExpander->expandQuote(
            $orderIntakeRequestTransfer,
            $quoteTransfer,
            $orderIntakeResponseTransfer,
        );

        $quoteTransfer = $this->orderIntakeItemExpander->expandItems($quoteTransfer, $orderIntakeResponseTransfer);

        $quoteTransfer = $this->orderIntakeProductOfferValidator->validateProductOffers(
            $quoteTransfer,
            $orderIntakeResponseTransfer,
        );

        $quoteTransfer = $this->orderIntakeMerchantValidator->validateMerchants(
            $quoteTransfer,
            $orderIntakeResponseTransfer,
        );

        $quoteTransfer = $this->orderIntakeSalesUnitExpander->expandSalesUnits(
            $orderIntakeRequestTransfer,
            $quoteTransfer,
            $orderIntakeResponseTransfer,
        );

        $quoteTransfer = $this->orderIntakeProductOptionExpander->expandProductOptions(
            $quoteTransfer,
            $orderIntakeResponseTransfer,
        );

        $quoteTransfer = $this->orderIntakePriceResolver->resolvePrices($quoteTransfer, $orderIntakeResponseTransfer);

        $quoteTransfer = $this->orderIntakePackagingAmountExpander->expandPackagingAmounts(
            $orderIntakeRequestTransfer,
            $quoteTransfer,
            $orderIntakeResponseTransfer,
        );

        return $this->orderIntakeShipmentExpander->expandShipment(
            $orderIntakeRequestTransfer,
            $quoteTransfer,
            $orderIntakeResponseTransfer,
        );
    }
}
