/**
 * G2G 竞品数据爬虫脚本
 *
 * 使用 Puppeteer 无头浏览器渲染页面，提取 G2G 店铺报价数据。
 *
 * 用法：
 *   node scripts/crawl_g2g.mjs --url="https://www.g2g.com/cn/categories/wow-gold/..."
 *   node scripts/crawl_g2g.mjs --file="/tmp/page.html"  （离线调试模式）
 *
 * 输出：JSON 数组，字段如下：
 *   store_name, store_url, store_level, stock, price, currency
 */

import puppeteer from 'puppeteer';
import { parseArgs } from 'node:util';
import fs from 'node:fs';

// ==================== 参数解析 ====================

const { values: args } = parseArgs({
  options: {
    url:  { type: 'string' },
    file: { type: 'string' },
    headless: { type: 'boolean', default: true },
  },
});

const url  = args.url;
const file = args.file;
const headless = args.headless !== false;

// ==================== 数据提取逻辑 ====================

/**
 * 在浏览器上下文中提取店铺卡片数据
 */
function extractCards() {
  const cards = document.querySelectorAll('#pcOtherOffer .other-seller--gradient');
  const results = [];

  cards.forEach((card) => {
    // 店铺名称
    const nameEl   = card.querySelector('.other-seller__store .ellipsis');
    const storeName = nameEl?.textContent?.trim() ?? '';

    // 店铺链接
    const linkEl  = card.querySelector('.other-seller__store a');
    const storeUrl = linkEl?.getAttribute('href') ?? '';

    // 店铺等级（如 "等级 151"）
    const levelEl  = card.querySelector('.text-caption.text-secondary.text-weight-medium');
    // 提取 "等级 xxx" 中的数字部分，兼容不同格式
    const levelText = levelEl?.textContent?.trim() ?? '';

    // ====== Badge 区：库存、最低量、交付时间等 ======
    const badges = card.querySelectorAll('.q-badge.bg-neutral-200');
    let stock = '';

    // 规则：第2个 badge 通常是库存量（在"最低 xxx"之后）
    // G2G 的 badge 顺序：[最低300] [9.9M] [10分钟] [锤子图标] [邮件图标] [多人图标] [金币图标]
    // 库存是第2个 badge
    if (badges.length >= 2) {
      stock = badges[1]?.textContent?.trim() ?? '';

      // 如果第2个看起来不像库存（含中文），尝试检查第3个
      if (/[\u4e00-\u9fa5]/.test(stock) && badges.length >= 3) {
        stock = badges[2]?.textContent?.trim() ?? '';
      }
    }

    // 库存是纯数字+单位（如 "9.9M"），如果不是这个格式，尝试从所有badge中找
    if (!/^[\d.]+[KMB]$/i.test(stock)) {
      for (const badge of badges) {
        const text = badge.textContent?.trim() ?? '';
        if (/^[\d.]+[KMB]$/i.test(text)) {
          stock = text;
          break;
        }
      }
    }

    // ====== 价格 ======
    const priceEl  = card.querySelector('.text-primary.text-body.text-weight-bold');
    const priceText = priceEl?.textContent?.trim() ?? '0';
    const price    = parseFloat(priceText.replace(/,/g, '')) || 0;

    // 币种
    const currencyEl = card.querySelector('.text-secondary.text-body2.text-weight-medium');
    const currency   = currencyEl?.textContent?.trim() ?? 'USD';

    // 过滤空行
    if (storeName) {
      results.push({
        store_name: storeName,
        store_url: storeUrl,
        store_level: levelText,
        stock: stock,
        price: price,
        currency: currency,
      });
    }
  });

  return results;
}

// ==================== 主流程 ====================

async function main() {
  let results = [];

  if (file) {
    // ---- 离线模式：从文件读取HTML ----
    const html = fs.readFileSync(file, 'utf-8');
    const browser = await puppeteer.launch({ headless: true });
    const page = await browser.newPage();
    await page.setContent(html, { waitUntil: 'networkidle2', timeout: 15000 });
    results = await page.evaluate(extractCards);
    await browser.close();
  } else if (url) {
    // ---- 在线模式：直接打开G2G页面 ----
    const browser = await puppeteer.launch({
      headless: headless ? 'new' : false,
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-blink-features=AutomationControlled',
        '--disable-web-security',
      ],
    });

    const page = await browser.newPage();

    // 伪装成正常浏览器
    await page.setUserAgent(
      'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36'
    );
    await page.setExtraHTTPHeaders({
      'Accept-Language': 'zh-CN,zh;q=0.9,en;q=0.8',
    });
    await page.setViewport({ width: 1440, height: 900 });

    try {
      await page.goto(url, {
        waitUntil: 'networkidle2',
        timeout: 30000,
      });

      // 等待 #pcOtherOffer 区域出现
      await page.waitForSelector('#pcOtherOffer', { timeout: 15000 });

      // 额外等待渲染完成
      await new Promise(r => setTimeout(r, 2000));

      // 滚动触发懒加载
      await page.evaluate(() => {
        const container = document.querySelector('#pcOtherOffer');
        if (container) {
          container.scrollIntoView({ behavior: 'instant', block: 'start' });
        }
      });
      await new Promise(r => setTimeout(r, 1000));

      // 提取数据
      results = await page.evaluate(extractCards);
    } catch (err) {
      // 即使出错也尝试提取已有的数据
      try {
        results = await page.evaluate(extractCards);
      } catch (_) {
        console.error('Error:', err.message);
      }
    } finally {
      await browser.close();
    }
  } else {
    console.error('请提供 --url 或 --file 参数');
    process.exit(1);
  }

  // 输出 JSON 到 stdout（PHP通过 exec 捕获）
  console.log(JSON.stringify(results, null, 2));
}

main().catch((err) => {
  console.error('Fatal:', err.message);
  process.exit(1);
});
