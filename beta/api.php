<?php
/**
 * CollectorVault API
 * Fix: ob_start + SameSite cookie config for HTTPS (fixes login on iOS/Safari)
 */
ob_start();

// ── Session cookie config MUST be before session_start ────────────────────────
// Required for HTTPS sites - without Secure+SameSite, iOS Safari blocks cookies
session_name('CVBETA');
ini_set('session.cookie_path', '/beta/');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
session_start();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// ── CONFIG ────────────────────────────────────────────────────────────────────
define('CV_VER', '1776877263'); // Cache-bust version — update on each deploy
define('GEMINI_KEY',     'AIzaSyAGMG88ej3QSwdhK2PkNpw0-rVv8IkxZJE');
define('GOOGLE_API_KEY', 'AIzaSyAGMG88ej3QSwdhK2PkNpw0-rVv8IkxZJE');
define('OPENAI_KEY',     ''); // ← paste your OpenAI key here to enable GPT-4o fallback

// Gemini models tried in order — separate capacity pools, same free key
// Note: gemini-1.5-x models are shut down. gemini-2.0-flash shuts down June 1 2026.
define('GEMINI_MODELS', [
    'gemini-2.5-flash',      // primary  — current stable, best quality
    'gemini-2.5-flash-lite', // fallback — budget tier, separate capacity pool
    'gemini-2.0-flash',      // fallback — retiring June 2026, keep until then
]);
define('GOOGLE_CSE_ID', 'YOUR_CSE_ID_HERE');
define('DATA_DIR',        __DIR__ . '/../data/');  // Share live site data
define('UPLOADS_DIR',     __DIR__ . '/../uploads/');  // Share live site uploads
define('USERS_FILE',      DATA_DIR . 'users.csv');
define('COLLECTION_FILE', DATA_DIR . 'collection.csv');
define('PRICES_FILE',     DATA_DIR . 'prices.csv');
define('IMAGES_FILE',     DATA_DIR . 'images.csv');

foreach ([DATA_DIR, UPLOADS_DIR] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}
if (!file_exists(DATA_DIR . '.htaccess')) file_put_contents(DATA_DIR . '.htaccess', "Deny from all\n");
if (!file_exists(UPLOADS_DIR . '.htaccess')) file_put_contents(UPLOADS_DIR . '.htaccess', "Allow from all\n");

// ── ROUTER ────────────────────────────────────────────────────────────────────
$action = $_POST['action'] ?? $_GET['action'] ?? '';
switch ($action) {
    case 'login':         doLogin();        break;
    case 'logout':        doLogout();       break;
    case 'register':      doRegister();     break;
    case 'whoami':        doWhoami();       break;
    case 'scan':          doScan();         break;
    case 'save':          doSave();         break;
    case 'collection':    doCollection();   break;
    case 'delete':        doDelete();       break;
    case 'stats':         doStats();        break;
    case 'getImage':      doGetImage();     break;
    case 'refreshPrices': doRefreshPrices();break;
    case 'getPrices':     doGetPrices();    break;
    case 'searchEbay':    doSearchEbay();   break;
    case 'linkEbayQuery': doLinkEbayQuery();break;
    default: json(['error' => 'Unknown action: ' . $action]);
}

// ── AUTH ──────────────────────────────────────────────────────────────────────
function doLogin() {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$username || !$password) json(['error' => 'Username and password required'], 400);
    $users = readCSV(USERS_FILE);
    foreach ($users as $user) {
        if (strtolower($user['username']) === strtolower($username)) {
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user']    = $user['username'];
                $_SESSION['user_id'] = $user['id'];
                json(['ok' => true, 'username' => $user['username']]);
            }
        }
    }
    json(['error' => 'Invalid username or password'], 401);
}

function doLogout() { session_destroy(); json(['ok' => true]); }

function doRegister() {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (strlen($username) < 3) json(['error' => 'Username must be at least 3 characters'], 400);
    if (strlen($password) < 6) json(['error' => 'Password must be at least 6 characters'], 400);
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) json(['error' => 'Username: letters, numbers and underscores only'], 400);
    $users = readCSV(USERS_FILE);
    foreach ($users as $u) {
        if (strtolower($u['username']) === strtolower($username)) json(['error' => 'Username already taken'], 409);
    }
    appendCSV(USERS_FILE, [
        'id'            => uniqid('u_', true),
        'username'      => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'created'       => date('Y-m-d H:i:s'),
    ], ['id','username','password_hash','created']);
    json(['ok' => true]);
}

