
(function (root) {
    'use strict';
    if (root.AetherUI) return;

    let config = { enabled: false, consoleEnabled: false, terminal: {} };
    const wrappers = new WeakMap();
    const terminals = new Map();
    const fits = new WeakMap();
    const observers = new WeakMap();
    const scheduled = new WeakSet();
    function fit(terminal) {
        if (!terminals.has(terminal) || scheduled.has(terminal)) return;
        scheduled.add(terminal);
        (root.requestAnimationFrame || ((callback) => callback()))(() => {
            scheduled.delete(terminal);
            if (!terminals.get(terminal)?.isConnected) return;
            try { fits.get(terminal)?.fit(); } catch {}
        });
    }
    const reducedMotion = () => !!root.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    const options = () => {
        const result = { ...config.terminal, theme: { ...config.terminal.theme } };
        if (reducedMotion()) result.cursorBlink = false;
        return result;
    };

    function decorate(api) {
        if (!api || typeof api.Terminal !== 'function') return api;
        if (typeof api.FitAddon === 'function' && !wrappers.has(api.FitAddon)) {
            const OriginalFit = api.FitAddon;
            class AetherFit extends OriginalFit {
                activate(terminal) {
                    fits.set(terminal, this);
                    return super.activate(terminal);
                }
            }
            wrappers.set(OriginalFit, AetherFit);
            wrappers.set(AetherFit, AetherFit);
            api.FitAddon = AetherFit;
        } else if (wrappers.has(api.FitAddon)) {
            api.FitAddon = wrappers.get(api.FitAddon);
        }
        const Original = api.Terminal;
        if (wrappers.has(Original)) {
            api.Terminal = wrappers.get(Original);
            return api;
        }
        class AetherTerminal extends Original {
            constructor(originalOptions = {}) {
                const active = config.enabled && config.consoleEnabled;
                super(active ? { ...originalOptions, ...options(), theme: { ...originalOptions.theme, ...options().theme } } : originalOptions);
                this.aetherOriginalOptions = { ...originalOptions, theme: { ...originalOptions.theme } };
            }
            open(element) {
                const result = super.open(element);
                if (element?.id === 'terminal') {
                    terminals.set(this, element);
                    if (root.ResizeObserver) {
                        const observer = new root.ResizeObserver(() => fit(this));
                        observer.observe(element);
                        observers.set(this, observer);
                    }
                    root.document?.fonts?.ready.then(() => fit(this));
                    fit(this);
                }
                return result;
            }
            dispose() {
                terminals.delete(this);
                observers.get(this)?.disconnect();
                observers.delete(this);
                fits.delete(this);
                return super.dispose();
            }
        }
        wrappers.set(Original, AetherTerminal);
        wrappers.set(AetherTerminal, AetherTerminal);
        api.Terminal = AetherTerminal;
        return api;
    }

    function update() {
        const html = root.document?.documentElement;
        html?.classList.toggle('aether-ui', !!config.enabled);
        if (html) {
            html.dataset.aetherLogin = config.loginLayout === 'split' ? 'split' : 'centered';
            html.dataset.aetherConsole = config.consoleEnabled ? 'on' : 'off';
        }
        for (const [terminal, element] of terminals) {
            if (!element.isConnected) {
                terminals.delete(terminal);
                observers.get(terminal)?.disconnect();
                observers.delete(terminal);
                continue;
            }
            try {
                const next = config.enabled && config.consoleEnabled ? options() : terminal.aetherOriginalOptions;
                const { rows, ...runtime } = next;
                terminal.options = { ...runtime, theme: { ...terminal.aetherOriginalOptions.theme, ...runtime.theme } };
                fit(terminal);
            } catch (error) {
                root.console?.warn('Aether UI: terminal appearance could not be refreshed.', error);
            }
        }
    }
    const descriptor = Object.getOwnPropertyDescriptor(root, 'Xterm');
    if (!descriptor || (descriptor.configurable && !descriptor.get && !descriptor.set)) {
        let api = decorate(root.Xterm);
        Object.defineProperty(root, 'Xterm', {
            configurable: true,
            enumerable: true,
            get: () => api,
            set: (value) => { api = decorate(value); },
        });
    } else {
        root.console?.warn('Aether UI: Xterm bridge unavailable; default terminal retained.');
    }
    root.document?.addEventListener('livewire:navigated', update);
    root.document?.fonts?.addEventListener?.('loadingdone', () => { for (const terminal of terminals.keys()) fit(terminal); });
    root.matchMedia?.('(prefers-reduced-motion: reduce)').addEventListener?.('change', update);
    root.AetherUI = Object.freeze({
        mount(next) {
            config = { ...config, ...next };
            update();
        },
        version: '0.2.2',
    });
})(window);
