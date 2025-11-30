import { Controller } from '@hotwired/stimulus';

/**
 * Controller for handling single item import with better UX feedback
 */
export default class extends Controller {
    static targets = ['button', 'spinner'];

    connect() {
        // Store original button content
        if (this.hasButtonTarget) {
            this.originalButtonContent = this.buttonTarget.innerHTML;
        }
    }

    /**
     * Handle form submission with loading state
     */
    submit(event) {
        if (!this.hasButtonTarget) {
            return;
        }

        // Disable the button and show loading state
        this.buttonTarget.disabled = true;
        this.buttonTarget.classList.add('importing');

        // Replace button content with spinner
        this.buttonTarget.innerHTML = '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>';
        const translator = window.SymfonyTranslator || ((key) => key);
        this.buttonTarget.title = translator('action.importing') || 'Importing...';

        // The form will submit normally, and page will redirect with preserved state
    }

    /**
     * Reset button state (useful if we add AJAX support later)
     */
    reset() {
        if (this.hasButtonTarget) {
            this.buttonTarget.disabled = false;
            this.buttonTarget.classList.remove('importing');
            this.buttonTarget.innerHTML = this.originalButtonContent;
            const translator = window.SymfonyTranslator || ((key) => key);
            this.buttonTarget.title = translator('action.import_this_item') || 'Import This Item';
        }
    }
}