function doWhoami() {
    if (!isset($_SESSION['user'])) json(['user' => null]);
    json(['user' => $_SESSION['user'], 'user_id' => $_SESSION['user_id']]);
}

function requireAuth() {
    if (!isset($_SESSION['user'])) json(['error' => 'Not authenticated'], 401);
}

// ── GEMINI SCAN ───────────────────────────────────────────────────────────────

/**
 * Returns true if a Gemini response is a capacity/rate-limit error,
 * meaning we should try the next model rather than give up.
 */
function isCapacityError(array $resp, array $data): bool {
    // 404 = model not found — hard failure, never a capacity issue
    if ($resp['code'] === 404) return false;
    if (in_array($resp['code'], [429, 503], true)) return true;
    $msg = strtolower($data['error']['message'] ?? '');
    foreach (['high demand','overloaded','resource exhausted','quota exceeded',
              'temporarily unavailable','try again later','rate limit'] as $phrase) {
        if (strpos($msg, $phrase) !== false) return true;
    }
    return false;
}

/**
 * Call a single Gemini model. Returns ['resp' => curlResult, 'data' => decoded].
 */
function callGeminiModel(string $model, string $base64, string $mediaType, string $prompt): array {
    $url     = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . GEMINI_KEY;
    $payload = json_encode([
        'contents' => [['parts' => [
            ['inline_data' => ['mime_type' => $mediaType, 'data' => $base64]],
            ['text' => $prompt],
        ]]],
        'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 8192],
    ]);
    $resp = curlPost($url, $payload, ['Content-Type: application/json']);
    return ['resp' => $resp, 'data' => json_decode($resp['body'] ?? '', true) ?? []];
}

/**
 * Call OpenAI GPT-4o as a last-resort fallback.
 * Returns ['resp' => curlResult, 'data' => decoded] same shape as callGeminiModel.
 */
function callOpenAIFallback(string $base64, string $mediaType, string $prompt): array {
    $payload = json_encode([
        'model'      => 'gpt-4o',
        'max_tokens' => 8192,
        'messages'   => [[
            'role'    => 'user',
            'content' => [
                ['type' => 'image_url', 'image_url' => ['url' => "data:{$mediaType};base64,{$base64}", 'detail' => 'high']],
                ['type' => 'text', 'text' => $prompt],
            ],
        ]],
    ]);
    $resp = curlPost('https://api.openai.com/v1/chat/completions', $payload, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_KEY,
    ]);
    return ['resp' => $resp, 'data' => json_decode($resp['body'] ?? '', true) ?? []];
}

function doScan() {
    requireAuth();
    $base64    = $_POST['base64']    ?? '';
    $mediaType = $_POST['mediaType'] ?? 'image/jpeg';
    $prompt    = $_POST['prompt']    ?? '';
    if (!$base64 || !$prompt) json(['error' => 'Missing image or prompt'], 400);

    // ── Try each Gemini model in order ────────────────────────────────────────
    foreach (GEMINI_MODELS as $model) {
        $result = callGeminiModel($model, $base64, $mediaType, $prompt);
        $resp   = $result['resp'];
        $data   = $result['data'];

        if (!$resp['ok']) {
            // cURL-level network failure — not a capacity issue, bail immediately
            json(['error' => 'Network error: ' . $resp['error']], 502);
        }

        if ($resp['code'] === 200) {
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if ($text) json(['ok' => true, 'text' => $text]);
            // 200 but empty text — fall through to next model
            error_log("CollectorVault: {$model} returned 200 but empty text, trying next.");
            continue;
        }

        if (isCapacityError($resp, $data)) {
            // This model is overloaded — try the next one
            error_log("CollectorVault: {$model} capacity error (HTTP {$resp['code']}), trying next model.");
            continue;
        }

        // Any other non-200 error (bad key, bad request, etc.) — report it directly
        json(['error' => $data['error']['message'] ?? 'Gemini error'], 502);
    }

    // ── All Gemini models at capacity — try OpenAI GPT-4o ─────────────────────
    if (OPENAI_KEY !== '') {
        error_log('CollectorVault: all Gemini models at capacity, trying OpenAI GPT-4o fallback.');
        $result = callOpenAIFallback($base64, $mediaType, $prompt);
        $resp   = $result['resp'];
        $data   = $result['data'];

        if ($resp['ok'] && $resp['code'] === 200) {
            $text = $data['choices'][0]['message']['content'] ?? '';
            if ($text) json(['ok' => true, 'text' => $text]);
        }
        error_log('CollectorVault: OpenAI fallback also failed (HTTP ' . $resp['code'] . ').');
    }

    // ── Everything failed — friendly user message ──────────────────────────────
    json(['error' => 'AI scanning is temporarily unavailable due to high demand. Please wait a moment and try again.'], 503);
}

