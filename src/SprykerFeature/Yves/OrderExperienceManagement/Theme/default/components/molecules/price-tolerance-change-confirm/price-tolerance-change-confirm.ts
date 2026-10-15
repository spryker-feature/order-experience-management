import Component from 'ShopUi/models/component';
import MainPopup, { EVENT_OPEN_POPUP } from 'ShopUi/components/molecules/main-popup/main-popup';

const COUNT_PLACEHOLDER = '%count%';
const SKIP_LEAVE_CONFIRM_ATTRIBUTE = 'data-skip-leave-confirm';
const PRIMARY_MOUSE_BUTTON = 0;

export default class PriceToleranceChangeConfirm extends Component {
    protected form: HTMLFormElement | null;
    protected confirmPopup: MainPopup | null;
    protected discardPopup: MainPopup | null;
    protected isLeaveConfirmed = false;

    protected init(): void {
        this.form = document.getElementById(this.formId) as HTMLFormElement | null;
        this.confirmPopup = this.querySelector<MainPopup>(`.${this.jsName}__confirm-popup`);
        this.discardPopup = this.querySelector<MainPopup>(`.${this.jsName}__discard-popup`);

        this.mapEvents();
    }

    protected mapEvents(): void {
        this.form?.addEventListener('submit', (event: SubmitEvent) => this.onFormSubmit(event));

        document.addEventListener('click', (event: MouseEvent) => this.onDocumentClick(event));
        window.addEventListener('beforeunload', (event: BeforeUnloadEvent) => this.onBeforeUnload(event));
    }

    protected onFormSubmit(event: SubmitEvent): void {
        const isConfirmed = (event.submitter as Element | null)?.classList.contains(`${this.jsName}__confirm`);
        const changesCount = this.getChangesCount();

        if (isConfirmed || changesCount === 0) {
            this.isLeaveConfirmed = true;

            return;
        }

        event.preventDefault();
        this.openPopup(this.confirmPopup, `${this.jsName}__confirm-text`, this.formatText('confirm', changesCount));
    }

    protected onDocumentClick(event: MouseEvent): void {
        const link = (event.target as Element).closest?.<HTMLAnchorElement>('a[href]');

        if (!link) {
            return;
        }

        if (link.classList.contains(`${this.jsName}__discard`)) {
            this.isLeaveConfirmed = true;

            return;
        }

        const changesCount = this.isLeavingNavigation(event, link) ? this.getChangesCount() : 0;

        if (changesCount === 0) {
            return;
        }

        event.preventDefault();
        this.setDiscardUrl(link.href);
        this.openPopup(this.discardPopup, `${this.jsName}__discard-text`, this.formatText('discard', changesCount));
    }

    protected isLeavingNavigation(event: MouseEvent, link: HTMLAnchorElement): boolean {
        const isPlainClick =
            !event.defaultPrevented &&
            event.button === PRIMARY_MOUSE_BUTTON &&
            !event.metaKey &&
            !event.ctrlKey &&
            !event.shiftKey &&
            !event.altKey;
        const opensElsewhere = (link.target !== '' && link.target !== '_self') || link.hasAttribute('download');

        if (!isPlainClick || opensElsewhere || link.hasAttribute(SKIP_LEAVE_CONFIRM_ATTRIBUTE)) {
            return false;
        }

        const url = new URL(link.href, window.location.href);
        const isWebUrl = url.protocol === 'http:' || url.protocol === 'https:';
        const isSamePageAnchor =
            url.hash !== '' &&
            url.origin + url.pathname + url.search ===
                window.location.origin + window.location.pathname + window.location.search;

        return isWebUrl && !isSamePageAnchor;
    }

    protected setDiscardUrl(url: string): void {
        Array.from(document.getElementsByClassName(`${this.jsName}__discard`)).forEach((element: Element) =>
            element.setAttribute('href', url),
        );
    }

    protected onBeforeUnload(event: BeforeUnloadEvent): void {
        if (this.isLeaveConfirmed || this.getChangesCount() === 0) {
            return;
        }

        event.preventDefault();
    }

    protected openPopup(popup: MainPopup | null, textClassName: string, text: string): void {
        Array.from(document.getElementsByClassName(textClassName)).forEach((element: Element) => {
            element.textContent = text;
        });

        popup?.dispatchEvent(new CustomEvent(EVENT_OPEN_POPUP));
    }

    protected getChangesCount(): number {
        return this.getTrackedFields().filter((field: HTMLInputElement) => this.isFieldChanged(field)).length;
    }

    protected getTrackedFields(): HTMLInputElement[] {
        if (!this.form) {
            return [];
        }

        return Array.from(this.form.querySelectorAll<HTMLInputElement>(this.trackedFieldSelector)).filter(
            (field: HTMLInputElement) => !field.disabled,
        );
    }

    protected isFieldChanged(field: HTMLInputElement): boolean {
        if (field.type === 'checkbox' || field.type === 'radio') {
            return field.checked !== field.defaultChecked;
        }

        const value = field.value.trim();
        const defaultValue = field.defaultValue.trim();

        if (
            value !== '' &&
            defaultValue !== '' &&
            Number.isFinite(Number(value)) &&
            Number.isFinite(Number(defaultValue))
        ) {
            return Number(value) !== Number(defaultValue);
        }

        return value !== defaultValue;
    }

    protected formatText(type: 'confirm' | 'discard', count: number): string {
        const text = this.getAttribute(`${type}-text-${count === 1 ? 'one' : 'many'}`) ?? '';

        return text.replace(COUNT_PLACEHOLDER, String(count));
    }

    protected get formId(): string {
        return this.getAttribute('form-id') ?? '';
    }

    protected get trackedFieldSelector(): string {
        return this.getAttribute('tracked-field-selector') ?? '';
    }
}
