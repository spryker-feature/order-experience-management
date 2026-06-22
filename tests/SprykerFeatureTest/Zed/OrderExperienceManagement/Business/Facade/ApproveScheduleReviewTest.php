<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\PermissionCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Zed\CompanyMailConnector\Business\CompanyMailConnectorBusinessFactory;
use Spryker\Zed\CompanyMailConnector\CompanyMailConnectorDependencyProvider;
use Spryker\Zed\CompanyMailConnector\Dependency\Facade\CompanyMailConnectorToMailFacadeInterface;
use Spryker\Zed\CompanyRole\Communication\Plugin\PermissionStoragePlugin;
use Spryker\Zed\CompanySalesConnector\Communication\Plugin\Permission\SeeCompanyOrdersPermissionPlugin;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Facade
 * @group ApproveScheduleReviewTest
 * Add your own group annotations below this line
 */
class ApproveScheduleReviewTest extends Unit
{
    /**
     * @uses \Spryker\Shared\Price\PriceConfig::PRICE_MODE_GROSS
     */
    protected const string PRICE_MODE_GROSS = 'GROSS_MODE';

    protected const string ERROR_MESSAGE_APPROVE_FAILED = 'recurring_orders.review.approve_failed';

    protected const string ERROR_MESSAGE_ALL_ITEMS_REMOVED = 'recurring_orders.review.all_items_removed';

    protected const string ERROR_MESSAGE_PRICES_CHANGED = 'recurring_orders.review.prices_changed';

    protected const string SKU_A = 'sku-a';

    protected const string SKU_B = 'sku-b';

    protected const string GROUP_KEY_A = 'group-key-a';

