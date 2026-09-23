<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Glue\OrderExperienceManagement\Api\Backend\Mapper;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Generated\Shared\Transfer\OrderItemTransitionOutcomeTransfer;
use Generated\Shared\Transfer\OrderItemTransitionResponseTransfer;
use SprykerFeatureTest\Glue\OrderExperienceManagement\OrderExperienceManagementGlueTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group OrderExperienceManagement
 * @group Api
 * @group Backend
 * @group Mapper
 * @group OrderTransitionsResourceMapperTest
 * Add your own group annotations below this line
 */
class OrderTransitionsResourceMapperTest extends Unit
{
    protected OrderExperienceManagementGlueTester $tester;

    public function testMapOrderItemTransitionResponseTransferToErrorMessageJoinsEveryReportedReason(): void
    {
        // Arrange
        $responseTransfer = (new OrderItemTransitionResponseTransfer())
            ->addError((new ErrorTransfer())->setMessage('Event "ship" is not legal.'))
            ->setUnknownItemUuids(['uuid-x', 'uuid-y'])
            ->addItemOutcome((new OrderItemTransitionOutcomeTransfer())->setUuid('uuid-1')->setState('new'))
            ->addMessage((new MessageTransfer())->setValue('OMS said no.'));

        // Act
        $errorMessage = $this->tester->createOrderTransitionsResourceMapper()
            ->mapOrderItemTransitionResponseTransferToErrorMessage($responseTransfer);

        // Assert
        $this->assertSame(
            'Event "ship" is not legal. Not items of this order: uuid-x, uuid-y. Item states: uuid-1 (currently new). OMS said no.',
            $errorMessage,
        );
    }

    public function testMapOrderItemTransitionResponseTransferToErrorMessageReturnsNullWhenNothingIsReported(): void
    {
        // Act
        $errorMessage = $this->tester->createOrderTransitionsResourceMapper()
            ->mapOrderItemTransitionResponseTransferToErrorMessage(new OrderItemTransitionResponseTransfer());

        // Assert
        $this->assertNull($errorMessage);
    }

    public function testMapOrderItemTransitionResponseTransferToOrderTransitionsBackendResourceMapsMessages(): void
    {
        // Arrange
        $responseTransfer = (new OrderItemTransitionResponseTransfer())
            ->addMessage((new MessageTransfer())->setType('warning')->setValue('One item was skipped.'));

        // Act
        $resource = $this->tester->createOrderTransitionsResourceMapper()
            ->mapOrderItemTransitionResponseTransferToOrderTransitionsBackendResource($responseTransfer);

        // Assert
        $this->assertCount(1, $resource->messages);
        $this->assertSame('warning', $resource->messages[0]->getType());
        $this->assertSame('One item was skipped.', $resource->messages[0]->getValue());
    }
}
