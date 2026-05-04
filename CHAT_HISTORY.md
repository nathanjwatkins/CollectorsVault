# CollectorVault — Chat History & Development Log

> This file is maintained across all sessions. Each session appends its own section.
> Future chats should read this file first to understand the current state of the project.

---

## PROJECT OVERVIEW

**CollectorVault** — A multi-user web app for scanning and cataloguing physical collectibles.
- **Live URL:** https://collectorsvault.store
- **Stack:** PHP 8 + vanilla JS/CSS, flat CSV storage, no framework
- **Hosting:** Hostinger shared hosting, LiteSpeed/PHP 8.3
- **Repo:** github.com/nathanjwatkins/CollectorsVault (private, branch: main)
- **Local (Claude sandbox):** /home/claude/project/

**Users:** Nate (password1), Hannah, vividpixel, Claude (claude1)

**Categories:** Trading Cards, Football Shirts, Video Games, Vinyl Records, Other

---

## DEPLOY PIPELINE (WORKING ✅)

- GitHub push → webhook → `https://collectorsvault.store/deploy.php`
- `deploy.php` reads token from server only (never committed to repo)
- Token stored in `deploy.php` on Hostinger server at `public_html/deploy.php`
- **Current token (CollectorVault Deploy V4):** `[REDACTED - stored in deploy.php on server only]`
- Webhook ID: 609003115
- Deploys: scanner.php, collection.php, shared.css, api.php, categories.js.php, index.php, theme.php, logout.php, nav.php

**Known deploy issues:**
- GitHub requires 2FA (GitHub Mobile) to access webhook settings — causes friction when redelivering
- Hostinger LiteSpeed aggressively caches PHP — fixed with `.htaccess` `CacheLookup off`
- GitHub occasionally fails to connect to Hostinger ("failed to connect to host") — transient, resolves itself
- Pushes that contain `ghp_` tokens are blocked by GitHub secret scanning — fixed by storing token only on server

---

## SESSION 1 — May 2026

### What Was Done

#### 1. Token Rotation & Deploy Pipeline Fix
- **Problem:** Multiple GitHub token rotations due to secret scanning blocking pushes
- **Root cause:** `beta_deploy.php` had token baked in as base64 and was committed to repo
- **Solution:** Rewrote `deploy.php` to read token from server file; removed token from all repo files
- **Token history:** V2 → V3 (revoked) → V4 (current, stored in deploy.php on server only)
- Webhook URL changed from `beta_deploy.php` to `deploy.php`

#### 2. Beta Site Scrapped
- User requested: migrate only the **collection page styling** from beta, discard everything else
- Beta folder (`/beta/`) deleted from Hostinger
- `beta_deploy.php` no longer used

#### 3. Collection Page Migration (beta → live)
- Migrated beta `collection.php` styling to live site
- **Issue:** Beta CSS used different class names (`cv-app`, `cv-sidebar`, `--acid`, `--void` etc) not in live `shared.css`
- **Fix:** Embedded beta's `shared.css` directly inside `collection.php` to make it self-contained
- **Issue:** `collection.php` DOMContentLoaded was calling `_renderThemeIcon(t)` which was deleted when theme toggle was removed — crashed `loadAll()` silently
- **Fix:** Removed the `_renderThemeIcon` call from DOMContentLoaded
- **Issue:** `buildToolbarTabs()` called `CATS` which was undefined — crashed before items loaded
- **Fix:** Inlined the CATS definition directly inside `buildToolbarTabs()`
- **Issue:** Hostinger LiteSpeed was caching old PHP — items not loading
- **Fix:** Added `.htaccess` with `CacheLookup off` and no-cache headers for PHP files

#### 4. Scanner Page
- Accidentally replaced live scanner with beta scanner — reverted to original (glassmorphism carousel)
- Original scanner uses: horizontal top nav, glassmorphism glass cards per category, Geist font
- Beta scanner (discarded): sidebar nav, dark category tile grid, Outfit font

