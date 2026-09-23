<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeItemExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakePackagingAmountExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeProductOptionExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeQuoteExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeSalesUnitExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeShipmentExpanderInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\OrderIntakeQuoteAssemblerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\OrderIntakeQuoteBuilder;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\OrderIntakePriceResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeMerchantValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeProductOfferValidatorInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group OrderIntakeQuoteBuilderTest
 * Add your own group annotations below this line
 */
class OrderIntakeQuoteBuilderTest extends Unit
{
    protected const array STEPS_IN_ORDER = [
        'assembleQuote',
        'expandQuote',
        'expandItems',
        'validateProductOffers',
        'validateMerchants',
        'expandSalesUnits',
        'expandProductOptions',
        'resolvePrices',
        'expandPackagingAmounts',
        'expandShipment',
    ];

    /**
     * @var list<string>
     */
    protected array $calls = [];

    /**
     * The order is load-bearing: prices need resolved SKUs, and packaging amounts need prices.
     */
    public function testBuildQuoteRunsEveryStepInOrder(): void
    {
        // Act
        $this->createBuilder()->buildQuote(new OrderIntakeRequestTransfer(), new OrderIntakeResponseTransfer());

        // Assert
        $this->assertSame(static::STEPS_IN_ORDER, $this->calls);
    }

    public function testBuildQuoteHandsEachStepTheQuoteThePreviousStepReturned(): void
    {
        // Act
        $quoteTransfer = $this->createBuilder()->buildQuote(new OrderIntakeRequestTransfer(), new OrderIntakeResponseTransfer());

        // Assert
        $this->assertSame(implode(' > ', static::STEPS_IN_ORDER), $quoteTransfer->getName());
    }

    /**
     * Stopping at the first problem would make the caller fix one issue per round trip.
     */
    public function testBuildQuoteKeepsRunningAfterAStepReportedAnIssue(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = (new OrderIntakeResponseTransfer())
            ->addValidationIssue(new OrderIntakeValidationIssueTransfer());

        // Act
        $this->createBuilder()->buildQuote(new OrderIntakeRequestTransfer(), $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(static::STEPS_IN_ORDER, $this->calls);
    }

    protected function createBuilder(): OrderIntakeQuoteBuilder
    {
        $orderIntakeQuoteAssemblerMock = $this->createMock(OrderIntakeQuoteAssemblerInterface::class);
        $orderIntakeQuoteAssemblerMock->method('assembleQuote')->willReturnCallback(function (): QuoteTransfer {
            $this->calls[] = 'assembleQuote';

            return (new QuoteTransfer())->setName('assembleQuote');
        });

        $orderIntakeQuoteExpanderMock = $this->createMock(OrderIntakeQuoteExpanderInterface::class);
        $orderIntakeQuoteExpanderMock->method('expandQuote')->willReturnCallback($this->recordStep('expandQuote', 1));

        $orderIntakeItemExpanderMock = $this->createMock(OrderIntakeItemExpanderInterface::class);
        $orderIntakeItemExpanderMock->method('expandItems')->willReturnCallback($this->recordStep('expandItems', 0));

        $orderIntakeProductOfferValidatorMock = $this->createMock(OrderIntakeProductOfferValidatorInterface::class);
        $orderIntakeProductOfferValidatorMock->method('validateProductOffers')->willReturnCallback($this->recordStep('validateProductOffers', 0));

        $orderIntakeMerchantValidatorMock = $this->createMock(OrderIntakeMerchantValidatorInterface::class);
        $orderIntakeMerchantValidatorMock->method('validateMerchants')->willReturnCallback($this->recordStep('validateMerchants', 0));

        $orderIntakeSalesUnitExpanderMock = $this->createMock(OrderIntakeSalesUnitExpanderInterface::class);
        $orderIntakeSalesUnitExpanderMock->method('expandSalesUnits')->willReturnCallback($this->recordStep('expandSalesUnits', 1));

        $orderIntakeProductOptionExpanderMock = $this->createMock(OrderIntakeProductOptionExpanderInterface::class);
        $orderIntakeProductOptionExpanderMock->method('expandProductOptions')->willReturnCallback($this->recordStep('expandProductOptions', 0));

        $orderIntakePriceResolverMock = $this->createMock(OrderIntakePriceResolverInterface::class);
        $orderIntakePriceResolverMock->method('resolvePrices')->willReturnCallback($this->recordStep('resolvePrices', 0));

        $orderIntakePackagingAmountExpanderMock = $this->createMock(OrderIntakePackagingAmountExpanderInterface::class);
        $orderIntakePackagingAmountExpanderMock->method('expandPackagingAmounts')->willReturnCallback($this->recordStep('expandPackagingAmounts', 1));

        $orderIntakeShipmentExpanderMock = $this->createMock(OrderIntakeShipmentExpanderInterface::class);
        $orderIntakeShipmentExpanderMock->method('expandShipment')->willReturnCallback($this->recordStep('expandShipment', 1));

        return new OrderIntakeQuoteBuilder(
            $orderIntakeQuoteAssemblerMock,
            $orderIntakeQuoteExpanderMock,
            $orderIntakeItemExpanderMock,
            $orderIntakeProductOfferValidatorMock,
            $orderIntakeMerchantValidatorMock,
            $orderIntakeSalesUnitExpanderMock,
            $orderIntakeProductOptionExpanderMock,
            $orderIntakePriceResolverMock,
            $orderIntakePackagingAmountExpanderMock,
            $orderIntakeShipmentExpanderMock,
        );
    }

    /**
     * Records the step and returns a new quote whose name chains the step onto the quote it received,
     * so the final name proves each step was handed the previous step's result.
     */
    protected function recordStep(string $step, int $quoteArgumentIndex): callable
    {
        return function (mixed ...$arguments) use ($step, $quoteArgumentIndex): QuoteTransfer {
            $this->calls[] = $step;

            /** @var \Generated\Shared\Transfer\QuoteTransfer $receivedQuoteTransfer */
            $receivedQuoteTransfer = $arguments[$quoteArgumentIndex];

            return (new QuoteTransfer())->setName(sprintf('%s > %s', $receivedQuoteTransfer->getName(), $step));
        };
    }
}