// ── IMAGE LOOKUP ──────────────────────────────────────────────────────────────
function doGetImage() {
    requireAuth();
    $itemId       = $_GET['id']      ?? '';
    $query        = $_GET['query']   ?? '';
    $category     = $_GET['cat']     ?? '';
    $forceRefresh = !empty($_GET['refresh']);
    if (!$itemId || !$query) json(['error' => 'Missing id or query'], 400);

    if (!$forceRefresh) {
        $cached = getImageCache($itemId);
        if ($cached) json(['ok' => true, 'url' => $cached, 'cached' => true]);
    }

    $suffixes = ['cards'=>'trading card','shirts'=>'football shirt','games'=>'video game','vinyl'=>'vinyl record','other'=>''];
    $searchQuery = trim($query . ' ' . ($suffixes[$category] ?? ''));

    $imageUrl = fetchEbayListingImage($searchQuery);
    if (!$imageUrl && GOOGLE_CSE_ID !== 'YOUR_CSE_ID_HERE') {
        $imageUrl = fetchGoogleImage($searchQuery);
    }

    if ($imageUrl) {
        saveImageCache($itemId, $imageUrl);
        json(['ok' => true, 'url' => $imageUrl, 'cached' => false]);
    } else {
        json(['ok' => false, 'url' => '']);
    }
}

function fetchEbayListingImage($query) {
    $url = 'https://www.ebay.co.uk/sch/i.html?' . http_build_query(['_nkw' => $query, '_ipg' => '10', '_sop' => '12']);
    $resp = curlGet($url, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-GB,en;q=0.9',
        'Cache-Control: no-cache',
    ]);
    if (!$resp['ok'] || $resp['code'] !== 200 || empty($resp['body'])) return null;
    preg_match_all('/https:\/\/i\.ebayimg\.com\/[^"\'\\s>]+/i', $resp['body'], $m);
    foreach ($m[0] ?? [] as $imgUrl) {
        if (strpos($imgUrl, 'spinner') !== false) continue;
        if (strlen($imgUrl) < 30) continue;
        $full = str_replace('/thumbs/images/g/', '/images/g/', $imgUrl);
        $full = preg_replace('/s-l\d+(\.\w+)$/', 's-l500$1', $full);
        return $full;
    }
    return null;
}

function fetchGoogleImage($query) {
    if (GOOGLE_CSE_ID === 'YOUR_CSE_ID_HERE') return null;
    $url = 'https://www.googleapis.com/customsearch/v1?' . http_build_query([
        'key' => GOOGLE_API_KEY, 'cx' => GOOGLE_CSE_ID, 'q' => $query,
        'searchType' => 'image', 'num' => 5, 'imgSize' => 'LARGE', 'safe' => 'active',
    ]);
    $resp = curlGet($url);
    if (!$resp['ok'] || $resp['code'] !== 200) return null;
    $data = json_decode($resp['body'], true);
    $best = null; $bestArea = 0;
    foreach ($data['items'] ?? [] as $img) {
        $a = intval($img['image']['width'] ?? 0) * intval($img['image']['height'] ?? 0);
        $l = $img['link'] ?? '';
        if ($a < 10000 || stripos($l, '.svg') !== false) continue;
        if ($a > $bestArea) { $bestArea = $a; $best = $l; }
    }
    return $best;
}

function getImageCache($itemId) {
    foreach (readCSV(IMAGES_FILE) as $row) {
        if ($row['item_id'] === $itemId && (time() - strtotime($row['fetched_at'])) < 604800) return $row['image_url'];
    }
    return null;
}

function saveImageCache($itemId, $url) {
    $rows = array_filter(readCSV(IMAGES_FILE), fn($r) => $r['item_id'] !== $itemId);
    $rows[] = ['item_id' => $itemId, 'image_url' => $url, 'fetched_at' => date('Y-m-d H:i:s')];
    writeCSV(IMAGES_FILE, array_values($rows), ['item_id','image_url','fetched_at']);
}

