<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Form;

use SprykerFeature\Yves\OrderExperienceManagement\Form\Constraint\PriceToleranceConstraint;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class RecurringOrderPriceToleranceForm extends AbstractRecurringOrderUuidForm
{
    public const string FORM_NAME = 'recurringOrderPriceToleranceForm';

    public const string FIELD_ORDER_TOLERANCE = 'orderTolerance';

    public const string FIELD_ITEMS = 'items';

    public const string FIELD_IGNORE_PRODUCT_PRICE_CHANGES = 'isProductPriceChangeIgnored';

    public const string GLOSSARY_KEY_BLANK = 'recurring_orders.price_tolerance.form.error.blank';

    public const string GLOSSARY_KEY_RANGE = 'recurring_orders.price_tolerance.form.error.range';

    public const string GLOSSARY_KEY_DECIMALS = 'recurring_orders.price_tolerance.form.error.decimals';

    public const string GLOSSARY_KEY_STEP = 'recurring_orders.price_tolerance.form.error.step';

    public const string VALIDATION_GROUP_PRODUCT_TOLERANCES = 'product_tolerances';

    public function getBlockPrefix(): string
    {
        return static::FORM_NAME;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('validation_groups', static function (FormInterface $form): array {
            $formData = $form->getData();
            $isIgnored = is_array($formData) && !empty($formData[static::FIELD_IGNORE_PRODUCT_PRICE_CHANGES]);

            return $isIgnored ? ['Default'] : ['Default', static::VALIDATION_GROUP_PRODUCT_TOLERANCES];
        });
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addUuidField($builder, true);

        $builder->add(static::FIELD_ORDER_TOLERANCE, NumberType::class, [
            'required' => false,
            'label' => 'recurring_orders.price_tolerance.order_total.field_label',
            'input' => 'string',
            'html5' => true,
            'invalid_message' => static::GLOSSARY_KEY_RANGE,
            'invalid_message_parameters' => ['{{ max }}' => $this->getConfig()->getPriceToleranceMaxPercent()],
            'attr' => [
                'min' => '0',
                'max' => $this->getConfig()->getPriceToleranceMaxPercent(),
                'step' => $this->getConfig()->getPriceToleranceStep(),
                'inputmode' => 'decimal',
                'placeholder' => '',
            ],
            'constraints' => [
                new NotBlank(['message' => static::GLOSSARY_KEY_BLANK]),
                new PriceToleranceConstraint(
                    $this->getConfig()->getPriceToleranceScale(),
                    $this->getConfig()->getPriceToleranceStep(),
                    static::GLOSSARY_KEY_DECIMALS,
                    static::GLOSSARY_KEY_STEP,
                ),
                new Range([
                    'min' => 0,
                    'max' => (float)$this->getConfig()->getPriceToleranceMaxPercent(),
                    'notInRangeMessage' => static::GLOSSARY_KEY_RANGE,
                ]),
            ],
        ]);

        $builder->add(static::FIELD_IGNORE_PRODUCT_PRICE_CHANGES, CheckboxType::class, [
            'required' => false,
            'label' => 'recurring_orders.price_tolerance.ignore_products.label',
        ]);

        $builder->add(static::FIELD_ITEMS, CollectionType::class, [
            'required' => false,
            'label' => false,
            'allow_add' => false,
            'allow_delete' => false,
            'entry_type' => RecurringOrderPriceToleranceItemForm::class,
            'entry_options' => ['label' => false],
        ]);
    }
}
