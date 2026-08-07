import Component from 'ShopUi/models/component';
import { EVENT_FORMATTED_NUMBER } from 'ShopUi/components/molecules/formatted-number-input/formatted-number-input';
import { LINE_ITEMS_UPDATE_EVENT } from '../review-summary-banner-price/review-sumary-banner-price';

const ARROW_DOWN_KEY = 'ArrowDown';

export default class ReviewQuantityControl extends Component {
    protected input: HTMLInputElement | null;
    protected acceptedQuantityInput: HTMLInputElement | null;
    protected removeInput: HTMLInputElement | null;
    protected removeToggleButton: HTMLButtonElement | null;

    protected init(): void {
        this.input = this.querySelector(`.${this.jsName}__input`);
        this.acceptedQuantityInput = this.querySelector(`.${this.jsName}__accepted-quantity-input`);
        this.removeInput = this.querySelector(`.${this.jsName}__remove-input`);
        this.removeToggleButton = this.querySelector(`.${this.jsName}__remove-toggle`);

        this.mapEvents();
        this.syncRemovedState();
        this.updateLineTotal();
    }

    protected mapEvents(): void {
        this.removeToggleButton?.addEventListener('click', () => this.toggleRemoved());
        this.input?.addEventListener('input', () => {
            this.syncAcceptedQuantity();
            this.notifyLineChanged();
        });

        this.input?.addEventListener(EVENT_FORMATTED_NUMBER, () => {
            this.syncAcceptedQuantity();
            this.notifyLineChanged();
        });

        this.addEventListener('keydown', (event: KeyboardEvent) => this.preventDecrementBelowMinimum(event), true);
    }

    protected preventDecrementBelowMinimum(event: KeyboardEvent): void {
        if (event.key !== ARROW_DOWN_KEY || event.target !== this.input) {
            return;
        }

        const currentQuantity = Number(this.input?.value.trim());

        if (!Number.isFinite(currentQuantity) || currentQuantity > this.minimumQuantity) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
    }

    protected clampQuantity(quantity: number): number {
        const minimum = this.minimumQuantity;

        return Number.isFinite(quantity) ? Math.max(Math.trunc(quantity), minimum) : minimum;
    }

    protected normalizeQuantity(value: string | null | undefined): number | null {
        const trimmedValue = value?.trim() ?? '';
        const quantity = Number(trimmedValue);

        return trimmedValue === '' || !Number.isFinite(quantity) ? null : this.clampQuantity(quantity);
    }

    protected get minimumQuantity(): number {
        return Number(this.input?.min) || 1;
    }

    protected syncAcceptedQuantity(): void {
        if (!this.acceptedQuantityInput || !this.input) {
            return;
        }

        this.acceptedQuantityInput.value = this.resolveAcceptedQuantity();
    }

    protected resolveAcceptedQuantity(): string {
        const quantity = this.normalizeQuantity(this.input?.value);

        return quantity === null ? '' : String(quantity);
    }

    protected notifyLineChanged(): void {
        this.updateLineTotal();
        this.dispatchEvent(new CustomEvent(LINE_ITEMS_UPDATE_EVENT, { bubbles: true }));
    }

    protected updateLineTotal(): void {
        this.setAttribute('data-line-total', String(this.getLineTotal()));
    }

    protected getLineTotal(): number {
        if (this.removeInput?.checked) {
            return 0;
        }

        const unitPrice = Number(this.getAttribute('data-unit-price')) || 0;
        const baselineQuantity = Number(this.getAttribute('data-quantity')) || 0;
        const acceptedQuantity = this.resolveAcceptedQuantity();
        const quantity = acceptedQuantity !== '' ? Number(acceptedQuantity) : baselineQuantity;

        return unitPrice * quantity;
    }

    protected toggleRemoved(): void {
        if (!this.removeInput) {
            return;
        }

        this.removeInput.checked = !this.removeInput.checked;
        this.syncRemovedState();
        this.notifyLineChanged();
    }

    protected syncRemovedState(): void {
        const isRemoved = this.removeInput?.checked;
        this.classList.toggle(`${this.name}--removed`, isRemoved);

        if (this.input) {
            this.input.toggleAttribute('disabled', isRemoved);
        }

        if (!this.removeToggleButton) {
            return;
        }

        this.removeToggleButton.setAttribute(
            'title',
            isRemoved
                ? (this.removeToggleButton.getAttribute('data-remove-label') ?? '')
                : (this.removeToggleButton?.getAttribute('data-undo-label') ?? ''),
        );
        this.removeToggleButton.classList.toggle(`${this.name}__button--removed`, isRemoved);
    }
}
