<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleCollectionRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleItemQuery;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Facade
 * @group UpdateRecurringScheduleCollectionTest
 */
class UpdateRecurringScheduleCollectionTest extends Unit
{
    protected const int INITIAL_QUANTITY = 1;

    protected const int UPDATED_QUANTITY = 5;

    protected OrderExperienceManagementBusinessTester $tester;

    public function testUpdatesItemQuantityForOwnedScheduleAndReturnsSchedule(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idCustomer);
        $recurringScheduleItemTransfer = $this->tester->haveRecurringScheduleItem(
            $recurringScheduleTransfer->getIdRecurringScheduleOrFail(),
            [RecurringScheduleItemTransfer::QUANTITY => static::INITIAL_QUANTITY],
        );

        $requestTransfer = $this->createRequest($idCustomer, $recurringScheduleTransfer->getUuidOrFail(), [
            (new RecurringScheduleItemTransfer())
                ->setIdRecurringScheduleItem($recurringScheduleItemTransfer->getIdRecurringScheduleItemOrFail())
                ->setQuantity(static::UPDATED_QUANTITY),
        ]);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $recurringScheduleItemEntity = SpyRecurringScheduleItemQuery::create()
            ->findOneByIdRecurringScheduleItem($recurringScheduleItemTransfer->getIdRecurringScheduleItemOrFail());

        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertCount(1, $responseTransfer->getRecurringSchedules());
        $this->assertSame(static::UPDATED_QUANTITY, $recurringScheduleItemEntity->getQuantity());
    }

    public function testReturnsErrorWhenScheduleNotFound(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $requestTransfer = $this->createRequest($idCustomer, 'non-existing-uuid', []);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertCount(0, $responseTransfer->getRecurringSchedules());
    }

    public function testReturnsErrorWhenScheduleBelongsToAnotherCustomer(): void
    {
        // Arrange
        $idOwnerCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $idOtherCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule($idOwnerCustomer);

        $requestTransfer = $this->createRequest($idOtherCustomer, $recurringScheduleTransfer->getUuidOrFail(), []);

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(1, $responseTransfer->getErrors());
        $this->assertCount(0, $responseTransfer->getRecurringSchedules());
    }

    public function testReturnsEmptyResponseWhenNoSchedulesRequested(): void
    {
        // Arrange
        $idCustomer = (int)$this->tester->haveCustomer()->getIdCustomer();
        $requestTransfer = (new RecurringScheduleCollectionRequestTransfer())
            ->setCustomer((new CustomerTransfer())->setIdCustomer($idCustomer));

        // Act
        $responseTransfer = $this->tester->getFacade()->updateRecurringScheduleCollection($requestTransfer);

        // Assert
        $this->assertCount(0, $responseTransfer->getErrors());
        $this->assertCount(0, $responseTransfer->getRecurringSchedules());
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $recurringScheduleItemTransfers
     */
    protected function createRequest(int $idCustomer, string $uuid, array $recurringScheduleItemTransfers): RecurringScheduleCollectionRequestTransfer
    {
        $recurringScheduleTransfer = (new RecurringScheduleTransfer())->setUuid($uuid);

        foreach ($recurringScheduleItemTransfers as $recurringScheduleItemTransfer) {
            $recurringScheduleTransfer->addItem($recurringScheduleItemTransfer);
        }

        return (new RecurringScheduleCollectionRequestTransfer())
            ->setCustomer((new CustomerTransfer())->setIdCustomer($idCustomer))
            ->addRecurringSchedule($recurringScheduleTransfer);
    }
}