// ── EBAY PRICES ───────────────────────────────────────────────────────────────
function doGetPrices() {
    requireAuth();
    $userId = $_SESSION['user_id'];
    $result = [];
    foreach (readCSV(PRICES_FILE) as $row) {
        if ($row['user_id'] === $userId) $result[$row['item_id']] = $row;
    }
    json(['ok' => true, 'prices' => $result]);
}

function doRefreshPrices() {
    requireAuth();
    $userId = $_SESSION['user_id'];
    $itemId = $_POST['item_id'] ?? '';
    $query  = $_POST['query']   ?? '';
    if (!$itemId || !$query) json(['error' => 'Missing item_id or query'], 400);

    // Use pinned eBay query if one has been approved by the user
    $pinnedQuery = '';
    foreach (readCSV(PRICES_FILE) as $r) {
        if ($r['item_id'] === $itemId && $r['user_id'] === $userId) {
            $pinnedQuery = $r['ebay_query'] ?? '';
            break;
        }
    }
    $searchQuery = $pinnedQuery ?: $query;

    $priceData = fetchEbayPrice($searchQuery);

    // If eBay returned nothing and we weren't using a pinned query,
    // retry with first 3 words
    if (!$priceData && !$pinnedQuery) {
        $words = explode(' ', trim($query));
        if (count($words) > 3) {
            $priceData = fetchEbayPrice(implode(' ', array_slice($words, 0, 3)));
        }
    }

    // Still nothing — save a zero-count placeholder so the UI stops retrying
    // and shows the fallback search UI rather than "No data yet" forever
    if (!$priceData) {
        $priceData = ['avg_30' => 0, 'avg_10' => 0, 'min' => 0, 'max' => 0, 'count' => 0];
        savePriceData($userId, $itemId, $priceData);
        json(['ok' => true, 'price' => $priceData, 'no_data' => true]);
    }

    savePriceData($userId, $itemId, $priceData);
    json(['ok' => true, 'price' => $priceData]);
}

function fetchEbayPrice($query) {
    $url = 'https://www.ebay.co.uk/sch/i.html?' . http_build_query([
        '_nkw' => $query, 'LH_Sold' => '1', 'LH_Complete' => '1',
        '_sacat' => '0', '_sop' => '13', '_ipg' => '60',
    ]);
    $resp = curlGet($url, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-GB,en;q=0.9',
        'Cache-Control: no-cache',
        'Pragma: no-cache',
    ]);
    if (!$resp['ok'] || $resp['code'] !== 200) return null;
    // Match pound prices — handle both £ symbol and HTML entity &#163;
    preg_match_all('/(?:£|&#163;)\s*([\d,]+\.?\d{0,2})/', $resp['body'], $matches);
    $prices = [];
    foreach ($matches[1] as $p) {
        $v = floatval(str_replace(',', '', $p));
        if ($v >= 0.50 && $v <= 50000) $prices[] = $v;
    }
    if (empty($prices)) return null;
    // Use up to 30 prices; if fewer exist gracefully use all available
    $prices  = array_slice($prices, 0, 30);
    $count   = count($prices);
    $last10  = array_slice($prices, 0, min(10, $count));
    return [
        'avg_30' => round(array_sum($prices) / $count, 2),
        'avg_10' => round(array_sum($last10) / count($last10), 2),
        'min'    => round(min($prices), 2),
        'max'    => round(max($prices), 2),
        'count'  => $count,
    ];
}

function savePriceData($userId, $itemId, $data, $ebayQuery = '') {
    $existing = [];
    foreach (readCSV(PRICES_FILE) as $r) {
        if ($r['item_id'] === $itemId && $r['user_id'] === $userId) { $existing = $r; break; }
    }
    $rows    = array_values(array_filter(readCSV(PRICES_FILE), fn($r) => !($r['item_id'] === $itemId && $r['user_id'] === $userId)));
    $prevAvg = floatval($existing['avg_10'] ?? 0);
    // Preserve existing pinned query unless a new one is explicitly provided
    $pinnedQuery = $ebayQuery ?: ($existing['ebay_query'] ?? '');
    $change = $prevAvg > 0 ? round((($data['avg_10'] - $prevAvg) / $prevAvg) * 100, 1) : 0;
    $rows[] = [
        'item_id' => $itemId, 'user_id' => $userId,
        'avg_30' => $data['avg_30'], 'avg_10' => $data['avg_10'],
        'min' => $data['min'], 'max' => $data['max'], 'count' => $data['count'],
        'prev_avg' => $prevAvg, 'change_pct' => $change,
        'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat'),
        'updated_at' => date('Y-m-d H:i:s'),
        'ebay_query' => $pinnedQuery,
    ];
    writeCSV(PRICES_FILE, $rows, ['item_id','user_id','avg_30','avg_10','min','max','count','prev_avg','change_pct','direction','updated_at','ebay_query']);
}

