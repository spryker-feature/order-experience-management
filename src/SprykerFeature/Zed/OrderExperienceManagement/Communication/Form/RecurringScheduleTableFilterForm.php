<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Form;

use Generated\Shared\Transfer\RecurringScheduleTableFilterTransfer;
use Spryker\Zed\Gui\Communication\Form\Type\Select2ComboBoxType;
use Spryker\Zed\Kernel\Communication\Form\AbstractType;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Form\DataProvider\RecurringScheduleTableFilterFormDataProvider;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Communication\OrderExperienceManagementCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class RecurringScheduleTableFilterForm extends AbstractType
{
    public const string BLOCK_PREFIX = 'recurringScheduleTableFilter';

    public const string FIELD_ID_COMPANY = 'idCompany';

    public const string FIELD_ID_COMPANY_BUSINESS_UNIT = 'idCompanyBusinessUnit';

    public const string FIELD_STATUSES = 'statuses';

    public const string FIELD_CADENCE_TYPES = 'cadenceTypes';

    public const string FIELD_CYCLE_TOTAL_FROM = 'cycleTotalFrom';

    public const string FIELD_CYCLE_TOTAL_TO = 'cycleTotalTo';

    public const string FIELD_NEXT_TRIGGER_DATE_FROM = 'nextTriggerDateFrom';

    public const string FIELD_NEXT_TRIGGER_DATE_TO = 'nextTriggerDateTo';

    protected const string COMPANY_FIELD_SELECTOR = '#recurringScheduleTableFilter_idCompany';

    /**
     * @uses \Spryker\Zed\CompanyGui\Communication\Controller\SuggestController::indexAction()
     */
    protected const string ROUTE_COMPANY_SUGGEST = '/company-gui/suggest';

    /**
     * @uses \Spryker\Zed\CompanyBusinessUnitGui\Communication\Controller\SuggestController::indexAction()
     */
    protected const string ROUTE_COMPANY_BUSINESS_UNIT_SUGGEST = '/company-business-unit-gui/suggest';

    protected const int MONEY_DIVISOR = 100;

    public function getBlockPrefix(): string
    {
        return static::BLOCK_PREFIX;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            RecurringScheduleTableFilterFormDataProvider::OPTION_STATUSES,
            RecurringScheduleTableFilterFormDataProvider::OPTION_CADENCE_TYPES,
            RecurringScheduleTableFilterFormDataProvider::OPTION_COMPANY_CHOICES,
            RecurringScheduleTableFilterFormDataProvider::OPTION_COMPANY_BUSINESS_UNIT_CHOICES,
        ]);

        $resolver->setDefaults([
            'data_class' => RecurringScheduleTableFilterTransfer::class,
            'csrf_protection' => false,
            'required' => false,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setMethod(Request::METHOD_GET);

        $this
            ->addCompanyField($builder, $options)
            ->addCompanyBusinessUnitField($builder, $options)
            ->addStatusesField($builder, $options)
            ->addCadenceTypesField($builder, $options)
            ->addCycleTotalFromField($builder)
            ->addCycleTotalToField($builder)
            ->addNextTriggerDateFromField($builder)
            ->addNextTriggerDateToField($builder)
            ->addPreSubmitEventListener($builder);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    protected function addCompanyField(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            static::FIELD_ID_COMPANY,
            Select2ComboBoxType::class,
            $this->getCompanyFieldParameters($options[RecurringScheduleTableFilterFormDataProvider::OPTION_COMPANY_CHOICES]),
        );

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    protected function addCompanyBusinessUnitField(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            static::FIELD_ID_COMPANY_BUSINESS_UNIT,
            Select2ComboBoxType::class,
            $this->getCompanyBusinessUnitFieldParameters($options[RecurringScheduleTableFilterFormDataProvider::OPTION_COMPANY_BUSINESS_UNIT_CHOICES]),
        );

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    protected function addStatusesField(FormBuilderInterface $builder, array $options)
    {
        $builder->add(static::FIELD_STATUSES, ChoiceType::class, [
            'label' => 'Status',
            'placeholder' => 'Select Statuses',
            'required' => false,
            'multiple' => true,
            'expanded' => false,
            'choices' => $options[RecurringScheduleTableFilterFormDataProvider::OPTION_STATUSES],
            'choice_translation_domain' => true,
            'attr' => [
                'class' => 'spryker-form-select2combobox',
                'data-clearable' => true,
                'data-placeholder' => 'Select Statuses',
            ],
        ]);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return $this
     */
    protected function addCadenceTypesField(FormBuilderInterface $builder, array $options)
    {
        $builder->add(static::FIELD_CADENCE_TYPES, ChoiceType::class, [
            'label' => 'Frequency',
            'placeholder' => 'Select Frequency',
            'required' => false,
            'multiple' => true,
            'expanded' => false,
            'choices' => $options[RecurringScheduleTableFilterFormDataProvider::OPTION_CADENCE_TYPES],
            'choice_translation_domain' => true,
            'attr' => [
                'class' => 'spryker-form-select2combobox',
                'data-clearable' => true,
                'data-placeholder' => 'Select Frequency',
            ],
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    protected function addCycleTotalFromField(FormBuilderInterface $builder)
    {
        $this->addMoneyField($builder, static::FIELD_CYCLE_TOTAL_FROM, 'Cycle total from');

        return $this;
    }

    /**
     * @return $this
     */
    protected function addCycleTotalToField(FormBuilderInterface $builder)
    {
        $this->addMoneyField($builder, static::FIELD_CYCLE_TOTAL_TO, 'Cycle total to');

        return $this;
    }

    protected function addMoneyField(FormBuilderInterface $builder, string $fieldName, string $label): void
    {
        $builder->add($fieldName, MoneyType::class, [
            'label' => $label,
            'required' => false,
            'currency' => false,
            'divisor' => static::MONEY_DIVISOR,
            'attr' => [
                'placeholder' => $label,
            ],
        ]);

        $builder->get($fieldName)->addModelTransformer(new CallbackTransformer(
            fn ($modelValue) => $modelValue,
            fn ($normValue) => $normValue === null ? null : (int)round((float)$normValue),
        ));
    }

    /**
     * @return $this
     */
    protected function addNextTriggerDateFromField(FormBuilderInterface $builder)
    {
        $this->addDateField($builder, static::FIELD_NEXT_TRIGGER_DATE_FROM, 'Next trigger date from');

        return $this;
    }

    /**
     * @return $this
     */
    protected function addNextTriggerDateToField(FormBuilderInterface $builder)
    {
        $this->addDateField($builder, static::FIELD_NEXT_TRIGGER_DATE_TO, 'Next trigger date to');

        return $this;
    }

    protected function addDateField(FormBuilderInterface $builder, string $fieldName, string $label): void
    {
        $builder->add($fieldName, DateType::class, [
            'label' => $label,
            'required' => false,
            'widget' => 'single_text',
            'html5' => true,
            'input' => 'string',
        ]);
    }

    protected function addPreSubmitEventListener(FormBuilderInterface $builder): void
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $formEvent): void {
            $this->reAddAutocompleteFields($formEvent);
        });
    }

    protected function reAddAutocompleteFields(FormEvent $formEvent): void
    {
        $data = $formEvent->getData();
        $form = $formEvent->getForm();
        $dataProvider = $this->getFactory()->createRecurringScheduleTableFilterFormDataProvider();

        if (isset($data[static::FIELD_ID_COMPANY]) && $data[static::FIELD_ID_COMPANY] !== '') {
            $companyChoices = $dataProvider->getCompanyChoices((int)$data[static::FIELD_ID_COMPANY]);
            $form->add(static::FIELD_ID_COMPANY, Select2ComboBoxType::class, $this->getCompanyFieldParameters($companyChoices));
        }

        if (isset($data[static::FIELD_ID_COMPANY_BUSINESS_UNIT]) && $data[static::FIELD_ID_COMPANY_BUSINESS_UNIT] !== '') {
            $companyBusinessUnitChoices = $dataProvider->getCompanyBusinessUnitChoices((int)$data[static::FIELD_ID_COMPANY_BUSINESS_UNIT]);
            $form->add(static::FIELD_ID_COMPANY_BUSINESS_UNIT, Select2ComboBoxType::class, $this->getCompanyBusinessUnitFieldParameters($companyBusinessUnitChoices));
        }
    }

    /**
     * @param array<string, int> $companyChoices
     *
     * @return array<string, mixed>
     */
    protected function getCompanyFieldParameters(array $companyChoices): array
    {
        return [
            'label' => 'Company',
            'placeholder' => 'Company',
            'choices' => $companyChoices,
            'required' => false,
            'attr' => [
                'data-minimum-input-length' => 2,
                'data-autocomplete-url' => static::ROUTE_COMPANY_SUGGEST,
                'data-clearable' => true,
            ],
        ];
    }

    /**
     * @param array<string, int> $companyBusinessUnitChoices
     *
     * @return array<string, mixed>
     */
    protected function getCompanyBusinessUnitFieldParameters(array $companyBusinessUnitChoices): array
    {
        return [
            'label' => 'Business Unit',
            'placeholder' => 'Business Unit',
            'choices' => $companyBusinessUnitChoices,
            'required' => false,
            'attr' => [
                'data-depends-on-field' => static::COMPANY_FIELD_SELECTOR,
                'data-dependent-autocomplete-key' => 'idCompany',
                'data-minimum-input-length' => 2,
                'data-autocomplete-url' => static::ROUTE_COMPANY_BUSINESS_UNIT_SUGGEST,
                'data-dependent-disable-when-empty' => true,
                'data-dependent-reset-on-change' => true,
                'data-clearable' => true,
            ],
        ];
    }
}
