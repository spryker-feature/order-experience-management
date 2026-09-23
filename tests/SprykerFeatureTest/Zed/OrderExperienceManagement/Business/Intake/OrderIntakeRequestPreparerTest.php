<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\OrderIntakeRequestPreparer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\OrderIntakeAddressResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\OrderIntakeCustomerResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeLocaleValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeRequestValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator\OrderIntakeStoreValidatorInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group OrderIntakeRequestPreparerTest
 * Add your own group annotations below this line
 */
class OrderIntakeRequestPreparerTest extends Unit
{
    /**
     * @var list<string>
     */
    protected array $calls = [];

    public function testPrepareRequestRunsEveryStepInOrder(): void
    {
        // Act
        $this->createPreparer()->prepareRequest(new OrderIntakeRequestTransfer(), new OrderIntakeResponseTransfer());

        // Assert
        $this->assertSame(
            ['validate', 'validateStore', 'validateLocale', 'resolveCustomer', 'resolveAddresses'],
            $this->calls,
        );
    }

    /**
     * The address resolver reads the customer's saved addresses, so it must receive the request the customer resolver returned.
     */
    public function testPrepareRequestHandsTheResolvedCustomerToTheAddressResolverAndReturnsItsResult(): void
    {
        // Arrange
        $requestWithCustomerTransfer = (new OrderIntakeRequestTransfer())->setCustomer(new CustomerTransfer());
        $requestWithAddressesTransfer = new OrderIntakeRequestTransfer();

        $orderIntakeCustomerResolverMock = $this->createMock(OrderIntakeCustomerResolverInterface::class);
        $orderIntakeCustomerResolverMock->method('resolveCustomer')->willReturn($requestWithCustomerTransfer);

        $orderIntakeAddressResolverMock = $this->createMock(OrderIntakeAddressResolverInterface::class);
        $orderIntakeAddressResolverMock->expects($this->once())
            ->method('resolveAddresses')
            ->with($this->identicalTo($requestWithCustomerTransfer))
            ->willReturn($requestWithAddressesTransfer);

        // Act
        $preparedRequestTransfer = $this->createPreparer($orderIntakeCustomerResolverMock, $orderIntakeAddressResolverMock)
            ->prepareRequest(new OrderIntakeRequestTransfer(), new OrderIntakeResponseTransfer());

        // Assert
        $this->assertSame($requestWithAddressesTransfer, $preparedRequestTransfer);
    }

    /**
     * Stopping at the first problem would make the caller fix one issue per round trip.
     */
    public function testPrepareRequestKeepsRunningAfterAStepReportedAnIssue(): void
    {
        // Arrange
        $orderIntakeResponseTransfer = new OrderIntakeResponseTransfer();

        $orderIntakeRequestValidatorMock = $this->createMock(OrderIntakeRequestValidatorInterface::class);
        $orderIntakeRequestValidatorMock->method('validate')->willReturnCallback(
            function (OrderIntakeRequestTransfer $requestTransfer, OrderIntakeResponseTransfer $responseTransfer): OrderIntakeResponseTransfer {
                return $responseTransfer->addValidationIssue(new OrderIntakeValidationIssueTransfer());
            },
        );

        // Act
        $this->createPreparer(null, null, $orderIntakeRequestValidatorMock)
            ->prepareRequest(new OrderIntakeRequestTransfer(), $orderIntakeResponseTransfer);

        // Assert
        $this->assertSame(['validateStore', 'validateLocale', 'resolveCustomer', 'resolveAddresses'], $this->calls);
        $this->assertCount(1, $orderIntakeResponseTransfer->getValidationIssues());
    }

    protected function createPreparer(
        ?OrderIntakeCustomerResolverInterface $orderIntakeCustomerResolver = null,
        ?OrderIntakeAddressResolverInterface $orderIntakeAddressResolver = null,
        ?OrderIntakeRequestValidatorInterface $orderIntakeRequestValidator = null,
    ): OrderIntakeRequestPreparer {
        if ($orderIntakeRequestValidator === null) {
            $orderIntakeRequestValidator = $this->createMock(OrderIntakeRequestValidatorInterface::class);
            $orderIntakeRequestValidator->method('validate')->willReturnCallback($this->recordCall('validate', 1));
        }

        $orderIntakeStoreValidatorMock = $this->createMock(OrderIntakeStoreValidatorInterface::class);
        $orderIntakeStoreValidatorMock->method('validateStore')->willReturnCallback($this->recordCall('validateStore', 1));

        $orderIntakeLocaleValidatorMock = $this->createMock(OrderIntakeLocaleValidatorInterface::class);
        $orderIntakeLocaleValidatorMock->method('validateLocale')->willReturnCallback($this->recordCall('validateLocale', 1));

        if ($orderIntakeCustomerResolver === null) {
            $orderIntakeCustomerResolver = $this->createMock(OrderIntakeCustomerResolverInterface::class);
            $orderIntakeCustomerResolver->method('resolveCustomer')->willReturnCallback($this->recordCall('resolveCustomer', 0));
        }

        if ($orderIntakeAddressResolver === null) {
            $orderIntakeAddressResolver = $this->createMock(OrderIntakeAddressResolverInterface::class);
            $orderIntakeAddressResolver->method('resolveAddresses')->willReturnCallback($this->recordCall('resolveAddresses', 0));
        }

        return new OrderIntakeRequestPreparer(
            $orderIntakeRequestValidator,
            $orderIntakeStoreValidatorMock,
            $orderIntakeLocaleValidatorMock,
            $orderIntakeCustomerResolver,
            $orderIntakeAddressResolver,
        );
    }

    /**
     * Records the step and returns the argument the real step returns.
     */
    protected function recordCall(string $step, int $returnedArgumentIndex): callable
    {
        return function (mixed ...$arguments) use ($step, $returnedArgumentIndex): mixed {
            $this->calls[] = $step;

            return $arguments[$returnedArgumentIndex];
        };
    }
}
