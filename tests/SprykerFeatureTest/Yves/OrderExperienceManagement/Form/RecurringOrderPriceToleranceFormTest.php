<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Form;

use Codeception\Test\Unit;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderPriceToleranceForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderPriceToleranceItemForm;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Form
 * @group RecurringOrderPriceToleranceFormTest
 */
class RecurringOrderPriceToleranceFormTest extends Unit
{
    public function testAcceptsABlankProductToleranceWhileProductPriceChangesAreIgnored(): void
    {
        // Act
        $form = $this->submit(true, '');

        // Assert
        $this->assertTrue($form->isValid());
    }

    public function testRejectsABlankProductToleranceWhileProductPriceChangesAreChecked(): void
    {
        // Act
        $form = $this->submit(false, '');

        // Assert
        $this->assertFalse($form->isValid());
    }

    public function testStillValidatesTheOrderToleranceWhileProductPriceChangesAreIgnored(): void
    {
        // Act
        $form = $this->submit(true, '', '1000');

        // Assert
        $this->assertFalse($form->isValid());
    }

    public function testRejectsAnOrderToleranceWithMoreDecimalsThanTheScaleAllows(): void
    {
        // Act
        $form = $this->submit(false, '1', '1.234');

        // Assert
        $this->assertFalse($form->isValid());
    }

    public function testAcceptsTheSmallestStepAndTheMaximum(): void
    {
        // Act
        $form = $this->submit(false, '0.01', '999.99');

        // Assert
        $this->assertTrue($form->isValid());
    }

    public function testRejectsItemEntriesThatAreNotPartOfTheFormData(): void
    {
        // Act
        $form = $this->createForm();
        $form->submit([
            RecurringOrderPriceToleranceForm::FIELD_UUID => 'schedule-uuid',
            RecurringOrderPriceToleranceForm::FIELD_ORDER_TOLERANCE => '5',
            RecurringOrderPriceToleranceForm::FIELD_ITEMS => [
                0 => [RecurringOrderPriceToleranceItemForm::FIELD_GROUP_KEY => 'group-a', RecurringOrderPriceToleranceItemForm::FIELD_TOLERANCE => '1'],
                1 => [RecurringOrderPriceToleranceItemForm::FIELD_GROUP_KEY => 'injected', RecurringOrderPriceToleranceItemForm::FIELD_TOLERANCE => '50'],
            ],
        ]);

        // Assert
        $this->assertFalse($form->isValid());
    }

    protected function submit(bool $isIgnored, string $itemTolerance, string $orderTolerance = '5'): FormInterface
    {
        $formData = [
            RecurringOrderPriceToleranceForm::FIELD_UUID => 'schedule-uuid',
            RecurringOrderPriceToleranceForm::FIELD_ORDER_TOLERANCE => $orderTolerance,
            RecurringOrderPriceToleranceForm::FIELD_ITEMS => [
                0 => [RecurringOrderPriceToleranceItemForm::FIELD_GROUP_KEY => 'group-a', RecurringOrderPriceToleranceItemForm::FIELD_TOLERANCE => $itemTolerance],
            ],
        ];

        if ($isIgnored) {
            $formData[RecurringOrderPriceToleranceForm::FIELD_IGNORE_PRODUCT_PRICE_CHANGES] = '1';
        }

        $form = $this->createForm();
        $form->submit($formData);

        return $form;
    }

    protected function createForm(): FormInterface
    {
        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();

        return $formFactory->create(RecurringOrderPriceToleranceForm::class, [
            RecurringOrderPriceToleranceForm::FIELD_UUID => 'schedule-uuid',
            RecurringOrderPriceToleranceForm::FIELD_ORDER_TOLERANCE => '5',
            RecurringOrderPriceToleranceForm::FIELD_IGNORE_PRODUCT_PRICE_CHANGES => false,
            RecurringOrderPriceToleranceForm::FIELD_ITEMS => [
                [RecurringOrderPriceToleranceItemForm::FIELD_GROUP_KEY => 'group-a', RecurringOrderPriceToleranceItemForm::FIELD_BUNDLE_ITEM_IDENTIFIER => null, RecurringOrderPriceToleranceItemForm::FIELD_TOLERANCE => '1'],
            ],
        ]);
    }
}
