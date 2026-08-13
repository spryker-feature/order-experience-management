import Component from 'ShopUi/models/component';
import MainPopup from 'ShopUi/components/molecules/main-popup/main-popup';
import { EVENT_POPUP_CLOSED } from 'ShopUi/components/molecules/main-popup/main-popup';

export default class ScheduleReview extends Component {
    protected form: HTMLFormElement;
    protected trigger: HTMLElement;
    protected confirmationPopup: MainPopup;
    protected isSubmitConfirmed = false;

    protected init(): void {
        this.form = <HTMLFormElement>this.querySelector(`.${this.jsName}__form`);
        this.trigger = <HTMLElement>document.querySelector(`.${this.getAttribute('confirm-modal-trigger-class-name')}`);
        this.confirmationPopup = <MainPopup>this.querySelector(`.${this.jsName}__confirmation-popup`);
        this.mapEvents();
    }

    protected mapEvents(): void {
        this.form.addEventListener('submit', (event: Event) => this.onFormSubmit(event));
        this.trigger?.addEventListener('click', () => this.onTriggerClick());
        this.confirmationPopup?.addEventListener(EVENT_POPUP_CLOSED, () => this.onPopupClosed());
    }

    protected onFormSubmit(event: Event): void {
        if (this.isSubmitConfirmed) {
            return;
        }

        event.preventDefault();
        this.trigger?.click();
    }

    protected onTriggerClick(): void {
        this.isSubmitConfirmed = true;
    }

    protected onPopupClosed(): void {
        this.isSubmitConfirmed = false;
    }
}
