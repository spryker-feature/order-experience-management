import Component from 'ShopUi/models/component';

export const EVENT_BULK_APPLY = 'price-tolerance-bulk-apply';
export const EVENT_BULK_TURN_OFF = 'price-tolerance-bulk-turn-off';

export default class PriceToleranceBulkBar extends Component {
    protected valueInput: HTMLInputElement | null;

    protected init(): void {
        this.valueInput = this.querySelector(`.${this.jsName}__value`);

        this.mapEvents();
    }

    protected mapEvents(): void {
        this.querySelector(`.${this.jsName}__apply`)?.addEventListener('click', () => this.apply());
        this.querySelector(`.${this.jsName}__turn-off`)?.addEventListener('click', () => this.reset());

        this.valueInput?.addEventListener('keydown', (event: KeyboardEvent) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                this.apply();
            }
        });
    }

    setCount(count: number): void {
        this.classList.toggle(this.getAttribute('hidden-class'), count === 0);

        const countLabel = this.querySelector(`.${this.jsName}__count`);

        if (countLabel) {
            countLabel.textContent = this.formatCount(count);
        }
    }

    protected reset() {
        this.dispatchEvent(new CustomEvent(EVENT_BULK_TURN_OFF));
    }

    protected apply(): void {
        if (!this.valueInput) {
            return;
        }

        if (this.valueInput.value === '') {
            this.valueInput.focus();

            return;
        }

        if (!this.valueInput.reportValidity()) {
            return;
        }

        this.dispatchEvent(new CustomEvent<string>(EVENT_BULK_APPLY, { detail: this.valueInput.value }));
    }

    protected formatCount(count: number): string {
        if (count === 1) {
            return this.getAttribute('selected-one-label') ?? '';
        }

        return (this.getAttribute('selected-many-label') ?? '').replace('%count%', String(count));
    }
}
