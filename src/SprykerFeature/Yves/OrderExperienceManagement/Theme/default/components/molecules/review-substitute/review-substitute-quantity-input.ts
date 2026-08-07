const ARROW_DOWN_KEY = 'ArrowDown';

/**
 * The quantity field of the review-substitute summary line. Kept out of the component for the same reason as the
 * summary renderer: the field posts into an IntegerType constrained to be greater than zero, so every read has to
 * truncate fractions and raise anything below the floor, and the floor itself comes from the min attribute the
 * template renders from that same constraint.
 */
export default class ReviewSubstituteQuantityInput {
    constructor(protected readonly input: HTMLInputElement | null) {}

    read(fallbackQuantity: number): number {
        const trimmedValue = this.input?.value.trim() ?? '';
        const quantity = Number(trimmedValue);

        return trimmedValue === '' || !Number.isFinite(quantity) ? this.clamp(fallbackQuantity) : this.clamp(quantity);
    }

    write(quantity: number): void {
        if (this.input) {
            this.input.value = String(quantity);
        }
    }

    correct(quantity: number): void {
        const currentValue = this.input?.value.trim() ?? '';

        if (currentValue !== '' && Number(currentValue) !== quantity) {
            this.write(quantity);
        }
    }

    preventDecrementBelowMinimum(event: KeyboardEvent): void {
        if (event.key !== ARROW_DOWN_KEY || event.target !== this.input) {
            return;
        }

        const currentQuantity = Number(this.input?.value.trim());

        if (!Number.isFinite(currentQuantity) || currentQuantity > this.minimum) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
    }

    protected clamp(quantity: number): number {
        const minimum = this.minimum;

        return Number.isFinite(quantity) ? Math.max(Math.trunc(quantity), minimum) : minimum;
    }

    protected get minimum(): number {
        return Number(this.input?.min) || 1;
    }
}
