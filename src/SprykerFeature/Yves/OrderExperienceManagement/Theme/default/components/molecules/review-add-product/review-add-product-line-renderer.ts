const CENTS_IN_UNIT = 100;

const PRICE_DECIMAL_PLACES = 2;

export interface ReviewAddProductLine {
    entryIndex: number;
    name: string;
    sku: string;
    unitPrice: number;
    quantity: number;
    onQuantityChange: (quantity: number) => void;
    onRemove: (lineElement: HTMLElement) => void;
}

/**
 * Clones the line template declared in review-add-product.twig instead of building the markup here, so the line —
 * including its review-total-recalculator hooks — stays overridable on project level.
 */
export default class ReviewAddProductLineRenderer {
    constructor(
        protected readonly template: HTMLTemplateElement | null,
        protected readonly entryAttribute: string,
        protected readonly jsName: string,
        protected readonly currency: string,
    ) {}

    render(line: ReviewAddProductLine): HTMLElement | null {
        const lineElement = this.cloneLineElement();

        if (!lineElement) {
            return null;
        }

        const quantityInput = lineElement.querySelector<HTMLInputElement>(
            '.js-review-total-recalculator__line-quantity',
        );
        const priceElement = lineElement.querySelector<HTMLElement>(`.${this.jsName}__line-price`);
        const removeButton = lineElement.querySelector<HTMLElement>(`.${this.jsName}__line-remove`);

        this.fillLineElement(lineElement, line);
        this.fillNameElement(lineElement, line);

        if (quantityInput) {
            quantityInput.value = String(line.quantity);
        }

        const applyQuantity = (quantity: number): void => {
            const lineTotal = line.unitPrice * quantity;
            lineElement.setAttribute('data-line-total', String(lineTotal));

            if (priceElement) {
                priceElement.textContent = this.formatPrice(lineTotal);
            }
        };

        applyQuantity(line.quantity);

        quantityInput?.addEventListener('input', () => {
            const quantity = this.normalizeQuantity(quantityInput);
            applyQuantity(quantity);
            line.onQuantityChange(quantity);
        });

        quantityInput?.addEventListener('change', () => {
            const quantity = this.normalizeQuantity(quantityInput);

            if (Number(quantityInput.value) !== quantity) {
                quantityInput.value = String(quantity);
            }

            applyQuantity(quantity);
            line.onQuantityChange(quantity);
        });
        removeButton?.addEventListener('click', () => line.onRemove(lineElement));

        return lineElement;
    }

    protected cloneLineElement(): HTMLElement | null {
        const clone = this.template?.content.cloneNode(true) as DocumentFragment | undefined;

        return clone?.querySelector<HTMLElement>('.js-review-total-recalculator__line') ?? null;
    }

    protected fillLineElement(lineElement: HTMLElement, line: ReviewAddProductLine): void {
        lineElement.setAttribute('data-unit-price', String(line.unitPrice));
        lineElement.setAttribute('data-quantity', String(line.quantity));
        lineElement.setAttribute(this.entryAttribute, String(line.entryIndex));
    }

    protected fillNameElement(lineElement: HTMLElement, line: ReviewAddProductLine): void {
        const nameText = lineElement.querySelector<HTMLElement>(`.${this.jsName}__line-name-text`);
        const skuElement = lineElement.querySelector<HTMLElement>(`.${this.jsName}__line-sku`);

        if (nameText) {
            nameText.textContent = line.name;
        }

        if (!skuElement) {
            return;
        }

        if (line.sku === '' || line.sku === line.name) {
            skuElement.remove();

            return;
        }

        skuElement.textContent = ` (${line.sku})`;
    }

    /** Truncates fractions and raises anything below the floor the line template rendered as the input's min. */
    protected normalizeQuantity(input: HTMLInputElement): number {
        const minimum = Number(input.min) || 1;
        const quantity = Number(input.value.trim());

        return Number.isFinite(quantity) ? Math.max(Math.trunc(quantity), minimum) : minimum;
    }

    protected formatPrice(amountInCents: number): string {
        const amount = amountInCents / CENTS_IN_UNIT;
        const { currency } = this;

        return currency === ''
            ? amount.toFixed(PRICE_DECIMAL_PLACES)
            : amount.toLocaleString(document.documentElement.lang || 'en', { style: 'currency', currency });
    }
}
