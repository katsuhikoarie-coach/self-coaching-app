import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage();
await page.goto('https://yamano-order.shop/Form/Login.aspx', { waitUntil: 'domcontentloaded', timeout: 30000 });

const info = await page.evaluate(() => {
  const inputs = Array.from(document.querySelectorAll('input')).map(el => ({
    type: el.type, name: el.name, id: el.id,
    placeholder: el.placeholder, autocomplete: el.autocomplete,
  }));
  const buttons = Array.from(document.querySelectorAll('button, input[type="submit"], input[type="button"], a[href*="login"], a[href*="Login"]')).map(el => ({
    tag: el.tagName, type: el.getAttribute('type') || '',
    name: el.name || '', id: el.id || '',
    value: el.value || '', text: el.textContent?.trim().slice(0, 40) || '',
    href: el.href || '',
  }));
  const labels = Array.from(document.querySelectorAll('label')).map(el => ({
    for: el.htmlFor, text: el.textContent?.trim().slice(0, 40),
  }));
  const forms = Array.from(document.querySelectorAll('form')).map(f => ({
    id: f.id, action: f.action, method: f.method,
  }));
  return { url: location.href, forms, inputs, buttons, labels };
});

console.log(JSON.stringify(info, null, 2));
await browser.close();
