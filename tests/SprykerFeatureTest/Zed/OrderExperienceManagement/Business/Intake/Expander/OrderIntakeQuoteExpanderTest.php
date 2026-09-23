<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Expander;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Expander\OrderIntakeQuoteExpander;
use SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\OrderIntakeQuoteExpanderPluginInterface;

/**
 * The expander is deliberately domain-free: the properties, their validation and their messages all
 * belong to the plugins. These tests assert only the contract between the two — which is why no
 * budget appears anywhere in them, even though a budget is what the one shipped plugin resolves.
 *
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Expander
 * @group OrderIntakeQuoteExpanderTest
 * Add your own group annotations below this line
 */
class OrderIntakeQuoteExpanderTest extends Unit
{
    protected const string ORDER_CUSTOM_REFERENCE = 'PO-4711';

    protected const string CUSTOMER_REFERENCE = 'DE--1';

    protected const string PRICE_MODE = 'GROSS';

    public function testExpandQuoteReturnsTheQuoteThePluginResolved(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $orderIntakeQuoteExpanderPluginMock = $this->createPlugin(
            static fn (QuoteTransfer $quoteTransfer): QuoteTransfer => $quoteTransfer
                ->setCustomerReference(static::CUSTOMER_REFERENCE),
        );

        // Act
        $quoteTransfer = (new OrderIntakeQuoteExpander([$orderIntakeQuoteExpanderPluginMock]))->expandQuote(
            new OrderIntakeRequestTransfer(),
            new QuoteTransfer(),
            $orderIntakeResponseTransfer,
        );

        // Assert
        $this->assertSame(static::CUSTOMER_REFERENCE, $quoteTransfer->getCustomerReference());
        $this->assertCount(0, $orderIntakeResponseTransfer->getValidationIssues());
    }

    /**
     * The plugin has to see the request to find the properties its own module contributed to it —
     * that is the whole point of the extension point — and the response to report on them.
     */
    public function testExpandQuotePassesTheRequestTheQuoteAndTheResponseToThePlugin(): void
    {
        // Arrange
        $orderIntakeRequestTransfer = (new OrderIntakeRequestTransfer())
            ->setOrderCustomReference(static::ORDER_CUSTOM_REFERENCE);
        $inputQuoteTransfer = new QuoteTransfer();
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $orderIntakeQuoteExpanderPluginMock = $this->createMock(OrderIntakeQuoteExpanderPluginInterface::class);
        $orderIntakeQuoteExpanderPluginMock->expects($this->once())
            ->method('expandQuote')
            ->with($orderIntakeRequestTransfer, $inputQuoteTransfer, $orderIntakeResponseTransfer)
            ->willReturn($inputQuoteTransfer);

        // Act
        (new OrderIntakeQuoteExpander([$orderIntakeQuoteExpanderPluginMock]))->expandQuote(
            $orderIntakeRequestTransfer,
            $inputQuoteTransfer,
            $orderIntakeResponseTransfer,
        );
    }

    /**
     * Plugins own disjoint properties, so an order naming two modules' fields is ordinary rather
     * than a conflict: every plugin runs, and each receives what the previous one produced.
     */
    public function testExpandQuoteRunsEveryPluginAndChainsTheQuoteThroughThem(): void
    {
        // Arrange
        $firstPluginMock = $this->createPlugin(
            static fn (QuoteTransfer $quoteTransfer): QuoteTransfer => $quoteTransfer
                ->setCustomerReference(static::CUSTOMER_REFERENCE),
        );

        $secondPluginMock = $this->createPlugin(
            static fn (QuoteTransfer $quoteTransfer): QuoteTransfer => $quoteTransfer
                ->setPriceMode(static::PRICE_MODE),
        );

        // Act
        $quoteTransfer = (new OrderIntakeQuoteExpander([$firstPluginMock, $secondPluginMock]))->expandQuote(
            new OrderIntakeRequestTransfer(),
            new QuoteTransfer(),
            new OrderIntakeResponseTransfer(),
        );

        // Assert
        $this->assertSame(static::CUSTOMER_REFERENCE, $quoteTransfer->getCustomerReference());
        $this->assertSame(static::PRICE_MODE, $quoteTransfer->getPriceMode());
    }

    /**
     * One call must tell the caller about every problem with the payload, not just the first
     * module's: a reporting plugin must not stop the ones behind it.
     */
    public function testExpandQuoteKeepsRunningPluginsAfterOneReportsAValidationIssue(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $firstPluginMock = $this->createPlugin(
            static function (QuoteTransfer $quoteTransfer, OrderIntakeResponseTransfer $responseTransfer): QuoteTransfer {
                $responseTransfer->addValidationIssue(
                    (new OrderIntakeValidationIssueTransfer())->setField('firstField')->setMessage('First problem.'),
                );

                return $quoteTransfer;
            },
        );

        $secondPluginMock = $this->createPlugin(
            static function (QuoteTransfer $quoteTransfer, OrderIntakeResponseTransfer $responseTransfer): QuoteTransfer {
                $responseTransfer->addValidationIssue(
                    (new OrderIntakeValidationIssueTransfer())->setField('secondField')->setMessage('Second problem.'),
                );

                return $quoteTransfer;
            },
        );

        // Act
        (new OrderIntakeQuoteExpander([$firstPluginMock, $secondPluginMock]))->expandQuote(
            new OrderIntakeRequestTransfer(),
            new QuoteTransfer(),
            $orderIntakeResponseTransfer,
        );

        // Assert
        $this->assertCount(2, $orderIntakeResponseTransfer->getValidationIssues());
        $this->assertSame('firstField', $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(0)->getField());
        $this->assertSame('secondField', $orderIntakeResponseTransfer->getValidationIssues()->offsetGet(1)->getField());
    }

    /**
     * Nothing registered (e.g. no module contributes to the payload) is the default configuration,
     * not an error.
     */
    public function testExpandQuoteReturnsTheQuoteUnchangedWhenNoPluginIsRegistered(): void
    {
        // Arrange
        $inputQuoteTransfer = (new QuoteTransfer())->setCustomerReference(static::CUSTOMER_REFERENCE);

        // Act
        $quoteTransfer = (new OrderIntakeQuoteExpander([]))->expandQuote(
            new OrderIntakeRequestTransfer(),
            $inputQuoteTransfer,
            new OrderIntakeResponseTransfer(),
        );

        // Assert
        $this->assertSame($inputQuoteTransfer, $quoteTransfer);
    }

    /**
     * @param callable(\Generated\Shared\Transfer\QuoteTransfer, \Generated\Shared\Transfer\OrderIntakeResponseTransfer):\Generated\Shared\Transfer\QuoteTransfer $expandQuoteCallback
     */
    protected function createPlugin(callable $expandQuoteCallback): OrderIntakeQuoteExpanderPluginInterface
    {
        $orderIntakeQuoteExpanderPluginMock = $this->createMock(OrderIntakeQuoteExpanderPluginInterface::class);
        $orderIntakeQuoteExpanderPluginMock->method('expandQuote')->willReturnCallback(
            static fn (
                OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
                QuoteTransfer $quoteTransfer,
                OrderIntakeResponseTransfer $orderIntakeResponseTransfer
            ): QuoteTransfer => $expandQuoteCallback($quoteTransfer, $orderIntakeResponseTransfer),
        );

        return $orderIntakeQuoteExpanderPluginMock;
    }
}