// ── COLLECTION CRUD ───────────────────────────────────────────────────────────
function doSave() {
    requireAuth();
    $item = json_decode($_POST['item'] ?? '{}', true);
    if (!$item || empty($item['name'])) json(['error' => 'Invalid item'], 400);
    $item['id']       = uniqid('cv_', true);
    $item['user_id']  = $_SESSION['user_id'];
    $item['username'] = $_SESSION['user'];
    $item['saved_at'] = date('Y-m-d H:i:s');
    $item['thumbnail'] = '';
    appendCSV(COLLECTION_FILE, flattenItem($item), array_keys(csvHeaders()));
    json(['ok' => true, 'id' => $item['id']]);
}

function doCollection() {
    requireAuth();
    $category = $_GET['category'] ?? 'all';
    $userId   = $_SESSION['user_id'];
    $rows = readCSV(COLLECTION_FILE);
    $items = [];
    foreach ($rows as $row) {
        if ($row['user_id'] !== $userId) continue;
        if ($category !== 'all' && $row['category'] !== $category) continue;
        $items[] = $row;
    }
    usort($items, fn($a,$b) => strcmp($b['saved_at'], $a['saved_at']));
    json(['ok' => true, 'items' => $items, 'count' => count($items)]);
}

function doDelete() {
    requireAuth();
    $id = $_POST['id'] ?? '';
    $userId = $_SESSION['user_id'];
    if (!$id) json(['error' => 'Missing id'], 400);
    $rows = readCSV(COLLECTION_FILE);
    $updated = []; $deleted = false;
    foreach ($rows as $row) {
        if ($row['id'] === $id && $row['user_id'] === $userId) { $deleted = true; }
        else $updated[] = $row;
    }
    if (!$deleted) json(['error' => 'Not found'], 404);
    writeCSV(COLLECTION_FILE, $updated, array_keys(csvHeaders()));
    // Clean up caches
    foreach ([IMAGES_FILE => 'item_id', PRICES_FILE => 'item_id'] as $file => $key) {
        if (!file_exists($file)) continue;
        $rows = array_values(array_filter(readCSV($file), fn($r) => $r[$key] !== $id));
        $hdrs = $file === IMAGES_FILE ? ['item_id','image_url','fetched_at'] : ['item_id','user_id','avg_30','avg_10','min','max','count','prev_avg','change_pct','direction','updated_at'];
        writeCSV($file, $rows, $hdrs);
    }
    json(['ok' => true]);
}

function doStats() {
    requireAuth();
    $userId = $_SESSION['user_id'];
    $stats  = ['total' => 0, 'invested' => 0, 'value' => 0, 'by_cat' => []];
    foreach (readCSV(COLLECTION_FILE) as $row) {
        if ($row['user_id'] !== $userId) continue;
        $stats['total']++;
        $stats['invested'] += floatval($row['bought'] ?? 0);
        $stats['value']    += floatval($row['value']  ?? 0);
        $cat = $row['category'] ?? 'other';
        $stats['by_cat'][$cat] = ($stats['by_cat'][$cat] ?? 0) + 1;
    }
    json(['ok' => true, 'stats' => $stats]);
}


// ── EBAY FALLBACK SEARCH ──────────────────────────────────────────────────────

/**
 * Search eBay for a simplified query and return a preview of the top listing:
 * title, image, price estimate. Used when the full query returns no sold data.
 */
