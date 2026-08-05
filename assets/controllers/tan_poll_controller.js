import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        url: String,
        allowsAutomatedPolling: Boolean,
        firstDelay: Number,
        periodicDelay: Number,
        maxChecks: Number,
    };

    static targets = ['status', 'manualButton'];

    connect() {
        this.checksRemaining = this.maxChecksValue;

        if (this.allowsAutomatedPollingValue) {
            this.timeoutId = setTimeout(() => this.poll(), this.firstDelayValue * 1000);
        } else {
            this.manualButtonTarget.hidden = false;
        }
    }

    disconnect() {
        clearTimeout(this.timeoutId);
    }

    manualCheck() {
        this.poll();
    }

    async poll() {
        this.statusTarget.textContent = 'Checking…';

        const response = await fetch(this.urlValue, { method: 'POST' });

        if (response.status === 202) {
            this.checksRemaining -= 1;
            if (this.allowsAutomatedPollingValue && this.checksRemaining > 0) {
                this.timeoutId = setTimeout(() => this.poll(), this.periodicDelayValue * 1000);
            } else {
                this.statusTarget.textContent = 'Still waiting — tap to check again.';
                this.manualButtonTarget.hidden = false;
            }
            return;
        }

        if (!response.ok) {
            this.statusTarget.textContent = 'Something went wrong — tap to try again.';
            this.manualButtonTarget.hidden = false;
            return;
        }

        Turbo.visit(window.location.href, { action: 'replace' });
    }
}
