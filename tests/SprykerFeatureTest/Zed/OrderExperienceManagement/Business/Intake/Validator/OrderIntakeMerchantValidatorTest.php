<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Validator;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\MerchantCollectionTransfer;
use Generated\Shared\Transfer\MerchantCriteriaTransfer;
use Generated\Shared\Transfer\MerchantTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Merchant\Business\MerchantFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeMerchantValidator;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Validator
 * @group OrderIntakeMerchantValidatorTest
 * Add your own group annotations below this line
 */
class OrderIntakeMerchantValidatorTest extends Unit
{
    protected const string MERCHANT_REFERENCE = 'MER000001';

    protected const string UNKNOWN_MERCHANT_REFERENCE = 'MER-NOPE';

    protected const string OFFER_REFERENCE = 'offer39';

    /**
     * The gap this closes: the reference is copied verbatim onto the order item, so a typo produced
     * an order attributed to a merchant that does not exist and nothing downstream noticed.
     */
    public function testValidateMerchantsReportsAnUnknownMerchantOnAMerchantOnlyLine(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())->addItem(
            (new ItemTransfer())->setMerchantReference(static::UNKNOWN_MERCHANT_REFERENCE),
        );
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator([])->validateMerchants($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $validationIssueTransfer = $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0);

        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame('items[0].merchantReference', $validationIssueTransfer->getField());
        $this->assertSame(static::UNKNOWN_MERCHANT_REFERENCE, $validationIssueTransfer->getParameters()['%merchantReference%']);
    }

    public function testValidateMerchantsAcceptsAKnownActiveMerchant(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())->addItem(
            (new ItemTransfer())->setMerchantReference(static::MERCHANT_REFERENCE),
        );
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        $this->createValidator([static::MERCHANT_REFERENCE])
            ->validateMerchants($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * The two fields are independent, and an offer already identifies its own merchant —
     * `OrderIntakeProductOfferValidator` owns that line and would report a mismatch itself.
     * Checking it here too would produce two issues for one problem.
     */
    public function testValidateMerchantsSkipsALineThatAlsoNamesAnOffer(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())->addItem(
            (new ItemTransfer())
                ->setMerchantReference(static::UNKNOWN_MERCHANT_REFERENCE)
                ->setProductOfferReference(static::OFFER_REFERENCE),
        );
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $merchantFacadeMock = $this->createMock(MerchantFacadeInterface::class);
        $merchantFacadeMock->expects($this->never())->method('get');

        // Act
        (new OrderIntakeMerchantValidator($merchantFacadeMock))
            ->validateMerchants($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * An operator-sold line names neither field and must not cost a lookup.
     */
    public function testValidateMerchantsDoesNotQueryForAnOperatorSoldLine(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())->addItem(new ItemTransfer());

        $merchantFacadeMock = $this->createMock(MerchantFacadeInterface::class);
        $merchantFacadeMock->expects($this->never())->method('get');

        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        // Act
        (new OrderIntakeMerchantValidator($merchantFacadeMock))
            ->validateMerchants($quoteTransfer, $orderIntakeResponseTransfer);

        // Assert
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * One lookup for the whole quote, restricted to active merchants — an inactive one reports the
     * same way as an unknown one, since neither can fulfil the line.
     */
    public function testValidateMerchantsAsksOnceForTheDistinctActiveReferences(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->addItem((new ItemTransfer())->setMerchantReference(static::MERCHANT_REFERENCE))
            ->addItem((new ItemTransfer())->setMerchantReference(static::MERCHANT_REFERENCE));

        $merchantFacadeMock = $this->createMock(MerchantFacadeInterface::class);
        $merchantFacadeMock->expects($this->once())
            ->method('get')
            ->with($this->callback(function (MerchantCriteriaTransfer $merchantCriteriaTransfer): bool {
                return $merchantCriteriaTransfer->getMerchantReferences() === [static::MERCHANT_REFERENCE]
                    && $merchantCriteriaTransfer->getIsActive() === true;
            }))
            ->willReturn(
                (new MerchantCollectionTransfer())->addMerchants(
                    (new MerchantTransfer())->setMerchantReference(static::MERCHANT_REFERENCE),
                ),
            );

        // Act
        (new OrderIntakeMerchantValidator($merchantFacadeMock))
            ->validateMerchants($quoteTransfer, new OrderIntakeResponseTransfer());

        // Assert
        $this->assertCount(2, $quoteTransfer->getItems());
    }

    /**
     * @param array<string> $activeMerchantReferences
     */
    protected function createValidator(array $activeMerchantReferences): OrderIntakeMerchantValidator
    {
        $merchantCollectionTransfer = new MerchantCollectionTransfer();

        foreach ($activeMerchantReferences as $activeMerchantReference) {
            $merchantCollectionTransfer->addMerchants(
                (new MerchantTransfer())->setMerchantReference($activeMerchantReference),
            );
        }

        $merchantFacadeMock = $this->createMock(MerchantFacadeInterface::class);
        $merchantFacadeMock->method('get')->willReturn($merchantCollectionTransfer);

        return new OrderIntakeMerchantValidator($merchantFacadeMock);
    }
}
