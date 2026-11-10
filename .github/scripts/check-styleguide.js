// Loads the built styleguide in Chrome and fails on uncaught errors or console errors of the examples.
// Usage: node .github/scripts/check-styleguide.js <url>
const {chromium} = require('playwright-core');

const url = process.argv[2] || 'http://localhost:8080/';

(async () => {
    const browser = await chromium.launch({channel: 'chrome'});
    const page = await browser.newPage();
    const errors = [];

    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => {
        if (message.type() === 'error' && !message.text().startsWith('Failed to load resource')) {
            errors.push(message.text());
        }
    });

    await page.goto(url);
    await page.waitForTimeout(10000);
    await browser.close();

    if (errors.length > 0) {
        console.error(`Styleguide has ${errors.length} error(s):\n- ${[...new Set(errors)].join('\n- ')}`);
        process.exit(1);
    }

    console.log('Styleguide examples render without errors.');
})();
