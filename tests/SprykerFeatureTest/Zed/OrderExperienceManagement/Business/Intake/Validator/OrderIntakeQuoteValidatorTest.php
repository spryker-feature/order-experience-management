<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Validator;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeMerchantValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeProductOfferValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeQuoteValidator;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Validator
 * @group OrderIntakeQuoteValidatorTest
 * Add your own group annotations below this line
 */
class OrderIntakeQuoteValidatorTest extends Unit
{
    public function testValidateQuoteValidatesProductOffersBeforeMerchantsAndChainsTheQuote(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $orderIntakeProductOfferValidatorMock = $this->createMock(OrderIntakeProductOfferValidatorInterface::class);
        $orderIntakeProductOfferValidatorMock->expects($this->once())
            ->method('validateProductOffers')
            ->with($this->isInstanceOf(QuoteTransfer::class), $orderIntakeResponseTransfer)
            ->willReturn((new QuoteTransfer())->setName('offers'));

        $orderIntakeMerchantValidatorMock = $this->createMock(OrderIntakeMerchantValidatorInterface::class);
        $orderIntakeMerchantValidatorMock->expects($this->once())
            ->method('validateMerchants')
            ->with($this->callback(fn (QuoteTransfer $quoteTransfer): bool => $quoteTransfer->getName() === 'offers'), $orderIntakeResponseTransfer)
            ->willReturn((new QuoteTransfer())->setName('merchants'));

        // Act
        $quoteTransfer = (new OrderIntakeQuoteValidator($orderIntakeProductOfferValidatorMock, $orderIntakeMerchantValidatorMock))
            ->validateQuote(new QuoteTransfer(), $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame('merchants', $quoteTransfer->getName());
    }
}
