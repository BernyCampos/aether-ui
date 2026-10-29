const {chromium} = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const os = require('node:os');
const {execFileSync} = require('node:child_process');
const root = path.resolve(__dirname, '..');
const fixture = process.env.AETHER_TEST_PANEL;
if (!fixture || !fs.existsSync(path.join(fixture, '.aether-isolated-test'))) {
    throw new Error('Set AETHER_TEST_PANEL to a marked disposable panel.');
}
const output = process.env.AETHER_TABLE_OUTPUT || fs.mkdtempSync(path.join(os.tmpdir(), 'aether-tables-'));
fs.mkdirSync(output, {recursive:true});
execFileSync(process.env.AETHER_TEST_PHP || 'php', [path.join(__dirname, 'table-fixture.php')], {env:{...process.env,AETHER_TABLE_OUTPUT:output}});
(async () => {
    const browser = await chromium.launch({headless:true, executablePath:process.env.AETHER_TEST_CHROME || undefined});
    try {
        const page = await browser.newPage({viewport:{width:1440,height:1000}});
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        const publicRoot = path.resolve(fixture, 'public');
        await page.route('**/*', async route => {
            const pathname = new URL(route.request().url()).pathname;
            if (pathname === '/') return route.fulfill({contentType:'text/html',body:'<!doctype html><html class="aether-ui dark"><head></head><body><main id="sample" style="padding:16px"></main></body></html>'});
            const file = path.resolve(publicRoot, '.' + pathname);
            if (!file.startsWith(publicRoot + path.sep) || !fs.existsSync(file)) return route.fulfill({status:404,body:''});
            await route.fulfill({body:fs.readFileSync(file),contentType:file.endsWith('.css')?'text/css':file.endsWith('.js')?'text/javascript':'application/octet-stream'});
        });
        await page.goto('http://aether.test/');
        const manifest = JSON.parse(fs.readFileSync(path.join(publicRoot,'build/manifest.json'),'utf8'));
        await page.addStyleTag({url:'/build/'+manifest['resources/css/app.css'].file});
        await page.addStyleTag({content:fs.readFileSync(path.join(root,'docs/preview.html'),'utf8').match(/<style>(html\.aether-ui\{[\s\S]*?)<\/style>/)[1]});
        await page.addStyleTag({content:fs.readFileSync(path.join(root,'resources/css/aether.css'),'utf8')});
        let checks = 0;
        let hydrated = false;
        for (const kind of ['files','empty','tickets']) {
            await page.locator('#sample').evaluate((el,html)=>el.innerHTML=html,fs.readFileSync(path.join(output,'table-'+kind+'.html'),'utf8'));
            if (!hydrated) {
                await page.locator('#sample style').evaluateAll(nodes=>nodes.forEach(node=>document.head.append(node)));
                await page.addScriptTag({url:'/js/filament/support/support.js'});
                await page.addScriptTag({url:'/js/filament/tables/tables.js'});
                await page.addScriptTag({url:'/js/filament/actions/actions.js'});
                await page.evaluate(()=>window.livewireScriptConfig={uri:'/livewire/update',csrf:'local-fixture',progressBar:false,nonce:''});
                await page.addScriptTag({content:fs.readFileSync(path.join(fixture,'vendor/livewire/livewire/dist/livewire.js'),'utf8')});
                await page.evaluate(()=>Livewire.start());
                hydrated = true;
            }
            await page.waitForTimeout(400);
            for (const font of [16,20]) {
                await page.evaluate(size=>document.documentElement.style.setProperty('--aether-font-size',size+'px'),font);
                for (const width of [320,390,768,1024,1440,1920,3080]) {
                    await page.setViewportSize({width,height:1000});
                    const result = await page.evaluate(() => {
                        const table=document.querySelector('table');
                        const overflow=[...document.querySelectorAll('.fi-ta-ctn,.fi-ta-content,.fi-ta-table')].filter(el=>el.clientWidth && el.scrollWidth>el.clientWidth+1).map(el=>({class:el.className,width:el.clientWidth,scroll:el.scrollWidth}));
                        const row=table.querySelector('tbody > tr');
                        const buttons=[...(row?.querySelector('.fi-ta-actions')?.children || [])].map(el=>el.getBoundingClientRect().y);
                        return {overflow,doc:document.documentElement.scrollWidth,viewport:innerWidth,rowHeight:row?.getBoundingClientRect().height,buttons,headers:[...table.querySelectorAll('.fi-ta-header-cell')].filter(el=>el.getClientRects().length).map(el=>({label:el.textContent.trim(),height:el.getBoundingClientRect().height}))};
                    });
                    assert.deepEqual(result.overflow,[],kind+' '+font+' '+width+' '+JSON.stringify(result)); checks++;
                    assert.ok(result.doc<=result.viewport,kind+' document overflow'); checks++;
                    if (kind==='files') {
                        assert.ok(result.rowHeight<=64,'compact row: '+JSON.stringify(result)); checks++;
                        assert.ok(Math.max(...result.buttons)-Math.min(...result.buttons)<12,'horizontal actions: '+JSON.stringify(result)); checks++;
                    } else {
                        assert.ok(result.headers.every(header=>header.height<80),'readable headers: '+JSON.stringify(result)); checks++;
                    }
                    if (font===16 && [390,1440].includes(width)) await page.locator('.fi-ta-ctn').screenshot({path:path.join(output,kind+'-fixed-'+width+'.png'),animations:'disabled'});
                }
            }
            if (kind === 'files') {
                for (const width of [390,1440]) {
                    await page.setViewportSize({width,height:1000});
                    await page.locator('.fi-ta-actions .fi-dropdown-trigger').first().click();
                    const rename = page.getByRole('menuitem', {name:'Rename',exact:true});
                    await rename.waitFor({state:'visible'});
                    const bounds = await rename.boundingBox();
                    assert.ok(bounds.x>=0 && bounds.x+bounds.width<=width, 'File menu fits viewport'); checks++;
                    await page.keyboard.press('Escape');
                    await rename.waitFor({state:'hidden'}); checks++;
                }
            }
        }
        assert.deepEqual(errors,[], 'Native table client errors');
        console.log('PASS: '+checks+' native Filament layout checks; compact file rows, inline actions, empty/populated ticket headers, no horizontal overflow at seven widths and two font sizes.');
    } finally { await browser.close(); }
})().catch(error=>{console.error(error);process.exitCode=1;});
