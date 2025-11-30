import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        // Re-initialize all dropdowns in the document
        if (window.bootstrap) {
            document.querySelectorAll('.dropdown-toggle').forEach((el) => {
                // Dispose previous instance if any
                if (el._dropdownInstance) {
                    el._dropdownInstance.dispose?.();
                }
                el._dropdownInstance = new bootstrap.Dropdown(el);
            });
        }
    }
}
