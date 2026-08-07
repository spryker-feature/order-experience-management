import Component from 'ShopUi/models/component';
import { LINE_ITEMS_UPDATE_EVENT } from '../review-summary-banner-price/review-sumary-banner-price';
import ReviewAddedItems from '../review-added-items/review-added-items';

const REMOVED_OUTPUT_CLASS = 'js-review-change-summary__removed';
const PRICE_CHANGE_OUTPUT_CLASS = 'js-review-change-summary__price-changes';
const SUBSTITUTED_OUTPUT_CLASS = 'js-review-change-summary__substituted';
const ADDED_OUTPUT_CLASS = 'js-review-change-summary__added';
const REMOVE_CHECKBOX_CLASS = 'js-review-total-recalculator__line-remove';
const LINE_SELECTOR = 'review-quantity-control';
const PRICE_CHANGED_LINE_SELECTOR = 'review-quantity-control[data-has-price-change="1"]';
const SUBSTITUTE_ENTRY_KEY_ATTRIBUTE = 'data-substitute-entry-key';
const COUNT_PLACEHOLDER = '%count%';

export default class ReviewChangeSummary extends Component {
    protected addedItems: ReviewAddedItems | null;

    protected init(): void {
        this.addedItems = document.querySelector<ReviewAddedItems>('review-added-items');

        this.refresh();
        document.addEventListener(LINE_ITEMS_UPDATE_EVENT, () => this.refresh());
    }

    protected refresh(): void {
        // Added products occupy the numeric entry indexes of the shared collection, substitutes the named keys.
        const entryKeys = this.addedItems?.getEntryKeys() ?? [];
        const substituteEntryKeys = entryKeys.filter((key: string) => Number.isNaN(Number(key)));

        this.renderCount(REMOVED_OUTPUT_CLASS, this.countRemovedLines(substituteEntryKeys));
        this.renderCount(PRICE_CHANGE_OUTPUT_CLASS, this.countAcceptedPriceChanges());
        this.renderCount(SUBSTITUTED_OUTPUT_CLASS, substituteEntryKeys.length);
        this.renderCount(ADDED_OUTPUT_CLASS, entryKeys.length - substituteEntryKeys.length);
    }

    protected countRemovedLines(substituteEntryKeys: string[]): number {
        return this.getLines(LINE_SELECTOR).filter(
            (line: HTMLElement) => this.isLineRemoved(line) && !this.hasAppliedSubstitute(line, substituteEntryKeys),
        ).length;
    }

    protected countAcceptedPriceChanges(): number {
        return this.getLines(PRICE_CHANGED_LINE_SELECTOR).filter((line: HTMLElement) => !this.isLineRemoved(line))
            .length;
    }

    protected getLines(selector: string): HTMLElement[] {
        return Array.from(document.querySelectorAll<HTMLElement>(selector));
    }

    protected isLineRemoved(line: HTMLElement): boolean {
        return line.querySelector<HTMLInputElement>(`.${REMOVE_CHECKBOX_CLASS}`)?.checked === true;
    }

    protected hasAppliedSubstitute(line: HTMLElement, substituteEntryKeys: string[]): boolean {
        const substituteEntryKey = line.getAttribute(SUBSTITUTE_ENTRY_KEY_ATTRIBUTE);

        if (!substituteEntryKey) {
            return false;
        }

        return substituteEntryKeys.includes(substituteEntryKey);
    }

    protected renderCount(outputClassName: string, count: number): void {
        document.querySelectorAll<HTMLElement>(`.${outputClassName}`).forEach((outputElement: HTMLElement) => {
            const template = outputElement.getAttribute('data-count-template');

            outputElement.textContent = template ? template.replace(COUNT_PLACEHOLDER, String(count)) : String(count);
        });
    }
}
