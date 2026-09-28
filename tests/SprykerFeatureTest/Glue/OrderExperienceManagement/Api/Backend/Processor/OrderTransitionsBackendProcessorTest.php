<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Glue\OrderExperienceManagement\Api\Backend\Processor;

use ApiPlatform\Metadata\Post;
use Codeception\Test\Unit;
use Generated\Api\Backend\OrderTransitionsBackendResource;
use Generated\Shared\Transfer\OrderItemTransitionOutcomeTransfer;
use Generated\Shared\Transfer\OrderItemTransitionRequestTransfer;
use Generated\Shared\Transfer\OrderItemTransitionResponseTransfer;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Processor\OrderTransitionsBackendProcessor;
use SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Transition\OrderItemTransitionApplier;
use SprykerFeatureTest\Glue\OrderExperienceManagement\OrderExperienceManagementGlueTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group OrderExperienceManagement
 * @group Api
 * @group Backend
 * @group Processor
 * @group OrderTransitionsBackendProcessorTest
 * Add your own group annotations below this line
 */
class OrderTransitionsBackendProcessorTest extends Unit
{
    protected const string ORDER_REFERENCE = 'DE--1234';

    protected const string EVENT = 'pay';

    protected const string ITEM_UUID = '5f6b1d2e-6c1a-4a3b-9d0e-2f7c8b9a1e34';

    protected OrderExperienceManagementGlueTester $tester;

    public function testProcessPostForwardsTheOrderReferenceEventAndItemUuidsToTheFacade(): void
    {
        // Arrange
        $resource = (new OrderTransitionsBackendResource())
            ->setEvent(static::EVENT)
            ->setItemUuids([static::ITEM_UUID]);

        $facadeMock = $this->createMock(OrderExperienceManagementFacadeInterface::class);
        $facadeMock->expects($this->once())
            ->method('applyOrderItemTransition')
            ->with($this->callback(fn (OrderItemTransitionRequestTransfer $requestTransfer): bool => $requestTransfer->getOrderReference() === static::ORDER_REFERENCE
                && $requestTransfer->getEvent() === static::EVENT
                && $requestTransfer->getItemUuids() === [static::ITEM_UUID]))
            ->willReturn($this->createSuccessfulResponse());

        // Act
        $this->createProcessor($facadeMock)->process(
            $resource,
            new Post(class: OrderTransitionsBackendResource::class),
            ['orderReference' => static::ORDER_REFERENCE],
            ['request' => new Request()],
        );
    }

    public function testProcessPostMapsASuccessfulTransitionOntoTheResource(): void
    {
        // Arrange
        $facadeMock = $this->createMock(OrderExperienceManagementFacadeInterface::class);
        $facadeMock->method('applyOrderItemTransition')->willReturn($this->createSuccessfulResponse());

        // Act
        $result = $this->createProcessor($facadeMock)->process(
            (new OrderTransitionsBackendResource())->setEvent(static::EVENT),
            new Post(class: OrderTransitionsBackendResource::class),
            ['orderReference' => static::ORDER_REFERENCE],
            ['request' => new Request()],
        );

        // Assert
        $this->assertInstanceOf(OrderTransitionsBackendResource::class, $result);
        $this->assertSame(static::ORDER_REFERENCE, $result->orderReference);
        $this->assertSame(static::EVENT, $result->event);
        $this->assertCount(1, $result->items);
        $this->assertSame(static::ITEM_UUID, $result->items[0]->getUuid());
        $this->assertSame('payment pending', $result->items[0]->getStateBefore());
        $this->assertSame('paid', $result->items[0]->getState());
    }

    /**
     * The route attribute is the fallback for when API Platform did not resolve the URI variable.
     */
    public function testProcessPostReadsTheOrderReferenceFromTheRequestAttributesWhenNoUriVariableIsGiven(): void
    {
        // Arrange
        $request = new Request();
        $request->attributes->set('orderReference', static::ORDER_REFERENCE);

        $facadeMock = $this->createMock(OrderExperienceManagementFacadeInterface::class);
        $facadeMock->expects($this->once())
            ->method('applyOrderItemTransition')
            ->with($this->callback(fn (OrderItemTransitionRequestTransfer $requestTransfer): bool => $requestTransfer->getOrderReference() === static::ORDER_REFERENCE))
            ->willReturn($this->createSuccessfulResponse());

        // Act
        $this->createProcessor($facadeMock)->process(
            (new OrderTransitionsBackendResource())->setEvent(static::EVENT),
            new Post(class: OrderTransitionsBackendResource::class),
            [],
            ['request' => $request],
        );
    }

