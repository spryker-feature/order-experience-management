<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Validator;

use Codeception\Test\Unit;
use DateTimeImmutable;
use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringScheduleEditForm;
use SprykerFeature\Yves\OrderExperienceManagement\Validator\CadenceFormValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Validator
 * @group CadenceFormValidatorTest
 * Add your own group annotations below this line
 */
class CadenceFormValidatorTest extends Unit
{
    protected const string DATE_FORMAT = 'Y-m-d';

    protected const string GLOSSARY_KEY_DAYS_REQUIRED = 'recurring_orders.cadence.validation.days_required';

    protected const string GLOSSARY_KEY_DATE_NOT_SELECTED_WEEK_DAY = 'recurring_orders.cadence.validation.date_not_selected_week_day';

    public function testAddsViolationWhenByDaysIsSelectedWithoutAnyDay(): void
    {
        // Arrange
        $recurringOrderSettingsTransfer = (new RecurringOrderSettingsTransfer())
            ->setCadenceType(SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS)
            ->setCadenceOptions([]);

        // Act
        $violatedGlossaryKeys = $this->validateCheckout($recurringOrderSettingsTransfer);

        // Assert
        $this->assertSame([static::GLOSSARY_KEY_DAYS_REQUIRED], $violatedGlossaryKeys);
    }

    public function testAddsViolationWhenTheFutureStartDateIsNotOneOfTheSelectedWeekDays(): void
    {
        // Arrange
        $futureSaturday = (new DateTimeImmutable('next saturday'))->modify('+1 week');

        $recurringOrderSettingsTransfer = (new RecurringOrderSettingsTransfer())
            ->setCadenceType(SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS)
            ->setCadenceOptions([
                (string)SharedOrderExperienceManagementConfig::DAY_TUESDAY,
                (string)SharedOrderExperienceManagementConfig::DAY_FRIDAY,
            ])
            ->setStartDate($futureSaturday->format(static::DATE_FORMAT));

        // Act
        $violatedGlossaryKeys = $this->validateCheckout($recurringOrderSettingsTransfer);

        // Assert
        $this->assertSame([static::GLOSSARY_KEY_DATE_NOT_SELECTED_WEEK_DAY], $violatedGlossaryKeys);
    }

    public function testAcceptsAFutureStartDateThatFallsOnASelectedWeekDay(): void
    {
        // Arrange
        $futureTuesday = (new DateTimeImmutable('next tuesday'))->modify('+1 week');

        $recurringOrderSettingsTransfer = (new RecurringOrderSettingsTransfer())
            ->setCadenceType(SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS)
            ->setCadenceOptions([
                (string)SharedOrderExperienceManagementConfig::DAY_TUESDAY,
                (string)SharedOrderExperienceManagementConfig::DAY_FRIDAY,
            ])
            ->setStartDate($futureTuesday->format(static::DATE_FORMAT));

        // Act
        $violatedGlossaryKeys = $this->validateCheckout($recurringOrderSettingsTransfer);

        // Assert
        $this->assertSame([], $violatedGlossaryKeys);
    }

    public function testAcceptsTodayAsStartDateEvenWhenItIsNotASelectedWeekDay(): void
    {
        // Arrange
        $today = new DateTimeImmutable('today');
        $notTodaysWeekDay = (string)((((int)$today->format('N')) % 7) + 1);

        $recurringOrderSettingsTransfer = (new RecurringOrderSettingsTransfer())
            ->setCadenceType(SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS)
            ->setCadenceOptions([$notTodaysWeekDay])
            ->setStartDate($today->format(static::DATE_FORMAT));

        // Act
        $violatedGlossaryKeys = $this->validateCheckout($recurringOrderSettingsTransfer);

        // Assert
        $this->assertSame([], $violatedGlossaryKeys);
    }

    public function testIgnoresTheStartDateForCadenceTypesOtherThanByDays(): void
    {
        // Arrange
        $futureSaturday = (new DateTimeImmutable('next saturday'))->modify('+1 week');

        $recurringOrderSettingsTransfer = (new RecurringOrderSettingsTransfer())
            ->setCadenceType(SharedOrderExperienceManagementConfig::CADENCE_TYPE_WEEKLY)
            ->setStartDate($futureSaturday->format(static::DATE_FORMAT));

        // Act
        $violatedGlossaryKeys = $this->validateCheckout($recurringOrderSettingsTransfer);

        // Assert
        $this->assertSame([], $violatedGlossaryKeys);
    }

    public function testAddsViolationWhenTheEditedExecutionDateIsNotOneOfTheSelectedWeekDays(): void
    {
        // Arrange
        $futureSaturday = (new DateTimeImmutable('next saturday'))->modify('+1 week');

        $formData = [
            RecurringScheduleEditForm::FIELD_CADENCE_TYPE => SharedOrderExperienceManagementConfig::CADENCE_TYPE_BY_DAYS,
            RecurringScheduleEditForm::FIELD_CADENCE_OPTIONS => [
                (string)SharedOrderExperienceManagementConfig::DAY_TUESDAY,
            ],
            RecurringScheduleEditForm::FIELD_NEXT_EXECUTION_DATE => $futureSaturday,
        ];

        // Act
        $violatedGlossaryKeys = [];
        (new CadenceFormValidator())->validateScheduleEditCadence(
            $formData,
            $this->createExecutionContextMock($violatedGlossaryKeys),
        );

        // Assert
        $this->assertSame([static::GLOSSARY_KEY_DATE_NOT_SELECTED_WEEK_DAY], $violatedGlossaryKeys);
    }

    /**
     * @return array<string>
     */
    protected function validateCheckout(RecurringOrderSettingsTransfer $recurringOrderSettingsTransfer): array
    {
        $violatedGlossaryKeys = [];

        (new CadenceFormValidator())->validateCheckoutCadence(
            $recurringOrderSettingsTransfer,
            $this->createExecutionContextMock($violatedGlossaryKeys),
        );

        return $violatedGlossaryKeys;
    }

    /**
     * @param array<string> $violatedGlossaryKeys
     */
    protected function createExecutionContextMock(array &$violatedGlossaryKeys): ExecutionContextInterface
    {
        $constraintViolationBuilderMock = $this->createMock(ConstraintViolationBuilderInterface::class);
        $constraintViolationBuilderMock->method('atPath')->willReturnSelf();

        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $executionContextMock->method('buildViolation')->willReturnCallback(
            function (string $glossaryKey) use (&$violatedGlossaryKeys, $constraintViolationBuilderMock) {
                $violatedGlossaryKeys[] = $glossaryKey;

                return $constraintViolationBuilderMock;
            },
        );

        return $executionContextMock;
    }
}