#### 5. Dark Mode — Always Dark
- Removed theme toggle entirely from all pages (scanner, collection, index, nav)
- `theme.php` now forces `data-theme=dark` always
- `shared.css` dark mode vars made permanent via `:root, [data-theme="dark"] {}` 
- Left panel on scanner forced solid dark (`#0E0D0B`, no backdrop-filter)
- Category bar and nav bar forced dark

#### 6. Recent Scans Panel Restyled
- After clicking a carousel tile, the scan view's right panel (Recent Scans) now matches collection page style
- Dark cards with: numbered index (acid green), category label, item name overlay, price + type badge
- `renderRecent()` JS function updated with new card markup

#### 7. eBay Pricing — Fixed
- **Problem:** eBay returning 403 for completed/sold listings from Hostinger IP
- **Diagnosis:** Hostinger's IP range is blocked by eBay for sold listings only; regular search works
- **Fix:** Fallback chain — try sold listings → if 403, fall to active listings → if empty, shorter query
- curlGet upgraded: full browser headers, cookie jar, gzip encoding
- Added `testEbay` debug endpoint at `/api.php?action=testEbay&q=query`

#### 8. PriceCharting Integration (Partial)
- Tested: PriceCharting returns 200 from Hostinger but body is empty (gzip/brotli compression issue)
- Integrated `fetchPriceCharting()` function in `api.php` — calls `/api/products` and `/api/product` JSON endpoints
- Category passed through to `fetchEbayPrice()` — cards/games try PriceCharting first
- **Remaining issue:** PriceCharting responses have `len:0` — compression not being decoded
- eBay sold listings are currently working so prices flow through regardless

#### 9. Item Edit Fixed
- **Problem:** Edit form called `action=update` but that endpoint didn't exist in `api.php`
- **First attempt:** Added `doUpdate()` but called `writeCSV` without headers → wiped collection CSV
- **Fix:** `doUpdate()` now reads actual CSV headers from file before writing, never changes structure
- Also updates `prices.csv` if `ebay_query` field was changed
- CSV was restored from Hostinger backup after the wipe incident

#### 10. Source Price Testing
- Added `testSources` endpoint to test site accessibility from Hostinger
- Results: PriceCharting ✅, Discogs ❌ (403), CardMarket ❌ (403), ClassicFootballShirts ❌ (403)
- eBay sold listings: intermittently blocked (403), currently working
- User has applied for eBay developer API — awaiting approval

---

## CURRENT STATE (End of Session 1)

### Working ✅
- Deploy pipeline: GitHub push → live site in ~10 seconds
- Collection page: loads all items, dark styling, category tabs, search, sort, images
- Scanner carousel: original glassmorphism design intact
- Recent scans panel: styled to match collection page
- Item editing: saves correctly without corrupting CSV
- Prices: eBay active + sold listings (sold intermittently blocked by IP)
- Images: eBay listing images fetching and caching correctly
- Dark mode: forced everywhere, no toggle

### Known Issues / Pending
- **PriceCharting compression:** Returns 200 but empty body — brotli encoding not decoded by Hostinger curl. Fix: change Accept-Encoding to gzip only or use a different endpoint.
- **eBay 403 on sold listings:** Intermittent IP block. When blocked, falls back to active listings (less accurate). Permanent fix: eBay Finding API (user awaiting approval).
- **Image loading:** Some items show placeholder icon — images cached on 7-day TTL, older items may not have cached images yet.
- **GitHub 2FA on webhook settings:** Every time webhook settings are accessed, requires GitHub Mobile approval. Workaround: make a new push to trigger fresh webhook rather than redelivering.

---

## KEY LEARNINGS & GOTCHAS