    /**
     * @dataProvider provideFailedResultsWithTheirStatus
     */
    public function testProcessPostTranslatesAFailedResultIntoTheMatchingStatus(string $result, int $expectedStatusCode): void
    {
        // Arrange
        $facadeMock = $this->createMock(OrderExperienceManagementFacadeInterface::class);
        $facadeMock->method('applyOrderItemTransition')->willReturn(
            (new OrderItemTransitionResponseTransfer())->setIsSuccessful(false)->setResult($result),
        );

        // Act
        try {
            $this->createProcessor($facadeMock)->process(
                (new OrderTransitionsBackendResource())->setEvent(static::EVENT),
                new Post(class: OrderTransitionsBackendResource::class),
                ['orderReference' => static::ORDER_REFERENCE],
                ['request' => new Request()],
            );
            $this->fail('Expected an HttpException.');
        } catch (HttpException $httpException) {
            // Assert
            $this->assertSame($expectedStatusCode, $httpException->getStatusCode());
        }
    }

    /**
     * @return array<string, array{string, int}>
     */
    public function provideFailedResultsWithTheirStatus(): array
    {
        return [
            'order not found' => [OrderItemTransitionApplier::RESULT_ORDER_NOT_FOUND, 404],
            'locked' => [OrderItemTransitionApplier::RESULT_LOCKED, 409],
            'ineligible' => [OrderItemTransitionApplier::RESULT_INELIGIBLE, 422],
            'unknown items' => [OrderItemTransitionApplier::RESULT_UNKNOWN_ITEMS, 422],
            'internal failure' => [OrderItemTransitionApplier::RESULT_INTERNAL_FAILURE, 500],
            'unmapped result' => ['somethingNew', 500],
        ];
    }

    public function testProcessPostReportsTheFallbackMessageWhenAFailureCarriesNothingToReport(): void
    {
        // Arrange
        $facadeMock = $this->createMock(OrderExperienceManagementFacadeInterface::class);
        $facadeMock->method('applyOrderItemTransition')->willReturn(
            (new OrderItemTransitionResponseTransfer())
                ->setIsSuccessful(false)
                ->setResult(OrderItemTransitionApplier::RESULT_INTERNAL_FAILURE),
        );

        // Assert
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('The transition produced an unrecognised result.');

        // Act
        $this->createProcessor($facadeMock)->process(
            (new OrderTransitionsBackendResource())->setEvent(static::EVENT),
            new Post(class: OrderTransitionsBackendResource::class),
            ['orderReference' => static::ORDER_REFERENCE],
            ['request' => new Request()],
        );
    }

    /**
     * An empty list and an omitted key deserialize to the same `[]`; only the raw body tells them apart.
     */
    public function testProcessPostRejectsAnExplicitlyEmptyItemUuidsListWithoutCallingTheFacade(): void
    {
        // Arrange
        $request = new Request(content: json_encode(['data' => ['attributes' => ['event' => static::EVENT, 'itemUuids' => []]]]) ?: '');

        $facadeMock = $this->createMock(OrderExperienceManagementFacadeInterface::class);
        $facadeMock->expects($this->never())->method('applyOrderItemTransition');

        // Act
        try {
            $this->createProcessor($facadeMock)->process(
                (new OrderTransitionsBackendResource())->setEvent(static::EVENT),
                new Post(class: OrderTransitionsBackendResource::class),
                ['orderReference' => static::ORDER_REFERENCE],
                ['request' => $request],
            );
            $this->fail('Expected an HttpException.');
        } catch (HttpException $httpException) {
            // Assert
            $this->assertSame(422, $httpException->getStatusCode());
        }
    }

    protected function createSuccessfulResponse(): OrderItemTransitionResponseTransfer
    {
        return (new OrderItemTransitionResponseTransfer())
            ->setIsSuccessful(true)
            ->setResult(OrderItemTransitionApplier::RESULT_APPLIED)
            ->setOrderReference(static::ORDER_REFERENCE)
            ->setEvent(static::EVENT)
            ->addItemOutcome(
                (new OrderItemTransitionOutcomeTransfer())
                    ->setUuid(static::ITEM_UUID)
                    ->setOutcome('advanced')
                    ->setStateBefore('payment pending')
                    ->setState('paid'),
            );
    }

    protected function createProcessor(OrderExperienceManagementFacadeInterface $facade): OrderTransitionsBackendProcessor
    {
        return new OrderTransitionsBackendProcessor($facade, $this->tester->createOrderTransitionsResourceMapper());
    }
}