    protected const int NON_EXISTENT_CUSTOMER_ID = 0;

    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->ensureRecurringScheduleTablesAreEmpty();
        $this->tester->disableScheduleValidatorPlugins();
        $this->tester->pinMailFacadeDependency();
    }

    public function testReturnsErrorWhenScheduleNotFound(): void
    {
        // Arrange
        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid('non-existent-uuid')
            ->setIdCustomer(static::NON_EXISTENT_CUSTOMER_ID);

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_MESSAGE_APPROVE_FAILED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    public function testReturnsErrorWhenScheduleStatusIsNotReviewRequired(): void
    {
        // Arrange
        [$uuid, $idCustomer] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_ACTIVE, [
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500],
        ]);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer);

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_MESSAGE_APPROVE_FAILED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    public function testReturnsErrorWhenPriceStillDriftsBeyondAcceptedPrice(): void
    {
        // Arrange
        [$uuid, $idCustomer] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500],
        ]);

        $this->tester->setScheduleValidatorPlugins([
            $this->tester->createFixedScheduleValidatorPlugin(static::SKU_A, true, SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED, 900),
        ]);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer);

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_MESSAGE_PRICES_CHANGED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    public function testReturnsErrorWhenAllItemsAreUnpurchasable(): void
    {
        // Arrange
        [$uuid, $idCustomer] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500],
            [RecurringScheduleItemTransfer::SKU => static::SKU_B, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 300],
        ]);

        $this->tester->setScheduleValidatorPlugins([
            $this->tester->createFixedScheduleValidatorPlugin(static::SKU_A, false, SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_UNAVAILABLE),
            $this->tester->createFixedScheduleValidatorPlugin(static::SKU_B, false, SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_UNAVAILABLE),
        ]);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer);

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_MESSAGE_ALL_ITEMS_REMOVED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    public function testApprovesAndRemovesUnpurchasableItem(): void
    {
        // Arrange
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500],
            [RecurringScheduleItemTransfer::SKU => static::SKU_B, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 300],
        ]);

        $this->tester->setScheduleValidatorPlugins([
            $this->tester->createFixedScheduleValidatorPlugin(static::SKU_A, false, SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_UNAVAILABLE),
        ]);
        $this->tester->enableStateMachineConfirmation($idRecurringSchedule);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer);

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $remainingItemTransfers = $this->findScheduleItems($idCustomer, $idRecurringSchedule);
        $this->assertCount(1, $remainingItemTransfers);
        $this->assertSame(static::SKU_B, $remainingItemTransfers[0]->getSku());
    }

    public function testApprovesAndPersistsAcceptedPriceAsNewReference(): void
    {
        // Arrange
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [
                RecurringScheduleItemTransfer::SKU => static::SKU_A,
                RecurringScheduleItemTransfer::QUANTITY => 1,
                RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
                RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A,
            ],
        ]);
        $this->tester->enableStateMachineConfirmation($idRecurringSchedule);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey(static::GROUP_KEY_A))
                    ->setCurrentPrice(800),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $itemTransfers = $this->findScheduleItems($idCustomer, $idRecurringSchedule);
        $this->assertCount(1, $itemTransfers);
        $this->assertSame(800, $itemTransfers[0]->getReferenceGrossPrice());
    }

    public function testApprovesColleagueScheduleWhenCompanyUserHasSeeCompanyOrdersPermission(): void
    {
        // Arrange
        $this->tester->setDependency(
            CompanyMailConnectorDependencyProvider::FACADE_MAIL,
            $this->createMock(CompanyMailConnectorToMailFacadeInterface::class),
            CompanyMailConnectorBusinessFactory::class,
        );
        $this->tester->preparePermissionStorageDependency(new PermissionStoragePlugin());

        $companyTransfer = $this->tester->haveCompany();
        $permissionTransfer = $this->tester->havePermission(new SeeCompanyOrdersPermissionPlugin());

        $companyUserTransfer = $this->tester->haveCompanyUserWithPermissions(
            $companyTransfer,
            (new PermissionCollectionTransfer())->addPermission($permissionTransfer),
        );

        $colleagueCompanyUserTransfer = $this->tester->haveCompanyUserWithPermissions(
            $companyTransfer,
            new PermissionCollectionTransfer(),
        );

        // Schedule awaiting review is owned by a colleague in the same company, not by the acting company user.
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule(
            (int)$colleagueCompanyUserTransfer->getCustomerOrFail()->getIdCustomer(),
            [
                RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED,
                RecurringScheduleTransfer::PRICE_MODE => static::PRICE_MODE_GROSS,
                RecurringScheduleTransfer::ID_COMPANY_USER => $colleagueCompanyUserTransfer->getIdCompanyUser(),
            ],
        );
        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();

        $this->tester->haveRecurringScheduleItem($idRecurringSchedule, [
            RecurringScheduleItemTransfer::SKU => static::SKU_A,
            RecurringScheduleItemTransfer::QUANTITY => 1,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
        ]);
        $this->tester->enableStateMachineConfirmation($idRecurringSchedule);

        $customerTransfer = $companyUserTransfer->getCustomerOrFail()
            ->setCompanyUserTransfer($companyUserTransfer);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setIdCustomer($customerTransfer->getIdCustomerOrFail())
            ->setCustomer($customerTransfer);

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());
    }

    /**
     * @param string $status
     * @param array<int, array<string, mixed>> $itemOverridesList
     *
     * @return array{0: string, 1: int, 2: int}
     */
    protected function haveSchedule(string $status, array $itemOverridesList): array
    {
        $customerTransfer = $this->tester->haveCustomer();
        $this->tester->pinMailFacadeDependency();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule((int)$customerTransfer->getIdCustomer(), [
            RecurringScheduleTransfer::STATUS => $status,
            RecurringScheduleTransfer::PRICE_MODE => static::PRICE_MODE_GROSS,
        ]);
        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();

        foreach ($itemOverridesList as $itemOverrides) {
            $this->tester->haveRecurringScheduleItem($idRecurringSchedule, $itemOverrides);
        }

        return [$recurringScheduleTransfer->getUuidOrFail(), (int)$customerTransfer->getIdCustomer(), $idRecurringSchedule];
    }

    /**
     * @param int $idCustomer
     * @param int $idRecurringSchedule
     *
     * @return array<int, \Generated\Shared\Transfer\RecurringScheduleItemTransfer>
     */
    protected function findScheduleItems(int $idCustomer, int $idRecurringSchedule): array
    {
        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->addIdRecurringSchedule($idRecurringSchedule)
                    ->setIsWithItems(true),
            );

        $recurringScheduleTransfer = $this->tester->getFacade()
            ->getRecurringScheduleCollection($criteriaTransfer)
            ->getRecurringSchedules()
            ->offsetGet(0);

        return array_values($recurringScheduleTransfer->getItems()->getArrayCopy());
    }
}
