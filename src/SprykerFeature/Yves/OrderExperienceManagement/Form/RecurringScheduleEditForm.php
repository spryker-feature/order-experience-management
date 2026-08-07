<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Form;

use DateTimeImmutable;
use DateTimeInterface;
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

/**
 * Edit form for a persisted recurring schedule shown in a popup on the detail page. Carries the schedule
 * UUID plus the editable name, cadence and next execution date; cost center and budget fields are added
 * through the edit-form expander plugin stack.
 *
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class RecurringScheduleEditForm extends AbstractRecurringOrderUuidForm
{
    public const string FORM_NAME = 'recurringScheduleEditForm';

    public const string FIELD_NAME = 'name';

    public const string FIELD_CADENCE_TYPE = 'cadenceType';

    public const string FIELD_CADENCE_VALUE = 'cadenceValue';

    public const string FIELD_NEXT_EXECUTION_DATE = 'nextExecutionDate';

    public const string OPTION_RECURRING_SCHEDULE = 'recurringSchedule';

    public function getBlockPrefix(): string
    {
        return static::FORM_NAME;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            static::OPTION_RECURRING_SCHEDULE => null,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addUuidField($builder, true)
            ->addNameField($builder)
            ->addCadenceTypeField($builder)
            ->addCadenceValueField($builder)
            ->addNextExecutionDateField($builder);

        $this->addFormExpanderFields($builder, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function addFormExpanderFields(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->getFactory()->getRecurringScheduleEditFormExpanderPlugins() as $recurringScheduleEditFormExpanderPlugin) {
            $recurringScheduleEditFormExpanderPlugin->expandForm($builder, $options);
        }
    }

    protected function addNameField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_NAME, TextType::class, [
            'label' => 'recurring_orders.detail.edit.name_label',
            'required' => true,
            'sanitize_xss' => true,
            'constraints' => [
                new NotBlank(['message' => 'recurring_orders.detail.edit.validation.name_required']),
                new Length(['max' => 255]),
            ],
        ]);

        return $this;
    }

    protected function addCadenceTypeField(FormBuilderInterface $builder): static
    {
        $choices = $this->getConfig()->getSupportedCadenceTypes();

        $builder->add(static::FIELD_CADENCE_TYPE, ChoiceType::class, [
            'choices' => $choices,
            'label' => 'recurring_orders.detail.edit.frequency_label',
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
            'label' => 'recurring_orders.detail.edit.cadence_value_label',
            'required' => false,
            'constraints' => [
                new Range(['min' => 1]),
            ],
        ]);

        return $this;
    }

    protected function addNextExecutionDateField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_NEXT_EXECUTION_DATE, DateType::class, [
            'widget' => 'single_text',
            'input' => 'datetime',
            'required' => true,
            'label' => 'recurring_orders.detail.edit.starting_date_label',
            'constraints' => [
                new NotBlank(['message' => 'recurring_orders.detail.edit.validation.starting_date_required']),
                new Callback([$this, 'validateNextExecutionDateNotInPast']),
            ],
        ]);

        return $this;
    }

    public function validateNextExecutionDateNotInPast(?DateTimeInterface $nextExecutionDate, ExecutionContextInterface $context): void
    {
        if ($nextExecutionDate === null) {
            return;
        }

        if ($nextExecutionDate < new DateTimeImmutable('today')) {
            $context->buildViolation('recurring_orders.detail.edit.validation.starting_date_in_past')
                ->addViolation();
        }
    }
}