### Deploy
- Never commit tokens to repo — GitHub secret scanning will block pushes and auto-revoke tokens
- Store token directly in `deploy.php` on server — file is never committed
- `deploy.php` intentionally not in its own file list (won't overwrite itself)
- LiteSpeed caches PHP aggressively — always add `.htaccess` with `CacheLookup off`

### PHP / CSV
- `writeCSV($file, $rows, $headers)` requires 3 args — headers must match the actual CSV file headers exactly
- Never use `array_keys(csvHeaders())` to rewrite an existing CSV — the function returns different field names than what's in the actual file
- Always read headers from the file first: `$h = fopen(FILE,'r'); $headers = fgetcsv($h); fclose($h);`
- `foreach ($rows as &$row)` — must `unset($row)` after to avoid reference bugs

### CSS / Dark Mode
- Embedded CSS (self-contained pages) avoids all shared.css conflicts
- `collection.php` and future self-contained pages don't load `shared.css` at all
- When removing theme toggle: check ALL DOMContentLoaded handlers for `_renderThemeIcon` calls
- When removing theme toggle: check ALL DOMContentLoaded handlers for `CATS` being undefined

### eBay
- Sold/completed listings (`LH_Sold=1&LH_Complete=1`) are blocked from Hostinger IPs — 403
- Regular search (`/sch/i.html`) works fine — use as fallback
- Curl must use `CURLOPT_COOKIEJAR` + `CURLOPT_COOKIEFILE` to maintain session
- Encode as `gzip, deflate` only — brotli (`br`) may not be supported

### JavaScript
- Unescaped apostrophes in JS single-quoted strings crash the page silently
- `allItems` array is populated by `loadAll()` — only available after async load completes

---

## FILE STRUCTURE (Current)

```
public_html/
├── api.php              — All backend: auth, Gemini, eBay, CSV CRUD, update, prices
├── scanner.php          — AI scanner with glassmorphism carousel (original live design)
├── collection.php       — Self-contained: beta dark styling + beta CSS embedded inline
├── shared.css           — Live site global CSS (used by scanner + nav only)
├── nav.php              — Original top nav (horizontal bar, Scan + Collection links)
├── theme.php            — Forces data-theme=dark always (no toggle)
├── index.php            — Login/register page (dark)
├── logout.php           — Session destroy + redirect
├── categories.js.php    — Per-category Gemini prompts and field definitions
├── deploy.php           — GitHub webhook receiver (token stored here, not in repo)
├── .htaccess            — LiteSpeed cache disabled, no-cache headers for PHP
└── data/
    ├── collection.csv   — All scanned items
    ├── prices.csv       — eBay price cache
    ├── images.csv       — eBay image URL cache (7-day TTL)
    └── .htaccess        — Deny from all
```

---

## API ENDPOINTS (api.php)

| Action | Method | Description |
|--------|--------|-------------|
| login | POST | Sets session |
| logout | POST | Destroys session |
| register | POST | Creates user |
| whoami | GET | Returns current user |
| scan | POST | Proxies to Gemini |
| save | POST | Appends to collection.csv |
| collection | GET | Returns user's items |
| delete | POST | Removes item + caches |
| update | POST | Updates item fields in CSV |
| stats | GET | Returns totals/stats |
| getImage | GET | eBay image URL, cached 7d |
| refreshPrices | POST | Scrapes eBay prices |
| getPrices | GET | Returns cached prices |
| searchEbay | POST | eBay search |
| linkEbayQuery | POST | Pins custom eBay search query |
| testEbay | GET | Debug: test eBay response from server |
| testSources | GET | Debug: test multiple price sites |

---

## GIT LOG (Key Commits This Session)

```
4fe2fb8  Fix doUpdate: preserve original CSV headers
c399a89  Add missing update action to api.php
d62fa15  Fix PriceCharting: explicit gzip encoding
cc767bd  Fix syntax error in api.php
986d45f  Integrate PriceCharting API for games/cards
f5b6cef  Disable LiteSpeed PHP caching via .htaccess
abe66ff  Fix: add missing CATS definition to collection.php
e7f6a5a  Fix: remove _renderThemeIcon call breaking collection load
c8c7c52  Fix eBay scraping: fallback to active listings
97928f1  Upgrade eBay scraper: better browser spoofing
f709126  Force dark everywhere: nav, left panel, category bar
cb7cbcc  Remove theme toggle everywhere - always dark mode
b1df525  Restyle recent scans to match collection page
ad4120e  Fix scanner: embed CSS, remove all /beta/ paths
2a67c14  Restore original live scanner.php (glassmorphism carousel)
d8340bc  Embed beta CSS into collection.php - fully self-contained
083ca09  deploy.php: fetch from GitHub raw, live site only, no beta
ee89ebb  Remove token from source: read from cv_token.txt on server
```
