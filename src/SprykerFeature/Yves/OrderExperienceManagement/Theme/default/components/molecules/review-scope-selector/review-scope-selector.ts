import Component from 'ShopUi/models/component';

export default class ReviewScopeSelector extends Component {
    protected targetInput: HTMLInputElement | null;
    protected radioSelector = '';

    protected init(): void {
        const inputSelector = this.getAttribute('input-selector');

        if (!inputSelector) {
            return;
        }

        this.targetInput = document.querySelector<HTMLInputElement>(inputSelector);
        this.radioSelector = `.${this.jsName}__radio`;

        if (!this.targetInput || !this.querySelector(this.radioSelector)) {
            return;
        }

        this.syncTargetValue();
        this.mapEvents();
    }

    protected mapEvents(): void {
        // The radios are rendered inside a main-popup, whose content is cloned to the end of <body> when the
        // popup opens. A listener bound to this component's own children would never see the clicked clone,
        // so delegation on document is used to catch changes from whichever copy is currently interacted with.
        document.addEventListener('change', (event: Event) => this.onRadioChange(event));
    }

    protected onRadioChange(event: Event): void {
        const target = event.target as HTMLElement;
        const radio = target.closest<HTMLInputElement>(this.radioSelector);

        if (!radio || !this.targetInput) {
            return;
        }

        this.targetInput.value = radio.value;
    }

    protected syncTargetValue(): void {
        const checkedRadio = this.querySelector<HTMLInputElement>(`${this.radioSelector}:checked`);

        if (!checkedRadio || !this.targetInput) {
            return;
        }

        this.targetInput.value = checkedRadio.value;
    }
}
