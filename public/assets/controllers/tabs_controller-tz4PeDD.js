import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['trigger', 'tab'];
    static values = { activeTab: String };

    open(e) {
        this.activeTabValue = e.currentTarget.dataset.tabId;
    }

    activeTabValueChanged() {
        this.triggerTargets.forEach((trigger) => {
            const isActive = trigger.dataset.tabId === this.activeTabValue;
            trigger.dataset.state = isActive ? 'active' : 'inactive';
            trigger.ariaSelected = isActive;
        });

        this.tabTargets.forEach((tab) => {
            const isActive = tab.dataset.tabId === this.activeTabValue;
            tab.dataset.state = isActive ? 'active' : 'inactive';
            if (isActive && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                import('motion')
                    .then(({ animate }) => {
                        animate(tab, { opacity: [0.88, 1] }, { duration: 0.2 });
                    })
                    .catch(() => {});
            }
        });
    }
}
