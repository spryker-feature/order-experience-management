<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Form;

use DateTimeImmutable;
use Generated\Shared\Transfer\RecurringOrderSettingsTransfer;
use Spryker\Yves\Kernel\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class RecurringOrderSelectorForm extends AbstractType
{
    public const string FIELD_CADENCE_TYPE = 'cadenceType';

    public const string FIELD_CADENCE_VALUE = 'cadenceValue';

    public const string FIELD_SCHEDULE_NAME = 'scheduleName';

    public const string FIELD_START_DATE = 'startDate';

    public const string OPTION_CADENCE_TYPE_CHOICES = 'cadence_type_choices';

    protected const string DATE_FORMAT = 'Y-m-d';

    public function getBlockPrefix(): string
    {
        return '';
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addScheduleNameField($builder)
            ->addCadenceTypeField($builder, $options)
            ->addCadenceValueField($builder)
            ->addStartDateField($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => RecurringOrderSettingsTransfer::class]);
        $resolver->setRequired([static::OPTION_CADENCE_TYPE_CHOICES]);
        $resolver->setAllowedTypes(static::OPTION_CADENCE_TYPE_CHOICES, 'array');
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function addCadenceTypeField(FormBuilderInterface $builder, array $options): static
    {
        $choices = $options[static::OPTION_CADENCE_TYPE_CHOICES];

        $builder->add(static::FIELD_CADENCE_TYPE, ChoiceType::class, [
            'choices' => $choices,
            'label' => 'recurring_orders.checkout.cadence_label',
            'required' => true,
            'placeholder' => 'recurring_orders.checkout.cadence_placeholder',
            'constraints' => [
                new NotBlank(['message' => 'recurring_orders.checkout.validation.cadence_required']),
                new Choice(['choices' => array_values($choices)]),
            ],
        ]);

        return $this;
    }

    protected function addCadenceValueField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_CADENCE_VALUE, IntegerType::class, [
            'label' => 'recurring_orders.checkout.cadence_value_label',
            'required' => false,
            'constraints' => [
                new Range(['min' => 1]),
            ],
        ]);

        return $this;
    }

    protected function addScheduleNameField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_SCHEDULE_NAME, TextType::class, [
            'label' => 'recurring_orders.checkout.schedule_name_label',
            'required' => false,
            'sanitize_xss' => true,
            'constraints' => [
                new Length(['max' => 255]),
            ],
        ]);

        return $this;
    }

    protected function addStartDateField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_START_DATE, DateType::class, [
            'widget' => 'single_text',
            'input' => 'string',
            'input_format' => static::DATE_FORMAT,
            'required' => true,
            'label' => 'recurring_orders.checkout.start_date_label',
            'constraints' => [
                new NotBlank(['message' => 'recurring_orders.checkout.validation.start_date_required']),
                // The field binds to a string transfer property, so the native GreaterThanOrEqual
                // constraint cannot compare it against "today" — validate the parsed date here instead.
                new Callback([$this, 'validateStartDateNotInPast']),
            ],
        ]);

        return $this;
    }

    public function validateStartDateNotInPast(?string $startDate, ExecutionContextInterface $context): void
    {
        // An empty value is already reported by NotBlank; nothing to compare here.
        if ($startDate === null || $startDate === '') {
            return;
        }

        $startDateTime = DateTimeImmutable::createFromFormat('!' . static::DATE_FORMAT, $startDate);

        // An unparseable value is not this constraint's concern.
        if ($startDateTime === false) {
            return;
        }

        if ($startDateTime < new DateTimeImmutable('today')) {
            $context->buildViolation('recurring_orders.checkout.validation.start_date_in_past')
                ->addViolation();
        }
    }
}
