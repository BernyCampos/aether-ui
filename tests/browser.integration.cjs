const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const fixture = process.env.AETHER_TEST_PANEL;
if (!fixture || !fs.existsSync(path.join(fixture, '.aether-isolated-test')) || !process.env.AETHER_TEST_AUTH) {
    throw new Error('Set AETHER_TEST_PANEL and AETHER_TEST_AUTH for a marked disposable panel.');
}
const root = path.join(__dirname, '..');
const base = process.env.AETHER_TEST_URL || 'http://127.0.0.1:8794';
const fixtureCommand = (...args) => execFileSync(process.env.AETHER_TEST_PHP || 'php', [path.join(__dirname, 'browser-fixture.php'), ...args], {env: process.env});
const widths = [320, 390, 768, 1024, 1440, 2560];
let checks = 0;
const check = (value, label) => { assert.ok(value, label); checks++; };
async function measure(page, label) {
    const overflow = await page.evaluate(() => {
        const selectors = '.fi-main,.fi-ta-content,.fi-ta-table,.fi-tabs,.fi-modal-window,.fi-modal-content,.fi-section-content,.fi-small-stat-block';
        return [...document.querySelectorAll(selectors)].filter(e => e.getClientRects().length && e.clientWidth && e.scrollWidth > e.clientWidth + 1)
            .map(e => ({class: e.className, width: e.clientWidth, scroll: e.scrollWidth}));
    });
    check(overflow.length === 0, label + ': ' + JSON.stringify(overflow));
    check(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), label + ': document overflow');
}
async function settled(page) {
    await page.waitForLoadState('networkidle');
    await page.evaluate(() => document.fonts.ready);
}
async function openSettings(page) {
    await page.getByRole('button', {name: 'Settings', exact: true}).click();
    await page.getByText('Enable Aether UI', {exact: true}).waitFor();
    await page.waitForFunction(() => {
        const e = [...document.querySelectorAll('.fi-modal-window')].find(e => e.getClientRects().length);
        return e && e.getBoundingClientRect().right <= innerWidth + 1;
    });
}
async function section(page, name) {
    await page.locator('.fi-section-header').filter({has: page.getByRole('heading', {name, exact: true})}).click();
}
(async () => {
    fixtureCommand('prepare');
    const samples = [];
    let browser;
    try {
        for (let i = 1; i <= 9; i++) {
            const id = 'aether-qa-sample-' + i;
            const folder = path.join(fixture, 'plugins', id);
            fs.mkdirSync(folder);
            samples.push(folder);
            fs.writeFileSync(path.join(folder, 'plugin.json'), JSON.stringify({id, name: 'Example workspace extension ' + i, author: 'Example author and community contributors with a very long display name', description: 'A sample description that checks wrapping across narrow screens and wide tables.', version: '1.0.0', category: 'plugin', namespace: 'AetherSample' + i, class: 'ExamplePlugin', panel_version: '1.0.0-rc.2', api_version: 1}));
        }
        browser = await chromium.launch({headless: true, ...(process.env.AETHER_TEST_CHROME ? {executablePath: process.env.AETHER_TEST_CHROME} : {})});
        const context = await browser.newContext({viewport: {width: 1440, height: 1000}, colorScheme: 'dark'});
        const page = await context.newPage();
        page.setDefaultTimeout(45000);
        page.setDefaultNavigationTimeout(60000);
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.goto(base + '/login');
        await settled(page);
        check((await page.locator('html').getAttribute('class')).includes('aether-ui'), 'theme loaded');
        for (const mode of ['dark', 'light']) {
            await page.evaluate(mode => document.documentElement.classList.toggle('dark', mode === 'dark'), mode);
            for (const width of widths) {
                await page.setViewportSize({width, height: 1000});
                await measure(page, 'login ' + mode + ' ' + width);
                if (width === 1440 || width === 390) await page.screenshot({path: path.join(root, 'docs/pelican-login-' + mode + '-' + width + '.png'), animations: 'disabled'});
            }
        }
        console.log('PASS: login at six widths in both modes.');
        await page.setViewportSize({width: 1440, height: 1000});
        const auth = JSON.parse(fs.readFileSync(process.env.AETHER_TEST_AUTH, 'utf8'));
        await page.locator('input[id="form.login"]').fill(auth.email);
        await page.locator('input[type=password]').fill(auth.password);
        await page.locator('button[type=submit]').click();
        await page.waitForURL(url => !url.pathname.includes('login'));
        await page.goto(base + '/admin/plugins');
        await settled(page);
        check((await page.locator('table').getAttribute('class')).includes('fi-ta-table-stacked-on-mobile'), 'native stacked table enabled');
        for (const mode of ['dark', 'light']) {
            await page.evaluate(mode => document.documentElement.classList.toggle('dark', mode === 'dark'), mode);
            for (const width of widths) {
                await page.setViewportSize({width, height: 1000});
                await measure(page, 'plugins ' + mode + ' ' + width);
                check(await page.getByRole('button', {name: 'Settings', exact: true}).isVisible(), 'settings action remains visible');
                if (width === 1440 || width === 390) await page.screenshot({path: path.join(root, 'docs/pelican-plugins-' + mode + '-' + width + '.png'), animations: 'disabled'});
            }
        }
        console.log('PASS: tables at six widths in both modes.');
        await page.setViewportSize({width: 1440, height: 1000});
        await page.evaluate(() => document.documentElement.classList.add('dark'));
        await openSettings(page);
        for (const width of widths) {
            await page.setViewportSize({width, height: 1000});
            await measure(page, 'settings ' + width);
        }
        await page.setViewportSize({width: 1440, height: 1000});
        await page.screenshot({path: path.join(root, 'docs/pelican-settings.png'), animations: 'disabled'});
        await section(page, 'Branding');
        await page.getByLabel('Brand name', {exact: true}).fill('Aether QA Verified');
        await page.getByRole('button', {name: 'Submit', exact: true}).click();
        await page.getByText('Aether UI: settings saved', {exact: true}).waitFor();
        await page.reload();
        await settled(page);
        check((await page.title()).includes('Aether QA Verified'), 'saved brand survives reload');
        fixtureCommand('locale', 'es');
        await page.reload();
        await settled(page);
        await page.getByRole('button', {name: 'Ajustes', exact: true}).click();
        await page.getByText('Activar Aether UI', {exact: true}).waitFor();
        check(await page.getByText('Tipografía', {exact: true}).isVisible(), 'Spanish settings follow authenticated user');
        await page.getByRole('button', {name: 'Cancelar', exact: true}).click();
        fixtureCommand('locale', 'en');
        await page.reload();
        await settled(page);
        console.log('PASS: settings persistence and per-user language.');
        const build = JSON.parse(fs.readFileSync(path.join(fixture, 'public/build/manifest.json'), 'utf8'));
        const terminalResult = await page.evaluate(async files => {
            const css = document.createElement('link'); css.rel = 'stylesheet'; css.href = '/build/' + files.css; document.head.append(css);
            await import('/build/' + files.js);
            const target = document.createElement('div'); target.id = 'terminal'; target.style.width = '800px'; target.style.height = '320px'; document.querySelector('.fi-main').append(target);
            const terminal = new window.Xterm.Terminal({fontSize: 11, disableStdin: true});
            const fit = new window.Xterm.FitAddon(); terminal.loadAddon(fit); terminal.open(target); fit.fit();
            await document.fonts.ready;
            await new Promise(resolve => terminal.write('Aether xterm integration\r\n\x1b[32mANSI OK\x1b[0m\r\n' + 'Long output '.repeat(90), resolve));
            await new Promise(resolve => setTimeout(resolve, 250));
            const initialCols = terminal.cols;
            target.style.width = '280px';
            await new Promise(resolve => setTimeout(resolve, 350));
            const screen = target.querySelector('.xterm-screen').getBoundingClientRect();
            const bounds = target.getBoundingClientRect();
            const result = {initialCols, narrowCols: terminal.cols, fontSize: terminal.options.fontSize, bg: terminal.options.theme.background, disableStdin: terminal.options.disableStdin, overflow: screen.right > bounds.right + 1 || screen.left < bounds.left - 1};
            terminal.dispose(); target.remove(); css.remove(); return result;
        }, {js: build['resources/js/console.js'].file, css: build['resources/css/console.css'].file});
        check(terminalResult.narrowCols < terminalResult.initialCols && terminalResult.narrowCols > 0, 'console refits without window resize');
        check(!terminalResult.overflow && terminalResult.fontSize === 14 && terminalResult.bg === '#0c1018' && terminalResult.disableStdin, 'console appearance and command options preserved: ' + JSON.stringify(terminalResult));
        await page.evaluate(() => {
            const sample = document.createElement('section'); sample.id = 'aether-native-sample'; sample.className = 'fi-sc-tabs fi-contained';
            const tabs = document.createElement('nav'); tabs.className = 'fi-tabs';
            for (const name of ['Overview', 'Basic settings', 'Advanced configuration', 'Configuration file', 'Diagnostics and connection details']) {
                const button = document.createElement('button'); button.className = 'fi-tabs-item'; button.innerHTML = '<span class="fi-tabs-item-label"></span>'; button.firstChild.textContent = name; tabs.append(button);
            }
            sample.append(tabs);
            const stat = document.createElement('div'); stat.className = 'fi-small-stat-block'; stat.textContent = 'Long server identifier ' + 'abcdef1234567890'.repeat(8); sample.append(stat);
            document.querySelector('.fi-main').append(sample);
        });
        for (const width of widths) {
            await page.setViewportSize({width, height: 1000});
            await measure(page, 'native component sample ' + width);
        }
        check(errors.length === 0, 'JavaScript errors: ' + errors.join('\n'));
        console.log('PASS: ' + checks + ' browser checks; login, tables, settings, locale, persistence, xterm and native component samples.');
    } finally {
        if (browser) await browser.close();
        for (const folder of samples) {
            fs.unlinkSync(path.join(folder, 'plugin.json'));
            fs.rmdirSync(folder);
        }
        fixtureCommand('restore');
    }
})().catch(error => { console.error(error); process.exitCode = 1; });

