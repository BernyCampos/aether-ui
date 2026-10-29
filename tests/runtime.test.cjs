'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const source = fs.readFileSync(__dirname + '/../resources/js/aether.js', 'utf8');

function fixture({ reduced = false, existing = false, locked = false } = {}) {
    const classes = new Set();
    const listeners = {};
    class Terminal {
        constructor(options) { this.options = options; }
        open(element) { this.element = element; return 'opened'; }
        dispose() { this.disposed = true; return 'disposed'; }
    }
    const warnings = [];
    const window = {
        document: {
            documentElement: { dataset: {}, classList: { toggle(name, on) { on ? classes.add(name) : classes.delete(name); } } },
            addEventListener(name, fn) { (listeners[name] ??= []).push(fn); },
        },
        matchMedia: () => ({ matches: reduced, addEventListener(name, fn) { listeners['media:' + name] = [fn]; } }),
        console: { warn: (...args) => warnings.push(args) },
    };
    if (existing) window.Xterm = { Terminal, FitAddon: 'preserved' };
    if (locked) Object.defineProperty(window, 'Xterm', { configurable: false, value: { Terminal } });
    const context = vm.createContext({ window });
    vm.runInContext(source, context);
    const mount = (extra = {}) => window.AetherUI.mount({ enabled: true, consoleEnabled: true, loginLayout: 'split', terminal: { fontSize: 17, rows: 30, cursorBlink: true, theme: { background: '#123456' } }, ...extra });
    return { window, Terminal, mount, classes, listeners, warnings, context };
}

test('deferred Xterm assignment is wrapped and addons are preserved', () => {
    const f = fixture(); f.mount();
    f.window.Xterm = { Terminal: f.Terminal, FitAddon: 'fit' };
    const original = { disableStdin: true, rows: 12, theme: { red: '#ff0000' } };
    const terminal = new f.window.Xterm.Terminal(original);
    assert.ok(terminal instanceof f.Terminal);
    assert.equal(terminal.options.fontSize, 17);
    assert.equal(terminal.options.disableStdin, true);
    assert.equal(terminal.options.theme.red, '#ff0000');
    assert.equal(f.window.Xterm.FitAddon, 'fit');
    assert.equal(original.rows, 12);
    assert.equal(original.theme.background, undefined);
});
test('existing API is wrapped', () => {
    const f = fixture({ existing: true }); f.mount();
    assert.equal(new f.window.Xterm.Terminal().options.fontSize, 17);
});
test('disabled terminal settings retain original options', () => {
    const f = fixture({ existing: true }); f.mount({ consoleEnabled: false });
    assert.equal(new f.window.Xterm.Terminal({ fontSize: 11 }).options.fontSize, 11);
});
test('modeled SPA navigation refreshes options without changing fitted rows', () => {
    const f = fixture({ existing: true }); f.mount();
    const terminal = new f.window.Xterm.Terminal({ fontSize: 11 });
    assert.equal(terminal.open({ id: 'terminal', isConnected: true }), 'opened');
    f.listeners['livewire:navigated'][0]();
    assert.equal(terminal.options.fontSize, 17);
    assert.equal(terminal.options.rows, undefined);
    assert.equal(terminal.dispose(), 'disposed');
    f.mount({ terminal: { fontSize: 22 } });
    assert.equal(terminal.options.fontSize, 17);
});
test('runtime disable restores native terminal options', () => {
    const f = fixture({ existing: true }); f.mount();
    const terminal = new f.window.Xterm.Terminal({ fontSize: 11, theme: { background: '#ffffff' } });
    terminal.open({ id: 'terminal', isConnected: true });
    f.mount({ enabled: false });
    assert.equal(terminal.options.fontSize, 11);
    assert.equal(terminal.options.theme.background, '#ffffff');
    assert.ok(!f.classes.has('aether-ui'));
});
test('reduced motion disables blinking cursor', () => {
    const f = fixture({ existing: true, reduced: true }); f.mount();
    assert.equal(new f.window.Xterm.Terminal().options.cursorBlink, false);
});
test('repeated script execution and API assignment do not stack handlers/wrappers', () => {
    const f = fixture({ existing: true }); f.mount();
    const type = f.window.Xterm.Terminal;
    f.window.Xterm = f.window.Xterm;
    vm.runInContext(source, f.context);
    assert.equal(f.window.Xterm.Terminal, type);
    assert.equal(f.listeners['livewire:navigated'].length, 1);
});
test('locked unfamiliar API fails softly', () => {
    const f = fixture({ locked: true }); f.mount();
    assert.equal(f.window.Xterm.Terminal, f.Terminal);
    assert.equal(f.warnings.length, 1);
});
test('detached terminals are no longer updated', () => {
    const f = fixture({ existing: true }); f.mount();
    const terminal = new f.window.Xterm.Terminal();
    terminal.open({ id: 'terminal', isConnected: false });
    f.mount({ terminal: { fontSize: 22 } });
    assert.equal(terminal.options.fontSize, 17);
});
test('layout choices are restricted to known values', () => {
    const f = fixture(); f.mount({ loginLayout: 'unexpected' });
    assert.equal(f.window.document.documentElement.dataset.aetherLogin, 'centered');
});

test('container resize and font loading refit only active terminal instances', async () => {
    const f = fixture();
    const observations = [];
    let fontReady;
    f.window.document.fonts = {
        ready: new Promise(resolve => { fontReady = resolve; }),
        addEventListener(name, fn) { f.listeners[name] = [fn]; },
    };
    f.window.ResizeObserver = class {
        constructor(callback) { this.callback = callback; observations.push(this); }
        observe(element) { this.element = element; }
        disconnect() { this.disconnected = true; }
    };
    class FitAddon {
        activate(terminal) { this.terminal = terminal; }
        fit() { this.calls = (this.calls || 0) + 1; }
    }
    f.window.Xterm = { Terminal: f.Terminal, FitAddon };
    f.mount();
    const terminal = new f.window.Xterm.Terminal();
    const fit = new f.window.Xterm.FitAddon();
    fit.activate(terminal);
    const element = { id: 'terminal', isConnected: true };
    terminal.open(element);
    assert.equal(fit.calls, 1);
    observations[0].callback();
    assert.equal(fit.calls, 2);
    fontReady();
    await Promise.resolve();
    assert.equal(fit.calls, 3);
    terminal.dispose();
    assert.equal(observations[0].disconnected, true);
    observations[0].callback();
    assert.equal(fit.calls, 3);
});

test('detaching a console disconnects its resize observer on navigation', () => {
    const f = fixture({existing: true});
    let disconnected = false;
    f.window.ResizeObserver = class {
        observe() {}
        disconnect() { disconnected = true; }
    };
    f.mount();
    const terminal = new f.window.Xterm.Terminal();
    const element = { id: 'terminal', isConnected: true };
    terminal.open(element);
    element.isConnected = false;
    f.listeners['livewire:navigated'][0]();
    assert.equal(disconnected, true);
});