function doSearchEbay() {
    requireAuth();
    $query = trim($_POST['query'] ?? '');
    if (!$query) json(['error' => 'Missing query'], 400);

    // Fetch active listings (not sold) for a richer preview
    $url = 'https://www.ebay.co.uk/sch/i.html?' . http_build_query([
        '_nkw' => $query, '_ipg' => '5', '_sop' => '12',
    ]);
    $resp = curlGet($url, [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-GB,en;q=0.9',
        'Cache-Control: no-cache',
    ]);
    if (!$resp['ok'] || $resp['code'] !== 200) json(['ok' => false, 'error' => 'eBay unavailable'], 502);

    $body = $resp['body'];

    // Extract first listing title
    $title = '';
    if (preg_match('/<h3[^>]*class="[^"]*s-item__title[^"]*"[^>]*>\s*(?:<span[^>]*>[^<]*<\/span>)?\s*(.*?)\s*<\/h3>/si', $body, $tm)) {
        $title = html_entity_decode(strip_tags($tm[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = preg_replace('/\s+/', ' ', trim($title));
    }

    // Extract first listing image
    $image = '';
    preg_match_all('#https://i\.ebayimg\.com/[^"\s>]+#i', $body, $im);
    foreach ($im[0] ?? [] as $imgUrl) {
        if (strpos($imgUrl, 'spinner') !== false) continue;
        if (strlen($imgUrl) < 30) continue;
        $image = str_replace('/thumbs/images/g/', '/images/g/', $imgUrl);
        $image = preg_replace('/s-l\d+(\.\w+)$/', 's-l500$1', $image);
        break;
    }

    // Get price estimate from completed/sold listings for the same query
    $priceData = fetchEbayPrice($query);

    if (!$title && !$image) {
        json(['ok' => false, 'no_results' => true]);
    }

    json([
        'ok'         => true,
        'title'      => $title,
        'image'      => $image,
        'price'      => $priceData,
        'query_used' => $query,
    ]);
}

/**
 * Save a user-approved eBay search query as the pinned query for an item.
 * Future price refreshes will use this query instead of the auto-generated one.
 */
function doLinkEbayQuery() {
    requireAuth();
    $userId     = $_SESSION['user_id'];
    $itemId     = $_POST['item_id']    ?? '';
    $ebayQuery  = trim($_POST['ebay_query'] ?? '');
    if (!$itemId || !$ebayQuery) json(['error' => 'Missing item_id or ebay_query'], 400);

    // Fetch fresh price data with the approved query
    $priceData = fetchEbayPrice($ebayQuery);
    if (!$priceData) {
        $priceData = ['avg_30' => 0, 'avg_10' => 0, 'min' => 0, 'max' => 0, 'count' => 0];
    }

    savePriceData($userId, $itemId, $priceData, $ebayQuery);
    json(['ok' => true, 'price' => $priceData, 'ebay_query' => $ebayQuery]);
}

// ── CSV HELPERS ───────────────────────────────────────────────────────────────
function csvHeaders() {
    return ['id'=>'','user_id'=>'','username'=>'','category'=>'','name'=>'','subtitle'=>'',
            'series'=>'','item_type'=>'','year'=>'','condition'=>'','bought'=>'','value'=>'',
            'extra1'=>'','extra2'=>'','extra3'=>'','extra4'=>'','notes'=>'','thumbnail'=>'','saved_at'=>''];
}
function flattenItem($item) {
    $flat = [];
    foreach (array_keys(csvHeaders()) as $k) {
        $flat[$k] = isset($item[$k]) ? str_replace(["\n","\r"], ' ', $item[$k]) : '';
    }
    return $flat;
}
function readCSV($file) {
    if (!file_exists($file)) return [];
    $rows = []; $hdrs = null;
    $h = fopen($file, 'r');
    while (($line = fgetcsv($h)) !== false) {
        if (!$hdrs) { $hdrs = $line; continue; }
        if (count($line) !== count($hdrs)) continue;
        $rows[] = array_combine($hdrs, $line);
    }
    fclose($h);
    return $rows;
}
function appendCSV($file, $row, $headers) {
    $new = !file_exists($file) || filesize($file) === 0;
    $h   = fopen($file, 'a');
    if ($new) fputcsv($h, $headers);
    fputcsv($h, array_values($row));
    fclose($h);
}
function writeCSV($file, $rows, $headers) {
    $h = fopen($file, 'w');
    fputcsv($h, $headers);
    foreach ($rows as $row) fputcsv($h, array_values($row));
    fclose($h);
}
function curlGet($url, $headers = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_ENCODING=>'',CURLOPT_HTTPHEADER=>$headers]);
    $body = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    return ['ok' => !$err, 'body' => $body, 'code' => $code, 'error' => $err];
}
function curlPost($url, $payload, $headers = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,CURLOPT_HTTPHEADER=>$headers,CURLOPT_TIMEOUT=>30,CURLOPT_SSL_VERIFYPEER=>true]);
    $body = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    return ['ok' => !$err, 'body' => $body, 'code' => $code, 'error' => $err];
}
function json($data, $code = 200) { http_response_code($code); echo json_encode($data); exit; }
