import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static values = {
        url: String
    }

    static targets = ["form", "tanModeSelect", "tanMediaSelect", "loader"];

    connect() {
        const select = this.tanModeSelectTarget;

        if (select.value.length !== 0) {
            let event = new Event('change');
            select.dispatchEvent(event);
        }
    }

    async fetchTanMedia(event) {
        const tanModeSelect = event.currentTarget;
        const selectedTanMode = tanModeSelect.options[tanModeSelect.selectedIndex];
        const tanMediaSelect = this.tanMediaSelectTarget;

        if (selectedTanMode.dataset.needsTanMedium === "false") {
            tanMediaSelect.options[0].innerText = 'The selected TAN mode does not require a TAN medium.';
        } else {
            const loader = this.loaderTarget;
            loader.classList.remove('hidden');

            const response = await fetch(this.urlValue + '?tan-mode=' + event.currentTarget.value);

            if (200 === response.status) {
                const data = await response.json()

                data.forEach(function(item) {
                    let option = document.createElement('option')
                    option.value = item.name;
                    option.innerText = item.name;

                    select.appendChild(option);
                });

                select.removeAttribute('disabled');
                select.classList.remove('cursor-not-allowed');

                loader.classList.add('hidden');
            } else {
                // todo error message
            }
        }
    }
}
