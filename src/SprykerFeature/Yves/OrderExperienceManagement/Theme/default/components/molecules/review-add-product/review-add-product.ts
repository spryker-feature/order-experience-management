import Component from 'ShopUi/models/component';
import { EVENT_CLOSE_POPUP } from 'ShopUi/components/molecules/main-popup/main-popup';
import ReviewAddedItems, {
    EVENT_ADDED_ITEM_REMOVE,
    EVENT_ADDED_ITEM_UPDATE,
    EVENT_ADDED_ITEM_WRITE,
} from '../review-added-items/review-added-items';
import { LINE_ITEMS_UPDATE_EVENT } from '../review-summary-banner-price/review-sumary-banner-price';
import {
    EVENT_REVIEW_PRODUCT_PICKED,
    PickedProductDetail,
} from '../review-add-product-picker/review-add-product-picker';
import ReviewAddProductLineRenderer from './review-add-product-line-renderer';

export default class ReviewAddProduct extends Component {
    protected linesContainer: HTMLElement | null;
    protected addedItems: ReviewAddedItems | null;
    protected titleContainer: HTMLElement | null;
    protected titleTemplate = '';
    protected titleTemplateSingular = '';
    protected lineRenderer: ReviewAddProductLineRenderer;
    protected entryIndex = 0;

    protected init(): void {
        this.linesContainer = this.querySelector(`.${this.jsName}__lines`);
        this.addedItems = this.querySelector<ReviewAddedItems>('review-added-items');
        this.titleContainer = this.querySelector(`.${this.jsName}__title`);
        this.titleTemplate = this.readTitleTemplate(`${this.jsName}-title-template`);
        this.titleTemplateSingular = this.readTitleTemplate(`${this.jsName}-title-template-singular`);
        this.lineRenderer = new ReviewAddProductLineRenderer(
            this.querySelector<HTMLTemplateElement>(`[data-id="${this.jsName}-line-template"]`),
            this.entryAttribute,
            this.jsName,
            this.getAttribute('currency') ?? '',
        );

        this.mapEvents();
        this.restoreSubmittedLines();
        this.renderTitle();
    }

    protected mapEvents(): void {
        document.addEventListener(EVENT_REVIEW_PRODUCT_PICKED, (event: Event) =>
            this.onProductPicked(event as CustomEvent<PickedProductDetail>),
        );
    }

    protected onProductPicked(event: CustomEvent<PickedProductDetail>): void {
        const detail = event.detail;
        const entryIndex = this.entryIndex++;

        this.dispatchCustomEvent(
            EVENT_ADDED_ITEM_WRITE,
            { entryKey: String(entryIndex), values: detail },
            { bubbles: true },
        );
        this.appendVisibleLine(entryIndex, detail.productName, detail.sku, detail.unitPrice, detail.quantity);
        this.renderTitle();
        this.closeModal();
    }

    protected readTitleTemplate(templateId: string): string {
        return this.querySelector<HTMLTemplateElement>(`[data-id="${templateId}"]`)?.innerHTML.trim() ?? '';
    }

    protected renderTitle(): void {
        if (!this.titleContainer) {
            return;
        }

        const count = this.linesContainer?.children.length ?? 0;
        const template = count === 1 ? this.titleTemplateSingular : this.titleTemplate;

        this.titleContainer.textContent = template.replace('%count%', String(count));
    }

    protected closeModal(): void {
        this.querySelector<HTMLElement>('main-popup')?.dispatchEvent(new CustomEvent(EVENT_CLOSE_POPUP));
    }

    protected restoreSubmittedLines(): void {
        const entryIndexes = (this.addedItems?.getEntryKeys() ?? [])
            .filter((entryKey: string) => this.isOwnEntryKey(entryKey))
            .map(Number);

        if (entryIndexes.length === 0) {
            return;
        }

        this.entryIndex = Math.max(...entryIndexes) + 1;

        entryIndexes.forEach((entryIndex: number) => this.restoreLine(entryIndex));
    }

    /** Added products are the numeric entry indexes; the substitute flow shares the collection under named keys. */
    protected isOwnEntryKey(entryKey: string): boolean {
        return !Number.isNaN(Number(entryKey));
    }

    protected restoreLine(entryIndex: number): void {
        const entry = this.addedItems?.readEntry(String(entryIndex));

        if (!entry) {
            return;
        }

        this.appendVisibleLine(
            entryIndex,
            entry.productName || entry.sku,
            entry.sku,
            entry.unitPrice,
            Math.max(Math.trunc(entry.quantity), 1),
        );
    }

    protected appendVisibleLine(
        entryIndex: number,
        name: string,
        sku: string,
        unitPrice: number,
        quantity: number,
    ): void {
        if (!this.linesContainer) {
            return;
        }

        const lineElement = this.lineRenderer.render({
            entryIndex,
            name,
            sku,
            unitPrice,
            quantity,
            onQuantityChange: (lineQuantity: number) => {
                this.dispatchCustomEvent(
                    EVENT_ADDED_ITEM_UPDATE,
                    { entryKey: String(entryIndex), values: { quantity: lineQuantity } },
                    { bubbles: true },
                );
                this.triggerRecalculation();
            },
            onRemove: (element: HTMLElement) => this.removeEntry(entryIndex, element),
        });

        if (!lineElement) {
            return;
        }

        this.linesContainer.appendChild(lineElement);
        this.triggerRecalculation();
    }

    protected removeEntry(entryIndex: number, lineElement: HTMLElement): void {
        this.dispatchCustomEvent(EVENT_ADDED_ITEM_REMOVE, { entryKey: String(entryIndex) }, { bubbles: true });
        lineElement.remove();

        this.renderTitle();
        this.triggerRecalculation();
    }

    protected triggerRecalculation(): void {
        this.dispatchCustomEvent(LINE_ITEMS_UPDATE_EVENT, {}, { bubbles: true });
    }

    protected get entryAttribute(): string {
        return `data-${this.name}-entry`;
    }
}
