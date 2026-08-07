import Component from 'ShopUi/models/component';
export const LINE_ITEMS_UPDATE_EVENT = 'lineItemsUpdated';

const CENTS_IN_UNIT = 100;

const PRICE_DECIMAL_PLACES = 2;

export default class ReviewSummaryBannerPrice extends Component {
    protected init(): void {
        this.recalculate();
        document.addEventListener(LINE_ITEMS_UPDATE_EVENT, () => this.recalculate());
    }

    protected recalculate(): void {
        const totalInCents = this.getLineElements().reduce(
            (total: number, lineElement: HTMLElement) => total + this.getLineTotal(lineElement),
            0,
        );

        const formattedTotal = this.formatPrice(totalInCents);
        this.textContent = formattedTotal;
        this.updateTotalOutputs(formattedTotal);
    }

    protected updateTotalOutputs(formattedTotal: string): void {
        document
            .querySelectorAll<HTMLElement>(`.${this.getAttribute('total-class-name')}`)
            .forEach((totalElement: HTMLElement) => {
                totalElement.textContent = formattedTotal;
            });
    }

    protected getLineElements(): HTMLElement[] {
        return Array.from(document.querySelectorAll<HTMLElement>(`.${this.getAttribute('line-class-name')}`));
    }

    protected getLineTotal(lineElement: HTMLElement): number {
        return Number(lineElement.getAttribute('data-line-total')) || 0;
    }

    /**
     * Formats the integer cent amounts the recurring-order Review Required page works with. Falls back to a plain
     * decimal when no currency is known, so a missing currency attribute degrades to a readable number instead of
     * throwing inside Intl.NumberFormat.
     */
    protected formatPrice(amountInCents: number): string {
        const amount = amountInCents / CENTS_IN_UNIT;
        const currency = this.getAttribute('data-currency') ?? '';

        return currency === ''
            ? amount.toFixed(PRICE_DECIMAL_PLACES)
            : amount.toLocaleString(document.documentElement.lang || 'en', { style: 'currency', currency });
    }
}
