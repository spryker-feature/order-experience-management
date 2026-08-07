<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Facade;

use Codeception\Stub;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\PermissionCollectionTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\Map\SpyRecurringScheduleItemTableMap;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleHistoryQuery;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleQuery;
use Propel\Runtime\Propel;
use Spryker\Zed\Checkout\Business\CheckoutFacadeInterface;
use Spryker\Zed\CompanyBusinessUnitSalesConnector\Communication\Plugin\Permission\SeeBusinessUnitOrdersPermissionPlugin;
use Spryker\Zed\CompanyMailConnector\Business\CompanyMailConnectorBusinessFactory;
use Spryker\Zed\CompanyMailConnector\CompanyMailConnectorDependencyProvider;
use Spryker\Zed\CompanyMailConnector\Dependency\Facade\CompanyMailConnectorToMailFacadeInterface;
use Spryker\Zed\CompanyRole\Communication\Plugin\PermissionStoragePlugin;
use Spryker\Zed\CompanySalesConnector\Communication\Plugin\Permission\SeeCompanyOrdersPermissionPlugin;
use Spryker\Zed\StateMachine\Business\StateMachineBusinessFactory;
use Spryker\Zed\StateMachine\StateMachineDependencyProvider;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\AddedItemResolverInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\StateMachine\RecurringOrdersStateMachineHandlerPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementDependencyProvider;
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

    /**
     * @uses \Spryker\Shared\Price\PriceConfig::PRICE_MODE_NET
     */
    protected const string PRICE_MODE_NET = 'NET_MODE';

    protected const string BUNDLE_ITEM_IDENTIFIER = 'bundle-item-1';

    protected const string CONFIGURED_BUNDLE_GROUP_KEY = 'configured-bundle-1';

    protected const string ERROR_MESSAGE_APPROVE_FAILED = 'recurring_orders.review.approve_failed';

    protected const string ERROR_MESSAGE_ALL_ITEMS_REMOVED = 'recurring_orders.review.all_items_removed';

    protected const string ERROR_MESSAGE_PRICES_CHANGED = 'recurring_orders.review.prices_changed';

    protected const string ERROR_MESSAGE_SCOPE_REQUIRED = 'recurring_orders.review.scope_required';

    protected const string ERROR_MESSAGE_QUANTITY_INVALID = 'recurring_orders.review.quantity_invalid';

    protected const string ERROR_MESSAGE_ADD_PRODUCT_NOT_AVAILABLE = 'recurring_orders.review.add_product.error.not_available';

    protected const string SKU_UNRESOLVABLE = 'sku-does-not-exist';

    /**
     * @uses \SprykerFeatureTest\Shared\OrderExperienceManagement\Helper\RecurringScheduleHelper::OVERRIDE_BUILD_QUOTE_DATA
     */
    protected const string OVERRIDE_BUILD_QUOTE_DATA = 'build_quote_data';

    /**
     * @uses \SprykerFeatureTest\Shared\OrderExperienceManagement\Helper\RecurringScheduleHelper::OVERRIDE_BUILD_ITEM_DATA
     */
    protected const string OVERRIDE_BUILD_ITEM_DATA = 'build_item_data';

    protected const string SKU_A = 'sku-a';

    protected const string SKU_B = 'sku-b';

    protected const string SKU_ADDED = 'sku-added';

    protected const int ID_SHIPMENT_METHOD = 1;

    protected const int ID_COST_CENTER = 10;

    protected const int ID_BUDGET = 20;

    protected const int ID_COST_CENTER_SUBMITTED = 30;

    protected const int ID_BUDGET_SUBMITTED = 40;

    protected const string GROUP_KEY_A = 'group-key-a';

    protected const string GROUP_KEY_B = 'group-key-b';

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

    public function testApproveBeforePlacementDueDateReturnsScheduleToActiveWithoutPlacingOrder(): void
    {
        // Arrange — the schedule's execution date lies in the future, so the confirm event must not place the order.
        $futureTriggerDate = (new DateTimeImmutable('+14 days'))->format('Y-m-d');

        $customerTransfer = $this->tester->haveCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule((int)$customerTransfer->getIdCustomer(), [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED,
            RecurringScheduleTransfer::PRICE_MODE => static::PRICE_MODE_GROSS,
            RecurringScheduleTransfer::FIRST_TRIGGER_DATE => $futureTriggerDate,
            RecurringScheduleTransfer::NEXT_TRIGGER_DATE => $futureTriggerDate,
            static::OVERRIDE_BUILD_QUOTE_DATA => true,
        ]);
        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();

        $this->tester->haveRecurringScheduleItem($idRecurringSchedule, [
            RecurringScheduleItemTransfer::SKU => static::SKU_A,
            RecurringScheduleItemTransfer::QUANTITY => 1,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
            RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A,
            static::OVERRIDE_BUILD_ITEM_DATA => true,
        ]);

        // The real state machine resolves the confirm target through the RecurringOrders/IsPlacementDue condition.
        $this->tester->setDependency(
            StateMachineDependencyProvider::PLUGINS_STATE_MACHINE_HANDLERS,
            [new RecurringOrdersStateMachineHandlerPlugin()],
            StateMachineBusinessFactory::class,
        );

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setIdCustomer($customerTransfer->getIdCustomerOrFail())
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey(static::GROUP_KEY_A))
                    ->setCurrentPrice(800),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert — the accepted price is applied, but placement is deferred: schedule returns to active untouched.
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $recurringScheduleEntity = SpyRecurringScheduleQuery::create()->findOneByIdRecurringSchedule($idRecurringSchedule);
        $this->assertSame(SharedOrderExperienceManagementConfig::STATUS_ACTIVE, $recurringScheduleEntity->getStatus());
        $this->assertSame($futureTriggerDate, $recurringScheduleEntity->getNextTriggerDate()->format('Y-m-d'));

        $placementHistoryCount = SpyRecurringScheduleHistoryQuery::create()
            ->filterByFkRecurringSchedule($idRecurringSchedule)
            ->count();
        $this->assertSame(0, $placementHistoryCount);

        $itemTransfers = $this->findScheduleItems((int)$customerTransfer->getIdCustomer(), $idRecurringSchedule);
        $this->assertSame(800, $itemTransfers[0]->getReferenceGrossPrice());
    }

    public function testApprovesColleagueScheduleWhenCompanyUserHasSeeBusinessUnitOrdersPermission(): void
    {
        // Arrange
        $this->tester->setDependency(
            CompanyMailConnectorDependencyProvider::FACADE_MAIL,
            $this->createMock(CompanyMailConnectorToMailFacadeInterface::class),
            CompanyMailConnectorBusinessFactory::class,
        );
        $this->tester->preparePermissionStorageDependency(new PermissionStoragePlugin());

        $companyTransfer = $this->tester->haveCompany();
        $permissionTransfer = $this->tester->havePermission(new SeeBusinessUnitOrdersPermissionPlugin());

        $companyUserTransfer = $this->tester->haveCompanyUserWithPermissions(
            $companyTransfer,
            (new PermissionCollectionTransfer())->addPermission($permissionTransfer),
        );

        $sameBusinessUnitCompanyUserTransfer = $this->tester->haveCompanyUserWithPermissions(
            $companyTransfer,
            new PermissionCollectionTransfer(),
        );
        $sameBusinessUnitCompanyUserTransfer->setFkCompanyBusinessUnit($companyUserTransfer->getFkCompanyBusinessUnit());

        // Schedule awaiting review is owned by a colleague in the same business unit.
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule(
            (int)$sameBusinessUnitCompanyUserTransfer->getCustomerOrFail()->getIdCustomer(),
            [
                RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED,
                RecurringScheduleTransfer::PRICE_MODE => static::PRICE_MODE_GROSS,
                RecurringScheduleTransfer::ID_COMPANY_USER => $sameBusinessUnitCompanyUserTransfer->getIdCompanyUser(),
                static::OVERRIDE_BUILD_QUOTE_DATA => true,
            ],
        );
        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();

        $this->tester->haveRecurringScheduleItem($idRecurringSchedule, [
            RecurringScheduleItemTransfer::SKU => static::SKU_A,
            RecurringScheduleItemTransfer::QUANTITY => 1,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
            static::OVERRIDE_BUILD_ITEM_DATA => true,
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
                static::OVERRIDE_BUILD_QUOTE_DATA => true,
            ],
        );
        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();

        $this->tester->haveRecurringScheduleItem($idRecurringSchedule, [
            RecurringScheduleItemTransfer::SKU => static::SKU_A,
            RecurringScheduleItemTransfer::QUANTITY => 1,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
            static::OVERRIDE_BUILD_ITEM_DATA => true,
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

    public function testApprovesAndPersistsStandingQuantityWithReferencePricePreserved(): void
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
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_STANDING)
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey(static::GROUP_KEY_A))
                    ->setAcceptedQuantity(5),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $itemTransfers = $this->findScheduleItems($idCustomer, $idRecurringSchedule);
        $this->assertCount(1, $itemTransfers);
        $this->assertSame(5, $itemTransfers[0]->getQuantity());
        $this->assertSame(500, $itemTransfers[0]->getReferenceGrossPrice());
    }

    public function testApprovesAndPersistsOccurrenceQuantityWithoutChangingStandingQuantity(): void
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

        // Occurrence scope overrides the upcoming order only; the standing quantity must stay unchanged.
        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_OCCURRENCE)
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey(static::GROUP_KEY_A))
                    ->setAcceptedQuantity(5),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $itemTransfers = $this->findScheduleItems($idCustomer, $idRecurringSchedule);
        $this->assertCount(1, $itemTransfers);
        $this->assertSame(1, $itemTransfers[0]->getQuantity());
        $this->assertSame(5, $itemTransfers[0]->getNextDeliveryQuantity());
    }

    public function testApprovesAndRebaselinesAcceptedPriceWithOccurrenceScope(): void
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
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_OCCURRENCE)
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

    public function testReturnsScopeRequiredErrorWhenQuantityChangedWithoutScope(): void
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

        // A quantity is submitted but no scope is chosen on the request — the buyer must choose explicitly (Story 9).
        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey(static::GROUP_KEY_A))
                    ->setAcceptedQuantity(5),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_MESSAGE_SCOPE_REQUIRED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());

        $itemTransfers = $this->findScheduleItems($idCustomer, $idRecurringSchedule);
        $this->assertSame(1, $itemTransfers[0]->getQuantity());
        $this->assertNull($itemTransfers[0]->getNextDeliveryQuantity());
    }

    /**
     * @return array<string, array{int}>
     */
    public function invalidAcceptedQuantityDataProvider(): array
    {
        return [
            'zero' => [0],
            'negative' => [-5],
        ];
    }

    /**
     * @dataProvider invalidAcceptedQuantityDataProvider
     */
    public function testReturnsQuantityInvalidErrorAndLeavesScheduleUntouchedWhenAcceptedQuantityIsBelowOne(
        int $acceptedQuantity,
    ): void {
        // Arrange
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [
                RecurringScheduleItemTransfer::SKU => static::SKU_A,
                RecurringScheduleItemTransfer::QUANTITY => 1,
                RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
                RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A,
            ],
        ]);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_STANDING)
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey(static::GROUP_KEY_A))
                    ->setAcceptedQuantity($acceptedQuantity),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert - the quantity is rejected with an error rather than silently dropped.
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_MESSAGE_QUANTITY_INVALID, $responseTransfer->getErrors()->offsetGet(0)->getMessage());

        $itemTransfers = $this->findScheduleItems($idCustomer, $idRecurringSchedule);
        $this->assertSame(1, $itemTransfers[0]->getQuantity());
        $this->assertNull($itemTransfers[0]->getNextDeliveryQuantity());
    }

    public function testReturnsQuantityInvalidErrorBeforeScopeRequiredWhenQuantityIsBelowOneAndScopeIsMissing(): void
    {
        // Arrange
        [$uuid, $idCustomer] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [
                RecurringScheduleItemTransfer::SKU => static::SKU_A,
                RecurringScheduleItemTransfer::QUANTITY => 1,
                RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
                RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A,
            ],
        ]);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey(static::GROUP_KEY_A))
                    ->setAcceptedQuantity(0),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert - the quantity is the accurate complaint; asking for a scope first would mislead.
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_MESSAGE_QUANTITY_INVALID, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    public function testCollapsesSplitGroupIntoSingleRowOnStandingQuantityEdit(): void
    {
        // Arrange — one line stored as three rows (quantity 1 each) sharing a single group key.
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
        ]);
        $this->tester->enableStateMachineConfirmation($idRecurringSchedule);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_STANDING)
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey(static::GROUP_KEY_A))
                    ->setAcceptedQuantity(5),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert — the group is a single row of exactly 5, not 5 per row.
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $itemTransfers = $this->findScheduleItems($idCustomer, $idRecurringSchedule);
        $this->assertCount(1, $itemTransfers);
        $this->assertSame(5, $itemTransfers[0]->getQuantity());
    }

    public function testDistributesOccurrenceQuantityAcrossSplitGroupWithoutChangingStanding(): void
    {
        // Arrange — one line stored as three rows (quantity 1 each) sharing a single group key.
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
        ]);
        $this->tester->enableStateMachineConfirmation($idRecurringSchedule);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_OCCURRENCE)
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey(static::GROUP_KEY_A))
                    ->setAcceptedQuantity(5),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert — standing rows are untouched (three rows of 1), and the next delivery sums to exactly 5.
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $itemTransfers = $this->findScheduleItems($idCustomer, $idRecurringSchedule);
        $this->assertCount(3, $itemTransfers);

        $standingTotal = 0;
        $nextDeliveryTotal = 0;
        foreach ($itemTransfers as $recurringScheduleItemTransfer) {
            $standingTotal += (int)$recurringScheduleItemTransfer->getQuantity();
            $nextDeliveryTotal += (int)$recurringScheduleItemTransfer->getNextDeliveryQuantity();
        }

        $this->assertSame(3, $standingTotal);
        $this->assertSame(5, $nextDeliveryTotal);
    }

    public function testSkipsUnavailableItemForNextOrderUnderOccurrenceScope(): void
    {
        // Arrange
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
            [RecurringScheduleItemTransfer::SKU => static::SKU_B, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 300, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_B],
        ]);
        $this->tester->setScheduleValidatorPlugins([
            $this->tester->createFixedScheduleValidatorPlugin(static::SKU_B, false, SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_UNAVAILABLE),
        ]);
        $this->tester->enableStateMachineConfirmation($idRecurringSchedule);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_OCCURRENCE);

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert — the unavailable item stays in the standing schedule but is skipped for the next order only.
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $itemTransfers = $this->findScheduleItems($idCustomer, $idRecurringSchedule);
        $this->assertCount(2, $itemTransfers);

        $itemsBySku = [];
        foreach ($itemTransfers as $recurringScheduleItemTransfer) {
            $itemsBySku[$recurringScheduleItemTransfer->getSku()] = $recurringScheduleItemTransfer;
        }

        $this->assertSame(0, $itemsBySku[static::SKU_B]->getNextDeliveryQuantity());
        $this->assertNull($itemsBySku[static::SKU_A]->getNextDeliveryQuantity());
    }

    public function testRemovesLinePermanentlyUnderStandingScope(): void
    {
        // Arrange
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
            [RecurringScheduleItemTransfer::SKU => static::SKU_B, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 300, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_B],
        ]);
        $this->tester->enableStateMachineConfirmation($idRecurringSchedule);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_STANDING)
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey(static::GROUP_KEY_A))
                    ->setIsRemoved(true),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert — the removed line is deleted; the other line remains.
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $itemTransfers = $this->findScheduleItems($idCustomer, $idRecurringSchedule);
        $this->assertCount(1, $itemTransfers);
        $this->assertSame(static::SKU_B, $itemTransfers[0]->getSku());
    }

    public function testSkipsRemovedLineForNextOrderUnderOccurrenceScope(): void
    {
        // Arrange
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
            [RecurringScheduleItemTransfer::SKU => static::SKU_B, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 300, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_B],
        ]);
        $this->tester->enableStateMachineConfirmation($idRecurringSchedule);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_OCCURRENCE)
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey(static::GROUP_KEY_A))
                    ->setIsRemoved(true),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert — the removed line stays in the standing schedule but is skipped for the next order only.
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $itemTransfers = $this->findScheduleItems($idCustomer, $idRecurringSchedule);
        $this->assertCount(2, $itemTransfers);

        $itemsBySku = [];
        foreach ($itemTransfers as $recurringScheduleItemTransfer) {
            $itemsBySku[$recurringScheduleItemTransfer->getSku()] = $recurringScheduleItemTransfer;
        }

        $this->assertSame(0, $itemsBySku[static::SKU_A]->getNextDeliveryQuantity());
        $this->assertNull($itemsBySku[static::SKU_B]->getNextDeliveryQuantity());
    }

    public function testReturnsScopeRequiredErrorWhenRemovalWithoutScope(): void
    {
        // Arrange
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
            [RecurringScheduleItemTransfer::SKU => static::SKU_B, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 300, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_B],
        ]);

        // A removal is a composition change and must carry an explicit scope.
        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey(static::GROUP_KEY_A))
                    ->setIsRemoved(true),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_MESSAGE_SCOPE_REQUIRED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertCount(2, $this->findScheduleItems($idCustomer, $idRecurringSchedule));
    }

    public function testReturnsAllItemsRemovedErrorWhenEveryLineRemoved(): void
    {
        // Arrange
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
        ]);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_STANDING)
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey(static::GROUP_KEY_A))
                    ->setIsRemoved(true),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert — removing the only line is rejected and nothing is applied.
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_MESSAGE_ALL_ITEMS_REMOVED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
        $this->assertCount(1, $this->findScheduleItems($idCustomer, $idRecurringSchedule));
    }

    public function testApprovesAndPersistsAcceptedPriceAsNewNetReferenceInNetMode(): void
    {
        // Arrange
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveSchedule(
            SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED,
            [
                [
                    RecurringScheduleItemTransfer::SKU => static::SKU_A,
                    RecurringScheduleItemTransfer::QUANTITY => 1,
                    RecurringScheduleItemTransfer::REFERENCE_NET_PRICE => 500,
                    RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A,
                ],
            ],
            static::PRICE_MODE_NET,
        );
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
        $this->assertSame(800, $itemTransfers[0]->getReferenceNetPrice());
    }

    public function testRemovesBundleLineByBundleItemIdentifierUnderStandingScope(): void
    {
        // Arrange
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [
                RecurringScheduleItemTransfer::SKU => static::SKU_A,
                RecurringScheduleItemTransfer::QUANTITY => 1,
                RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
                RecurringScheduleItemTransfer::BUNDLE_ITEM_IDENTIFIER => static::BUNDLE_ITEM_IDENTIFIER,
            ],
            [
                RecurringScheduleItemTransfer::SKU => static::SKU_B,
                RecurringScheduleItemTransfer::QUANTITY => 1,
                RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 300,
                RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_B,
            ],
        ]);
        $this->tester->enableStateMachineConfirmation($idRecurringSchedule);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_STANDING)
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setBundleItemIdentifier(static::BUNDLE_ITEM_IDENTIFIER))
                    ->setIsRemoved(true),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $itemTransfers = $this->findScheduleItems($idCustomer, $idRecurringSchedule);
        $this->assertCount(1, $itemTransfers);
        $this->assertSame(static::SKU_B, $itemTransfers[0]->getSku());
    }

    public function testRemovesConfiguredBundleLineByConfiguredBundleGroupKeyUnderStandingScope(): void
    {
        // Arrange
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [
                RecurringScheduleItemTransfer::SKU => static::SKU_A,
                RecurringScheduleItemTransfer::QUANTITY => 1,
                RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
                RecurringScheduleItemTransfer::CONFIGURED_BUNDLE_GROUP_KEY => static::CONFIGURED_BUNDLE_GROUP_KEY,
            ],
            [
                RecurringScheduleItemTransfer::SKU => static::SKU_B,
                RecurringScheduleItemTransfer::QUANTITY => 1,
                RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 300,
                RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_B,
            ],
        ]);
        $this->tester->enableStateMachineConfirmation($idRecurringSchedule);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_STANDING)
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setConfiguredBundleGroupKey(static::CONFIGURED_BUNDLE_GROUP_KEY))
                    ->setIsRemoved(true),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $itemTransfers = $this->findScheduleItems($idCustomer, $idRecurringSchedule);
        $this->assertCount(1, $itemTransfers);
        $this->assertSame(static::SKU_B, $itemTransfers[0]->getSku());
    }

    public function testRemovesLineByItemIdWhenNoGroupAddressingUnderStandingScope(): void
    {
        // Arrange
        $customerTransfer = $this->tester->haveCustomer();
        $this->tester->pinMailFacadeDependency();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule((int)$customerTransfer->getIdCustomer(), [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED,
            RecurringScheduleTransfer::PRICE_MODE => static::PRICE_MODE_GROSS,
            static::OVERRIDE_BUILD_QUOTE_DATA => true,
        ]);
        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();

        $removedItemTransfer = $this->tester->haveRecurringScheduleItem($idRecurringSchedule, [
            RecurringScheduleItemTransfer::SKU => static::SKU_A,
            RecurringScheduleItemTransfer::QUANTITY => 1,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
            RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A,
            static::OVERRIDE_BUILD_ITEM_DATA => true,
        ]);
        $this->tester->haveRecurringScheduleItem($idRecurringSchedule, [
            RecurringScheduleItemTransfer::SKU => static::SKU_B,
            RecurringScheduleItemTransfer::QUANTITY => 1,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 300,
            RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_B,
            static::OVERRIDE_BUILD_ITEM_DATA => true,
        ]);
        $this->tester->enableStateMachineConfirmation($idRecurringSchedule);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($recurringScheduleTransfer->getUuidOrFail())
            ->setIdCustomer((int)$customerTransfer->getIdCustomer())
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_STANDING)
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem(
                        (new RecurringScheduleItemTransfer())->setIdRecurringScheduleItem($removedItemTransfer->getIdRecurringScheduleItemOrFail()),
                    )
                    ->setIsRemoved(true),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $itemTransfers = $this->findScheduleItems((int)$customerTransfer->getIdCustomer(), $idRecurringSchedule);
        $this->assertCount(1, $itemTransfers);
        $this->assertSame(static::SKU_B, $itemTransfers[0]->getSku());
    }

    public function testApprovesAndRetainsFlaggedButPurchasableItem(): void
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
        $this->tester->setScheduleValidatorPlugins([
            $this->tester->createFixedScheduleValidatorPlugin(static::SKU_A, true, SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_SUBSTITUTED),
        ]);
        $this->tester->enableStateMachineConfirmation($idRecurringSchedule);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer);

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $itemTransfers = $this->findScheduleItems($idCustomer, $idRecurringSchedule);
        $this->assertCount(1, $itemTransfers);
        $this->assertSame(static::SKU_A, $itemTransfers[0]->getSku());
    }

    public function testReturnsApproveFailedWhenConfirmationEventDoesNotFire(): void
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

        $this->tester->failStateMachineConfirmation($idRecurringSchedule);

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
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_MESSAGE_APPROVE_FAILED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());

        // Apply and confirm run in one transaction, so in production the failed confirm rolls the re-baselined
        // price back. That rollback is not asserted here: this suite wraps each test in its own transaction, which
        // makes the approver's transaction nested, so its rollback only materializes when the test transaction closes.
    }

    public function testReturnsScopeRequiredErrorWhenAdditionsPresentWithoutScope(): void
    {
        // Arrange
        [$uuid, $idCustomer] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500],
        ]);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->addAddedItem(
                (new RecurringScheduleItemAdditionTransfer())
                    ->setSku(static::SKU_A)
                    ->setQuantity(1),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_MESSAGE_SCOPE_REQUIRED, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    public function testReturnsNotAvailableErrorForUnresolvableAddedProduct(): void
    {
        // Arrange
        [$uuid, $idCustomer] = $this->haveScheduleWithQuoteData([
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500],
        ]);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_STANDING)
            ->addAddedItem(
                (new RecurringScheduleItemAdditionTransfer())
                    ->setSku(static::SKU_UNRESOLVABLE)
                    ->setQuantity(1),
            );

        // The bogus SKU's real cart/offer resolution is environment-dependent; stub the resolve-once step so
        // the addition deterministically resolves to nothing and hits the not-available branch.
        $this->tester->mockFactoryMethod(
            'createAddedItemResolver',
            Stub::makeEmpty(AddedItemResolverInterface::class, [
                'resolveAddedItems' => [],
            ]),
        );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());

        $errorTransfer = $responseTransfer->getErrors()->offsetGet(0);
        $this->assertSame(static::ERROR_MESSAGE_ADD_PRODUCT_NOT_AVAILABLE, $errorTransfer->getMessage());
        $this->assertSame(static::SKU_UNRESOLVABLE, $errorTransfer->getParameters()['%sku%'] ?? null);
    }

    public function testDoesNotReportAllItemsRemovedWhenAdditionsArePresent(): void
    {
        // Arrange
        [$uuid, $idCustomer] = $this->haveScheduleWithQuoteData([
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
        ]);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_STANDING)
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey(static::GROUP_KEY_A))
                    ->setIsRemoved(true),
            )
            ->addAddedItem(
                (new RecurringScheduleItemAdditionTransfer())
                    ->setSku(static::SKU_UNRESOLVABLE)
                    ->setQuantity(1),
            );

        // The bogus SKU's real cart/offer resolution is environment-dependent; stub the resolve-once step so
        // the addition deterministically resolves to nothing and hits the not-available branch.
        $this->tester->mockFactoryMethod(
            'createAddedItemResolver',
            Stub::makeEmpty(AddedItemResolverInterface::class, [
                'resolveAddedItems' => [],
            ]),
        );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertFalse($responseTransfer->getIsSuccessful());
        $this->assertSame(static::ERROR_MESSAGE_ADD_PRODUCT_NOT_AVAILABLE, $responseTransfer->getErrors()->offsetGet(0)->getMessage());
    }

    public function testPersistsAddedItemWhenApprovalSucceeds(): void
    {
        // Arrange
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveScheduleWithQuoteData([
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
        ]);

        // The real cart/offer/shipment resolution needs infrastructure absent from the isolated Business suite,
        // so stub the single resolution point (the resolve-once approver step) and the placeability check to
        // exercise the persist path end to end.
        // Product ids are required on every quote item by recalculation (category discount decision rules).
        $resolvedItemTransfer = (new ItemTransfer())
            ->setSku(static::SKU_ADDED)
            ->setQuantity(2)
            ->setId(0)
            ->setIdProductAbstract(0)
            ->setUnitGrossPrice(1000)
            ->setUnitNetPrice(900)
            ->setShipment(
                (new ShipmentTransfer())->setMethod(
                    (new ShipmentMethodTransfer())
                        ->setIdShipmentMethod(static::ID_SHIPMENT_METHOD)
                        ->setStoreCurrencyPrice(490),
                ),
            );

        $this->tester->mockFactoryMethod(
            'createAddedItemResolver',
            Stub::makeEmpty(AddedItemResolverInterface::class, [
                'resolveAddedItems' => [[$resolvedItemTransfer]],
            ]),
        );
        $this->tester->setDependency(
            OrderExperienceManagementDependencyProvider::FACADE_CHECKOUT,
            Stub::makeEmpty(CheckoutFacadeInterface::class, [
                'isPlaceableOrder' => (new CheckoutResponseTransfer())->setIsSuccess(true),
            ]),
        );
        $this->tester->enableStateMachineConfirmation($idRecurringSchedule);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_STANDING)
            ->addAddedItem(
                (new RecurringScheduleItemAdditionTransfer())
                    ->setSku(static::SKU_ADDED)
                    ->setQuantity(2),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $persistedSkus = array_map(
            static fn (RecurringScheduleItemTransfer $recurringScheduleItemTransfer): ?string => $recurringScheduleItemTransfer->getSku(),
            $this->findScheduleItems($idCustomer, $idRecurringSchedule),
        );

        $this->assertContains(static::SKU_ADDED, $persistedSkus, 'The approved added product should be persisted as a schedule item.');
    }

    public function testAppliesSubmittedCostCenterAndBudgetToScheduleQuoteDataOnApproval(): void
    {
        // Arrange
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveScheduleWithQuoteData([
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
        ]);
        $this->tester->enableStateMachineConfirmation($idRecurringSchedule);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_STANDING)
            ->setQuote(
                (new QuoteTransfer())
                    ->setIdCostCenter(static::ID_COST_CENTER_SUBMITTED)
                    ->setIdBudget(static::ID_BUDGET_SUBMITTED),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $quoteData = $this->findScheduleQuoteData($idCustomer, $idRecurringSchedule);
        $this->assertSame(static::ID_COST_CENTER_SUBMITTED, $quoteData[QuoteTransfer::ID_COST_CENTER] ?? null);
        $this->assertSame(static::ID_BUDGET_SUBMITTED, $quoteData[QuoteTransfer::ID_BUDGET] ?? null);
    }

    public function testOverwritesExistingCostCenterAndBudgetWithSubmittedValuesWhilePreservingOtherQuoteData(): void
    {
        // Arrange
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveScheduleWithSeededQuoteData([
            QuoteTransfer::ID_COST_CENTER => static::ID_COST_CENTER,
            QuoteTransfer::ID_BUDGET => static::ID_BUDGET,
            QuoteTransfer::PRICE_MODE => static::PRICE_MODE_GROSS,
        ]);
        $this->tester->enableStateMachineConfirmation($idRecurringSchedule);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_STANDING)
            ->setQuote(
                (new QuoteTransfer())
                    ->setIdCostCenter(static::ID_COST_CENTER_SUBMITTED)
                    ->setIdBudget(static::ID_BUDGET_SUBMITTED),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $quoteData = $this->findScheduleQuoteData($idCustomer, $idRecurringSchedule);
        $this->assertSame(static::ID_COST_CENTER_SUBMITTED, $quoteData[QuoteTransfer::ID_COST_CENTER] ?? null);
        $this->assertSame(static::ID_BUDGET_SUBMITTED, $quoteData[QuoteTransfer::ID_BUDGET] ?? null);
        $this->assertSame(static::PRICE_MODE_GROSS, $quoteData[QuoteTransfer::PRICE_MODE] ?? null);
    }

    public function testLeavesQuoteDataUnchangedWhenNoQuoteOverrideSent(): void
    {
        // Arrange
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveScheduleWithSeededQuoteData([
            QuoteTransfer::ID_COST_CENTER => static::ID_COST_CENTER,
            QuoteTransfer::ID_BUDGET => static::ID_BUDGET,
        ]);
        $this->tester->enableStateMachineConfirmation($idRecurringSchedule);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer);

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $quoteData = $this->findScheduleQuoteData($idCustomer, $idRecurringSchedule);
        $this->assertSame(static::ID_COST_CENTER, $quoteData[QuoteTransfer::ID_COST_CENTER] ?? null);
        $this->assertSame(static::ID_BUDGET, $quoteData[QuoteTransfer::ID_BUDGET] ?? null);
    }

    public function testKeepsQueryCountGrowthToAtMostOneStatementPerAcceptedGroup(): void
    {
        // Arrange - two schedules differing only in how many distinct group keys are under review.
        $smallGroupCount = 3;
        $largeGroupCount = 30;

        // Act
        $smallQueryCount = $this->countApplyApprovedChangesQueries($smallGroupCount);
        $largeQueryCount = $this->countApplyApprovedChangesQueries($largeGroupCount);

        // Assert - the growth rate is asserted rather than an absolute count because ActiveRecordBatchProcessorTrait
        // concatenates UPDATEs only on MariaDB: there the count is a flat 3, while on PostgreSQL it is one UPDATE per
        // row on top of the two constant SELECTs. The batching guarantee both engines share is at most one statement
        // per additional accepted group, against roughly four per group before this path was batched.
        $this->assertGreaterThan(0, $smallQueryCount, 'No queries were counted, so the growth assertion would hold vacuously.');

        $this->assertLessThanOrEqual(
            $largeGroupCount - $smallGroupCount,
            $largeQueryCount - $smallQueryCount,
            sprintf(
                'Applying %d accepted groups issued %d queries while %d groups issued %d; the write path is not batched.',
                $largeGroupCount,
                $largeQueryCount,
                $smallGroupCount,
                $smallQueryCount,
            ),
        );
    }

    public function testAppliesAcceptedQuantityToGroupAlsoFlaggedUnpurchasableUnderOccurrenceScope(): void
    {
        // Arrange - the storefront renders the quantity input for an unpurchasable line too (only its remove button is
        // hidden), so the same group key can arrive both flagged-unpurchasable and as an accepted item.
        // A second, purchasable line keeps the all-items-unpurchasable validator from rejecting the submission.
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
            [RecurringScheduleItemTransfer::SKU => static::SKU_B, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 300, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_B],
        ]);
        $this->tester->setScheduleValidatorPlugins([
            $this->tester->createFixedScheduleValidatorPlugin(static::SKU_A, false, SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_UNAVAILABLE),
        ]);
        $this->tester->enableStateMachineConfirmation($idRecurringSchedule);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_OCCURRENCE)
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey(static::GROUP_KEY_A))
                    ->setAcceptedQuantity(5),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert - the accepted quantity wins over the unpurchasable skip because removals are applied first.
        // This re-arms an unpurchasable line for the next delivery, which looks wrong but is pre-existing behaviour
        // pinned here deliberately so the batching change stays behaviour-neutral. Tracked separately as a bug.
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $itemsBySku = [];
        foreach ($this->findScheduleItems($idCustomer, $idRecurringSchedule) as $recurringScheduleItemTransfer) {
            $itemsBySku[$recurringScheduleItemTransfer->getSku()] = $recurringScheduleItemTransfer;
        }

        $this->assertSame(5, $itemsBySku[static::SKU_A]->getNextDeliveryQuantity());
    }

    public function testKeepsSurvivingRowOfSplitGroupWhenBundleRemovalOverlapsUnderStandingScope(): void
    {
        // Arrange - the first row of a split group also belongs to a bundle that is removed in the same submission,
        // so the accepted quantity has to land on the lowest SURVIVING row, not on the deleted one.
        [$uuid, $idCustomer, $idRecurringSchedule] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, [
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A, RecurringScheduleItemTransfer::BUNDLE_ITEM_IDENTIFIER => static::BUNDLE_ITEM_IDENTIFIER],
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500, RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A],
        ]);
        $this->tester->enableStateMachineConfirmation($idRecurringSchedule);

        $requestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->setUuid($uuid)
            ->setIdCustomer($idCustomer)
            ->setScope(SharedOrderExperienceManagementConfig::SCOPE_STANDING)
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem(
                        (new RecurringScheduleItemTransfer())->setBundleItemIdentifier(static::BUNDLE_ITEM_IDENTIFIER),
                    )
                    ->setIsRemoved(true),
            )
            ->addAcceptedItem(
                (new RecurringScheduleItemReviewTransfer())
                    ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey(static::GROUP_KEY_A))
                    ->setAcceptedQuantity(7),
            );

        // Act
        $responseTransfer = $this->tester->getFacade()->approveScheduleReview($requestTransfer);

        // Assert - the group survives as exactly one row carrying the whole accepted quantity.
        $this->assertTrue($responseTransfer->getIsSuccessful());

        $itemTransfers = $this->findScheduleItems($idCustomer, $idRecurringSchedule);
        $this->assertCount(1, $itemTransfers);
        $this->assertSame(7, $itemTransfers[0]->getQuantity());
        $this->assertNull($itemTransfers[0]->getBundleItemIdentifier());
    }

    /**
     * Seeds a schedule with the given number of single-row group keys, then measures how many statements
     * applying an accepted change for every one of them costs.
     */
    protected function countApplyApprovedChangesQueries(int $groupCount): int
    {
        $itemOverridesList = [];
        $acceptedItemReviewTransfers = [];

        for ($i = 0; $i < $groupCount; $i++) {
            $groupKey = sprintf('%s-%d', static::GROUP_KEY_A, $i);
            $itemOverridesList[] = [
                RecurringScheduleItemTransfer::SKU => static::SKU_A,
                RecurringScheduleItemTransfer::QUANTITY => 1,
                RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
                RecurringScheduleItemTransfer::GROUP_KEY => $groupKey,
            ];
            $acceptedItemReviewTransfers[] = (new RecurringScheduleItemReviewTransfer())
                ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey($groupKey))
                ->setCurrentPrice(600)
                ->setAcceptedQuantity(2);
        }

        [, , $idRecurringSchedule] = $this->haveSchedule(SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED, $itemOverridesList);

        $recurringScheduleReviewResponseTransfer = (new RecurringScheduleReviewResponseTransfer())
            ->setRecurringSchedule(
                (new RecurringScheduleTransfer())
                    ->setIdRecurringSchedule($idRecurringSchedule)
                    ->setPriceMode(static::PRICE_MODE_GROSS),
            );

        $scheduleReviewChangeApplier = $this->tester->getFactory()->createScheduleReviewChangeApplier();

        /** @var \Propel\Runtime\Connection\ConnectionWrapper $connection */
        $connection = Propel::getWriteConnection(SpyRecurringScheduleItemTableMap::DATABASE_NAME);

        // Disabling resets the counter, enabling does not - so the seeding above is excluded only by toggling off first.
        $connection->useDebug(false);
        $connection->useDebug(true);

        try {
            $scheduleReviewChangeApplier->applyApprovedChanges(
                $recurringScheduleReviewResponseTransfer,
                $acceptedItemReviewTransfers,
                SharedOrderExperienceManagementConfig::SCOPE_STANDING,
            );

            return $connection->getQueryCount();
        } finally {
            $connection->useDebug(false);
        }
    }

    /**
     * @param string $status
     * @param array<int, array<string, mixed>> $itemOverridesList
     * @param string $priceMode
     *
     * @return array{0: string, 1: int, 2: int}
     */
    protected function haveSchedule(string $status, array $itemOverridesList, string $priceMode = self::PRICE_MODE_GROSS): array
    {
        $customerTransfer = $this->tester->haveCustomer();
        $this->tester->pinMailFacadeDependency();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule((int)$customerTransfer->getIdCustomer(), [
            RecurringScheduleTransfer::STATUS => $status,
            RecurringScheduleTransfer::PRICE_MODE => $priceMode,
            static::OVERRIDE_BUILD_QUOTE_DATA => true,
        ]);
        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();

        foreach ($itemOverridesList as $itemOverrides) {
            $this->tester->haveRecurringScheduleItem($idRecurringSchedule, $itemOverrides + [static::OVERRIDE_BUILD_ITEM_DATA => true]);
        }

        return [$recurringScheduleTransfer->getUuidOrFail(), (int)$customerTransfer->getIdCustomer(), $idRecurringSchedule];
    }

    /**
     * Builds a review-required schedule carrying serialized quote data, required by the added-item flow which
     * deserializes the schedule quote to resolve the cart items being added.
     *
     * @param array<int, array<string, mixed>> $itemOverridesList
     *
     * @return array{0: string, 1: int, 2: int}
     */
    protected function haveScheduleWithQuoteData(array $itemOverridesList): array
    {
        $customerTransfer = $this->tester->haveCustomer();
        $this->tester->pinMailFacadeDependency();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule((int)$customerTransfer->getIdCustomer(), [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED,
            RecurringScheduleTransfer::PRICE_MODE => static::PRICE_MODE_GROSS,
            static::OVERRIDE_BUILD_QUOTE_DATA => true,
        ]);
        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();

        foreach ($itemOverridesList as $itemOverrides) {
            $this->tester->haveRecurringScheduleItem($idRecurringSchedule, $itemOverrides + [static::OVERRIDE_BUILD_ITEM_DATA => true]);
        }

        return [$recurringScheduleTransfer->getUuidOrFail(), (int)$customerTransfer->getIdCustomer(), $idRecurringSchedule];
    }

    /**
     * Seeds a review-required schedule whose quote data is pre-populated with the given values, used to assert how
     * a submitted cost center/budget override is merged into the already stored quote data.
     *
     * @param array<string, mixed> $quoteData
     *
     * @return array{0: string, 1: int, 2: int}
     */
    protected function haveScheduleWithSeededQuoteData(array $quoteData): array
    {
        $customerTransfer = $this->tester->haveCustomer();
        $this->tester->pinMailFacadeDependency();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule((int)$customerTransfer->getIdCustomer(), [
            RecurringScheduleTransfer::STATUS => SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED,
            RecurringScheduleTransfer::PRICE_MODE => static::PRICE_MODE_GROSS,
            RecurringScheduleTransfer::QUOTE_DATA => json_encode($quoteData, JSON_THROW_ON_ERROR),
            static::OVERRIDE_BUILD_QUOTE_DATA => true,
        ]);
        $idRecurringSchedule = $recurringScheduleTransfer->getIdRecurringScheduleOrFail();

        $this->tester->haveRecurringScheduleItem($idRecurringSchedule, [
            RecurringScheduleItemTransfer::SKU => static::SKU_A,
            RecurringScheduleItemTransfer::QUANTITY => 1,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
            RecurringScheduleItemTransfer::GROUP_KEY => static::GROUP_KEY_A,
            static::OVERRIDE_BUILD_ITEM_DATA => true,
        ]);

        return [$recurringScheduleTransfer->getUuidOrFail(), (int)$customerTransfer->getIdCustomer(), $idRecurringSchedule];
    }

    /**
     * @param int $idCustomer
     * @param int $idRecurringSchedule
     *
     * @return array<string, mixed>
     */
    protected function findScheduleQuoteData(int $idCustomer, int $idRecurringSchedule): array
    {
        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addCustomerId($idCustomer)
                    ->addIdRecurringSchedule($idRecurringSchedule),
            );

        $recurringScheduleTransfer = $this->tester->getFacade()
            ->getRecurringScheduleCollection($criteriaTransfer)
            ->getRecurringSchedules()
            ->offsetGet(0);

        return (array)json_decode($recurringScheduleTransfer->getQuoteDataOrFail(), true, 512, JSON_THROW_ON_ERROR);
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
