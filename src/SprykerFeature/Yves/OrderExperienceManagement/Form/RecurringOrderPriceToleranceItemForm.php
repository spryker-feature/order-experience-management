<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Form;

use Spryker\Yves\Kernel\Form\AbstractType;
use SprykerFeature\Yves\OrderExperienceManagement\Form\Constraint\PriceToleranceConstraint;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class RecurringOrderPriceToleranceItemForm extends AbstractType
{
    public const string FORM_NAME = 'recurringOrderPriceToleranceItemForm';

    public const string FIELD_GROUP_KEY = 'groupKey';

    public const string FIELD_BUNDLE_ITEM_IDENTIFIER = 'bundleItemIdentifier';

    public const string FIELD_TOLERANCE = 'tolerance';

    public function getBlockPrefix(): string
    {
        return static::FORM_NAME;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(static::FIELD_GROUP_KEY, HiddenType::class, ['required' => false, 'label' => false]);
        $builder->add(static::FIELD_BUNDLE_ITEM_IDENTIFIER, HiddenType::class, ['required' => false, 'label' => false]);

        $builder->add(static::FIELD_TOLERANCE, NumberType::class, [
            'required' => false,
            'label' => false,
            'input' => 'string',
            'html5' => true,
            'invalid_message' => RecurringOrderPriceToleranceForm::GLOSSARY_KEY_RANGE,
            'invalid_message_parameters' => ['{{ max }}' => $this->getConfig()->getPriceToleranceMaxPercent()],
            'attr' => [
                'min' => '0',
                'max' => $this->getConfig()->getPriceToleranceMaxPercent(),
                'step' => $this->getConfig()->getPriceToleranceStep(),
                'data-scale' => $this->getConfig()->getPriceToleranceScale(),
                'inputmode' => 'decimal',
                'placeholder' => '',
            ],
            'constraints' => [
                new NotBlank(['message' => RecurringOrderPriceToleranceForm::GLOSSARY_KEY_BLANK, 'groups' => [RecurringOrderPriceToleranceForm::VALIDATION_GROUP_PRODUCT_TOLERANCES]]),
                new PriceToleranceConstraint(
                    $this->getConfig()->getPriceToleranceScale(),
                    $this->getConfig()->getPriceToleranceStep(),
                    RecurringOrderPriceToleranceForm::GLOSSARY_KEY_DECIMALS,
                    RecurringOrderPriceToleranceForm::GLOSSARY_KEY_STEP,
                    [RecurringOrderPriceToleranceForm::VALIDATION_GROUP_PRODUCT_TOLERANCES],
                ),
                new Range([
                    'min' => 0,
                    'max' => (float)$this->getConfig()->getPriceToleranceMaxPercent(),
                    'notInRangeMessage' => RecurringOrderPriceToleranceForm::GLOSSARY_KEY_RANGE,
                    'groups' => [RecurringOrderPriceToleranceForm::VALIDATION_GROUP_PRODUCT_TOLERANCES],
                ]),
            ],
        ]);
    }
}
