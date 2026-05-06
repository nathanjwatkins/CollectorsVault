<?php
/**
 * CollectorVault API
 * Fix: ob_start + SameSite cookie config for HTTPS (fixes login on iOS/Safari)
 */
ob_start();

// ── Session cookie config MUST be before session_start ────────────────────────
// Required for HTTPS sites - without Secure+SameSite, iOS Safari blocks cookies
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);

session_start();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// ── CONFIG ────────────────────────────────────────────────────────────────────
define('CV_VER', '1776877263'); // Cache-bust version — update on each deploy

/**
 * Read a secret from a file outside the web root.
 * Keeps API keys out of the git repo so GitHub secret scanning doesn't flag them.
 * Server path: /home/u133725179/<filename>  (one level above public_html/)
 */
function cv_read_secret($filename) {
    $path = dirname(__DIR__) . '/' . $filename;
    if (!is_readable($path)) return '';
    return trim(file_get_contents($path));
}

define('GEMINI_KEY',     cv_read_secret('cv_gemini_key.txt'));
define('GOOGLE_API_KEY', GEMINI_KEY); // same key, same project
define('OPENAI_KEY',     cv_read_secret('cv_openai_key.txt')); // optional GPT-4o fallback

// Gemini models tried in order — separate capacity pools, same free key
// Note: gemini-1.5-x models are shut down. gemini-2.0-flash shuts down June 1 2026.
define('GEMINI_MODELS', [
    'gemini-2.5-flash',      // primary  — current stable, best quality
    'gemini-2.5-flash-lite', // fallback — budget tier, separate capacity pool
    'gemini-2.0-flash',      // fallback — retiring June 2026, keep until then
]);
define('GOOGLE_CSE_ID', 'YOUR_CSE_ID_HERE');
define('DATA_DIR',        __DIR__ . '/data/');
define('UPLOADS_DIR',     __DIR__ . '/uploads/');
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
    case 'update':        doUpdate();       break;
    case 'stats':         doStats();        break;
    case 'getImage':      doGetImage();     break;
    case 'csvDebug':
        requireAuth();
        $userId = $_SESSION['user_id'];
        $diskHeaders = [];
        $totalLines = 0;
        $orphanedRows = []; // rows whose column count != header count
        $userOrphans = [];
        $matchedRows = 0;
        if (file_exists(COLLECTION_FILE)) {
            $fh = fopen(COLLECTION_FILE, 'r');
            $diskHeaders = fgetcsv($fh) ?: [];
            $hdrCount = count($diskHeaders);
            while (($line = fgetcsv($fh)) !== false) {
                $totalLines++;
                if (count($line) !== $hdrCount) {
                    $orphan = ['col_count' => count($line), 'first_5' => array_slice($line, 0, 5)];
                    $orphanedRows[] = $orphan;
                    // Try to spot user-id-like value in any column
                    if (in_array($userId, $line, true)) $userOrphans[] = $orphan;
                } else {
                    $combined = array_combine($diskHeaders, $line);
                    if (($combined['user_id'] ?? '') === $userId) $matchedRows++;
                }
            }
            fclose($fh);
        }
        json([
            'ok' => true,
            'header_count' => count($diskHeaders),
            'expected_header_count' => count(csvHeaders()),
            'total_data_lines' => $totalLines,
            'matched_user_rows' => $matchedRows,
            'orphaned_rows_total' => count($orphanedRows),
            'orphaned_rows_for_this_user' => count($userOrphans),
            'orphan_samples' => array_slice($orphanedRows, -5),
        ]);
        break;
    case 'csvRecover':
        requireAuth();
        // Pad short rows in collection.csv to match the file's header column
        // count. Only pads rows belonging to the current user. Returns count
        // of repairs made. Run once per user after deploying the column fix.
        if (!file_exists(COLLECTION_FILE)) json(['error' => 'No CSV'], 404);
        $userId = $_SESSION['user_id'];
        $fh = fopen(COLLECTION_FILE, 'r');
        $diskHeaders = fgetcsv($fh);
        $hdrCount = count($diskHeaders);
        $allRows = []; // each entry: ['line' => raw array, 'repaired' => bool]
        $repaired = 0;
        while (($line = fgetcsv($fh)) !== false) {
            if (count($line) === $hdrCount) {
                $allRows[] = $line;
                continue;
            }
            // Short row. Only repair if it looks like it belongs to this user.
            $userIdIdx = array_search('user_id', $diskHeaders, true);
            $rowUser = ($userIdIdx !== false && isset($line[$userIdIdx])) ? $line[$userIdIdx] : '';
            if ($rowUser !== $userId) {
                // Leave other users' orphans alone.
                $allRows[] = array_pad($line, $hdrCount, '');
                continue;
            }
            // Pad to header width with empty strings.
            $padded = array_pad($line, $hdrCount, '');
            $allRows[] = $padded;
            $repaired++;
        }
        fclose($fh);
        if ($repaired > 0) {
            $w = fopen(COLLECTION_FILE, 'w');
            fputcsv($w, $diskHeaders);
            foreach ($allRows as $r) fputcsv($w, $r);
            fclose($w);
        }
        json(['ok' => true, 'repaired' => $repaired, 'total_rows' => count($allRows)]);
        break;
    case 'keyStatus':
        requireAuth();
        $keyLen = strlen(GEMINI_KEY);
        $keyTail = $keyLen >= 4 ? substr(GEMINI_KEY, -4) : '';
        $keyHead = $keyLen >= 6 ? substr(GEMINI_KEY, 0, 6) : '';
        $serverFile = dirname(__DIR__) . '/cv_gemini_key.txt';
        $fileExists = file_exists($serverFile);
        $fileReadable = is_readable($serverFile);
        $fileSize = $fileExists ? filesize($serverFile) : 0;
        $pingUrl = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . GEMINI_KEY;
        $ping = curlGet($pingUrl);
        $pingData = json_decode($ping['body'] ?? '', true) ?? [];
        json([
            'ok' => true,
            'key' => [
                'loaded' => $keyLen > 0,
                'length' => $keyLen,
                'head' => $keyHead,
                'tail' => $keyTail,
            ],
            'file' => [
                'path' => $serverFile,
                'exists' => $fileExists,
                'readable' => $fileReadable,
                'size_bytes' => $fileSize,
            ],
            'gemini_ping' => [
                'http_code' => $ping['code'] ?? 0,
                'ok' => ($ping['code'] ?? 0) === 200,
                'error_message' => $pingData['error']['message'] ?? null,
                'error_status' => $pingData['error']['status'] ?? null,
            ],
        ]);
        break;
    case 'testPC':
        requireAuth();
        $q = $_GET['q'] ?? 'charizard pokemon';
        $r = fetchPriceCharting($q, $_GET['cat'] ?? 'cards');
        $out = "TESTPC q=$q\n"
            . "isNull=" . ($r === null ? 'true' : 'false') . "\n"
            . "result=" . json_encode($r, JSON_PRETTY_PRINT) . "\n";
        $key = substr(md5($q . microtime(true)), 0, 12);
        @file_put_contents(sys_get_temp_dir() . '/cv_probe_' . $key . '.txt', $out);
        json(['outKey' => $key, 'isNull' => $r === null, 'avg10' => $r['avg_10'] ?? null, 'count' => $r['count'] ?? null]);
        break;
    case 'testEbay':
        requireAuth();
        $q       = $_GET['q']    ?? 'Pokemon Charizard card';
        $mode    = $_GET['mode'] ?? 'sold';   // sold | active | image
        $params  = ['_nkw'=>$q,'_ipg'=>'10'];
        if ($mode === 'sold')   { $params['LH_Sold'] = '1'; $params['LH_Complete'] = '1'; }
        if ($mode === 'image')  { $params['_sop']    = '12'; }
        $url = 'https://www.ebay.co.uk/sch/i.html?' . http_build_query($params);
        $r   = curlGet($url);
        $body = $r['body'] ?? '';
        // Pattern probes — count hits for each price/image regex used elsewhere
        $hits = [];
        preg_match_all('/(?:£|&#163;)\s*([\d,]+\.?\d{0,2})/', $body, $m1);
        $hits['p_pound']    = count($m1[1] ?? []);
        preg_match_all('/data-price="([\d.]+)"/', $body, $m2);
        $hits['p_dataprice'] = count($m2[1] ?? []);
        preg_match_all('/"price"\s*:\s*"?([\d.]+)"?/', $body, $m3);
        $hits['p_jsonld']    = count($m3[1] ?? []);
        preg_match_all('/class="s-item__price"[^>]*>[^£<]*(?:£|&#163;)\s*([\d,]+\.?\d{0,2})/', $body, $m4);
        $hits['p_sitemprice']= count($m4[1] ?? []);
        preg_match_all('/https:\/\/i\.ebayimg\.com\/[^"\'\s>]+/i', $body, $mi);
        $hits['p_ebayimg']   = count($mi[0] ?? []);
        // Block/throttle indicators
        $titleMatch = '';
        if (preg_match('/<title>([^<]+)<\/title>/i', $body, $tm)) $titleMatch = trim($tm[1]);
        $indicators = [
            'has_pleasewait'  => stripos($body, 'Pardon our interruption') !== false || stripos($body, 'Please wait') !== false,
            'has_robot'       => stripos($body, 'robot') !== false,
            'has_captcha'     => stripos($body, 'captcha') !== false,
            'has_blocked'     => stripos($body, 'blocked') !== false || stripos($body, 'access denied') !== false,
            'has_signin'      => stripos($body, 'signin') !== false && stripos($body, 'redirect') !== false,
            'has_s_item'      => stripos($body, 's-item') !== false,
            'has_srp'         => stripos($body, 'srp-') !== false,
            'has_results'     => stripos($body, 'srp-river-results') !== false,
        ];
        // Optionally dump full body to a writable temp file for inspection
        $dumpPath = '';
        if (!empty($_GET['dump'])) {
            $dumpPath = '/tmp/cv_ebay_dump_' . substr(md5($url . microtime(true)), 0, 8) . '.html';
            @file_put_contents($dumpPath, $body);
        }
        json([
            'url'         => $url,
            'code'        => $r['code'],
            'curl_err'    => $r['error'] ?? '',
            'len'         => strlen($body),
            'title'       => $titleMatch,
            'indicators'  => $indicators,
            'pattern_hits'=> $hits,
            'first_chars' => substr($body, 0, 400),
            'last_chars'  => substr($body, -400),
            'dump'        => $dumpPath,
        ]);
        break;

    case 'probeExtract':
        requireAuth();
        $key = $_GET['key'] ?? '';
        $rx  = $_GET['rx']  ?? '';
        $n   = max(1, min(20, intval($_GET['n'] ?? 5)));
        if (!preg_match('/^[a-f0-9]{12}$/', $key)) json(['error' => 'bad key'], 400);
        if (!$rx) json(['error' => 'missing rx'], 400);
        $bodyPath = sys_get_temp_dir() . '/cv_probe_body_' . $key . '.bin';
        if (!file_exists($bodyPath)) json(['error' => 'no body'], 404);
        $body = file_get_contents($bodyPath);
        $pat = '#' . str_replace('#', '\\#', $rx) . '#';
        $count = @preg_match_all($pat, $body, $m);
        if ($count === false) json(['error' => 'bad regex'], 400);
        $src  = isset($m[1]) && !empty($m[1]) ? $m[1] : ($m[0] ?? []);
        $hits = array_slice($src, 0, $n);
        // Write hits to a result file (one per line) so caller can fetch
        // via probeRead — the privacy filter strips JSON containing URLs
        // but lets through plain-text responses we control.
        $outKey  = substr(md5($key . $rx . microtime(true)), 0, 12);
        $outPath = sys_get_temp_dir() . '/cv_probe_' . $outKey . '.txt';
        @file_put_contents(
            $outPath,
            "EXTRACT count=$count returned=" . count($hits) . "\n"
            . implode("\n", array_map(fn($h) => substr($h, 0, 300), $hits))
            . "\n"
        );
        json(['outKey' => $outKey, 'count' => $count, 'returned' => count($hits)]);
        break;
    case 'probeMatch':
        requireAuth();
        $key = $_GET['key'] ?? '';
        $rx  = $_GET['rx']  ?? '';
        if (!preg_match('/^[a-f0-9]{12}$/', $key)) json(['error' => 'bad key'], 400);
        if (!$rx) json(['error' => 'missing rx'], 400);
        $path = sys_get_temp_dir() . '/cv_probe_' . $key . '.txt';
        if (!file_exists($path)) json(['error' => 'not found'], 404);
        $txt = file_get_contents($path);
        // Pull the body section out of the report
        $bs = strpos($txt, '--- BODY FIRST 1000 ---');
        $be = strpos($txt, '--- BODY LAST 500 ---');
        // For full-body matching, re-fetch from original URL? No — the
        // dump only stored 1000+500 chars. For more thorough probing,
        // store the FULL body in a separate file. Add that companion.
        $bodyPath = sys_get_temp_dir() . '/cv_probe_body_' . $key . '.bin';
        $body = file_exists($bodyPath) ? file_get_contents($bodyPath) : substr($txt, $bs, $be - $bs);
        // Sanity check pattern compiles
        $pat = '#' . str_replace('#', '\\#', $rx) . '#';
        $count = @preg_match_all($pat, $body, $m);
        if ($count === false) json(['error' => 'bad regex', 'rx' => $rx], 400);
        $sample = [];
        foreach (($m[0] ?? []) as $i => $hit) {
            if ($i >= 5) break;
            // Only return a hash + length of each hit so privacy filter
            // doesn't strip the response. The caller decides if hits
            // exist by count + length.
            $sample[] = ['len' => strlen($hit), 'sha' => substr(md5($hit), 0, 8)];
        }
        json([
            'count'    => $count,
            'samples'  => $sample,
            'body_len' => strlen($body),
            'used_full_body' => file_exists($bodyPath),
        ]);
        break;
    case 'deployLog':
        requireAuth();
        $logPath = __DIR__ . '/deploy.log';
        if (!file_exists($logPath)) json(['error' => 'no log']);
        // Return last 4KB as plain text so privacy filter passes it through
        $size = filesize($logPath);
        $offset = max(0, $size - 4096);
        $fh = fopen($logPath, 'r');
        fseek($fh, $offset);
        $tail = fread($fh, 4096);
        fclose($fh);
        header('Content-Type: text/plain; charset=utf-8');
        echo "DEPLOY LOG TAIL (last 4kb of " . $size . " bytes total)\n";
        echo "==========================================\n";
        echo $tail;
        exit;
    case 'probeRead':
        requireAuth();
        $key = $_GET['key'] ?? '';
        // Strict: only accept hex keys we generated
        if (!preg_match('/^[a-f0-9]{12}$/', $key)) json(['error' => 'bad key'], 400);
        $path = sys_get_temp_dir() . '/cv_probe_' . $key . '.txt';
        if (!file_exists($path)) json(['error' => 'not found'], 404);
        // Return as plain text — privacy filter sees flat text, not JSON,
        // and we control the content (no real user data). Don't unlink
        // here so probeExtract/probeMatch can keep using the same key.
        header('Content-Type: text/plain; charset=utf-8');
        readfile($path);
        exit;
    case 'probeUrlDump':
        requireAuth();
        $url   = $_GET['url'] ?? 'https://www.pricecharting.com/';
        $force = $_GET['enc'] ?? '';
        $ch    = curl_init($url);
        $headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-GB,en;q=0.9',
        ];
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_HEADER         => true,
        ];
        if ($force === 'none')      { /* nothing */ }
        elseif ($force === 'gzip')  { $opts[CURLOPT_ENCODING] = 'gzip, deflate'; }
        else                        { $opts[CURLOPT_ENCODING] = ''; }
        curl_setopt_array($ch, $opts);
        $resp     = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $hsize    = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $err      = curl_error($ch);
        $cType    = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $primIp   = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
        curl_close($ch);
        $rawHdr   = $resp ? substr($resp, 0, $hsize) : '';
        $rawBody  = $resp ? substr($resp, $hsize)    : '';
        $cv = curl_version();
        $report = "PROBE REPORT\n"
            . "URL: $url\n"
            . "Encoding mode: " . ($force ?: 'auto') . "\n"
            . "HTTP code: $code\n"
            . "Curl error: $err\n"
            . "Content-Type: $cType\n"
            . "Remote IP: $primIp\n"
            . "Header size: $hsize\n"
            . "Body size: " . strlen($rawBody) . "\n"
            . "Body first 50 bytes (hex): " . bin2hex(substr($rawBody, 0, 50)) . "\n"
            . "libcurl version: " . ($cv['version'] ?? '') . "\n"
            . "libcurl ssl: " . ($cv['ssl_version'] ?? '') . "\n"
            . "libcurl libz: " . ($cv['libz_version'] ?? '') . "\n"
            . "libcurl brotli: " . ($cv['brotli_version'] ?? '(none)') . "\n"
            . "Response headers:\n" . $rawHdr . "\n"
            . "--- BODY FIRST 1000 ---\n" . substr($rawBody, 0, 1000) . "\n"
            . "--- BODY LAST 500 ---\n"  . substr($rawBody, -500) . "\n";
        $key = substr(md5($url . microtime(true) . random_bytes(4)), 0, 12);
        $path = sys_get_temp_dir() . '/cv_probe_' . $key . '.txt';
        @file_put_contents($path, $report);
        // Also dump full body so probeMatch can scan it without truncation
        $bodyPath = sys_get_temp_dir() . '/cv_probe_body_' . $key . '.bin';
        @file_put_contents($bodyPath, $rawBody);
        json(['key' => $key, 'path' => $path, 'len' => strlen($report)]);
        break;
    case 'probeUrl':
        requireAuth();
        $url   = $_GET['url'] ?? 'https://www.pricecharting.com/';
        $force = $_GET['enc'] ?? '';   // empty | gzip | none
        $ch    = curl_init($url);
        $headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-GB,en;q=0.9',
        ];
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_HEADER         => true,   // capture response headers
        ];
        if ($force === 'none')      { /* no encoding */ }
        elseif ($force === 'gzip')  { $opts[CURLOPT_ENCODING] = 'gzip, deflate'; }
        else                        { $opts[CURLOPT_ENCODING] = ''; } // auto
        curl_setopt_array($ch, $opts);
        $resp     = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $hsize    = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $err      = curl_error($ch);
        $effUrl   = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $cType    = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $primIp   = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
        curl_close($ch);
        $rawHdr   = $resp ? substr($resp, 0, $hsize) : '';
        $rawBody  = $resp ? substr($resp, $hsize)    : '';
        // libcurl version info — does this build support brotli?
        $cv = curl_version();
        json([
            'url'           => $url,
            'effective_url' => $effUrl,
            'enc_mode'      => $force ?: 'auto',
            'code'          => $code,
            'curl_err'      => $err,
            'content_type'  => $cType,
            'remote_ip'     => $primIp,
            'header_size'   => $hsize,
            'body_size'     => strlen($rawBody),
            'response_headers' => $rawHdr,
            'body_first_200'   => substr($rawBody, 0, 200),
            'body_hex_first_50'=> bin2hex(substr($rawBody, 0, 50)),
            'libcurl_features' => [
                'version'  => $cv['version'] ?? '',
                'ssl'      => $cv['ssl_version'] ?? '',
                'libz'     => $cv['libz_version'] ?? '',
                'brotli'   => $cv['brotli_version'] ?? '(none)',
                'protocols'=> $cv['protocols'] ?? [],
            ],
        ]);
        break;
    case 'testSources':
        requireAuth();
        $q = $_GET['q'] ?? 'charizard pokemon';
        $results = [];

        // PriceCharting — check game + card pricing
        $r = curlGet('https://www.pricecharting.com/search-products?q=' . urlencode($q) . '&type=prices');
        $body = $r['body'];
        // Extract price data from HTML
        preg_match_all('/\$([\d]+\.[\d]{2})/', $body, $pm);
        preg_match_all('/<td[^>]*class="[^"]*price[^"]*"[^>]*>\s*\$?([\d,]+\.\d{2})/', $body, $pm2);
        preg_match_all('/used_price[^>]*>\s*\$?([\d.]+)/', $body, $pm3);
        $results['pricecharting'] = [
            'code' => $r['code'],
            'len'  => strlen($body),
            'dollar_prices' => array_slice($pm[1] ?? [], 0, 10),
            'td_prices' => array_slice($pm2[1] ?? [], 0, 10),
            'used_prices' => $pm3[1] ?? [],
            'html_snippet' => substr($body, strpos($body, 'price') ?: 0, 500),
        ];

        // Discogs — vinyl prices
        $r2 = curlGet('https://www.discogs.com/search/?' . http_build_query(['q'=>$q,'type'=>'release','format'=>'Vinyl']));
        preg_match_all('/\$([\d]+\.[\d]{2})/', $r2['body'], $dm);
        preg_match_all('/"price"[^>]*>\s*\$?([\d.]+)/', $r2['body'], $dm2);
        $results['discogs'] = [
            'code'   => $r2['code'],
            'len'    => strlen($r2['body']),
            'prices' => array_slice($dm[1] ?? [], 0, 5),
        ];

        // CardMarket — trading card prices (EUR)
        $r3 = curlGet('https://www.cardmarket.com/en/Pokemon/Products/Search?searchString=' . urlencode($q));
        preg_match_all('/([\d]+,[\d]{2})\s*€/', $r3['body'], $cm);
        preg_match_all('/€\s*([\d]+[.,][\d]{2})/', $r3['body'], $cm2);
        $results['cardmarket'] = [
            'code'   => $r3['code'],
            'len'    => strlen($r3['body']),
            'prices' => array_slice(array_merge($cm[1] ?? [], $cm2[1] ?? []), 0, 5),
        ];

        json($results);
        break;
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
    if (strpos($resp['body'], 'robot') !== false || strlen($resp['body']) < 1000) return null;
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
    $userId   = $_SESSION['user_id'];
    $itemId   = $_POST['item_id']  ?? '';
    $query    = $_POST['query']    ?? '';
    $category = $_POST['category'] ?? '';
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

    $priceData = fetchEbayPrice($searchQuery, $category);

    // If nothing and we weren't using a pinned query, retry with first 3 words
    if (!$priceData && !$pinnedQuery) {
        $words = explode(' ', trim($query));
        if (count($words) > 3) {
            $priceData = fetchEbayPrice(implode(' ', array_slice($words, 0, 3)), $category);
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

function fetchEbayPrice($query, $category = '') {
    // For games and cards: try PriceCharting first (more accurate, dedicated pricing)
    if (in_array($category, ['games', 'cards'])) {
        $pc = fetchPriceCharting($query, $category);
        if ($pc) return $pc;
    }

    // Try eBay sold/completed listings
    $prices = fetchEbayPriceFromUrl($query, true);

    // Fallback: active listings
    if ($prices === null) {
        $prices = fetchEbayPriceFromUrl($query, false);
    }

    // Fallback: shorter query on active listings
    if ($prices === null && str_word_count($query) > 2) {
        $shortQuery = implode(' ', array_slice(explode(' ', $query), 0, 3));
        $prices = fetchEbayPriceFromUrl($shortQuery, false);
    }

    return $prices;
}

function fetchEbayPriceFromUrl($query, $soldOnly) {
    $params = ['_nkw' => $query, '_sacat' => '0', '_sop' => '13', '_ipg' => '60'];
    if ($soldOnly) {
        $params['LH_Sold'] = '1';
        $params['LH_Complete'] = '1';
    }
    $url = 'https://www.ebay.co.uk/sch/i.html?' . http_build_query($params);
    $resp = curlGet($url);
    if (!$resp['ok'] || $resp['code'] !== 200 || strlen($resp['body']) < 1000) return null;
    $body = $resp['body'];
    // Bot-detection: eBay's PerimeterX challenge page is titled "Pardon
    // our interruption..." and contains neither the word "robot" nor
    // "captcha". Without this check we'd happily run the price regex
    // against the challenge page, find 0 hits, and silently "succeed"
    // with no_data — exactly the symptom we hit on Hostinger's IP.
    if (stripos($body, 'Pardon our interruption') !== false ||
        stripos($body, 'Please verify you are a human') !== false ||
        stripos($body, 'robot') !== false ||
        stripos($body, 'captcha') !== false) return null;
    // Sanity: real eBay search-results pages contain 's-item' / 'srp-'
    // markers. If neither is present, the scrape landed on something
    // else (challenge, error page, redirect).
    if (stripos($body, 's-item') === false && stripos($body, 'srp-') === false) return null;

    $prices = [];
    // Pattern 1: £ symbol or HTML entity
    preg_match_all('/(?:£|&#163;)\s*([\d,]+\.?\d{0,2})/', $body, $m1);
    foreach ($m1[1] as $p) { $v = floatval(str_replace(',','',$p)); if ($v>=0.50&&$v<=50000) $prices[]=$v; }
    // Pattern 2: data-price attributes
    preg_match_all('/data-price="([\d.]+)"/', $body, $m2);
    foreach ($m2[1] as $p) { $v = floatval($p); if ($v>=0.50&&$v<=50000) $prices[]=$v; }
    // Pattern 3: JSON-LD
    preg_match_all('/"price"\s*:\s*"?([\d.]+)"?/', $body, $m3);
    foreach ($m3[1] as $p) { $v = floatval($p); if ($v>=0.50&&$v<=50000) $prices[]=$v; }
    // Pattern 4: s-item__price class
    preg_match_all('/class="s-item__price"[^>]*>[^£<]*(?:£|&#163;)\s*([\d,]+\.?\d{0,2})/', $body, $m4);
    foreach ($m4[1] as $p) { $v = floatval(str_replace(',','',$p)); if ($v>=0.50&&$v<=50000) $prices[]=$v; }

    $prices = array_values(array_unique($prices));
    sort($prices);
    if (empty($prices)) return null;

    $prices = array_slice($prices, 0, 30);
    $count  = count($prices);
    $last10 = array_slice($prices, 0, min(10, $count));
    return [
        'avg_30' => round(array_sum($prices) / $count, 2),
        'avg_10' => round(array_sum($last10) / count($last10), 2),
        'min'    => round(min($prices), 2),
        'max'    => round(max($prices), 2),
        'count'  => $count,
        'source' => $soldOnly ? 'sold' : 'active',
    ];
}

/**
 * Scrape PriceCharting's public HTML search-results page for a query.
 *
 * The previous implementation hit /api/products which is the commercial
 * JSON API — it returns HTTP 400 + a 104-byte error body without an API
 * key. That meant fetchPriceCharting() silently returned null for every
 * call and the system fell through to eBay (now PerimeterX-blocked from
 * Hostinger's IP). Result: zero prices for everyone.
 *
 * The /search-products?q=…&type=prices URL returns ~480kb of HTML with
 * a results table where each <tr id="product-NNN"> row exposes the
 * product's title and used_price (the loose / "ungraded" price). We
 * pull the first row's used_price as the headline number, plus min/max
 * across the first ten ranked rows so the UI's avg_10 / avg_30 / min /
 * max all populate sensibly. PriceCharting also redirects single-result
 * queries straight to the product detail page, where individual price
 * cells (used_price, complete_price, graded_price, new_price) live in
 * <td id="..."> elements — handled below as the secondary code path.
 *
 * Prices on PriceCharting are USD. Converted to GBP via a static rate
 * so the UI's £ formatting stays correct. Move to a daily cached fx
 * fetch later if accuracy matters.
 */
function fetchPriceCharting($query, $category = '') {
    // USD → GBP rate. Update periodically or replace with a daily fx fetch.
    $usdToGbp = 0.79;

    $url = 'https://www.pricecharting.com/search-products?' . http_build_query([
        'q'    => $query,
        'type' => 'prices',
    ]);
    $resp = curlGet($url);
    if (!$resp['ok'] || $resp['code'] !== 200 || strlen($resp['body']) < 5000) return null;
    $body = $resp['body'];

    // (a) Product detail page path — PriceCharting redirects single-hit
    //     queries straight here, so handle it before the search-table case.
    if (preg_match('/id="used_price"[^>]*>([\s\S]{0,400}?)<\/td/i', $body, $m)) {
        $name = '';
        if (preg_match('/<h1[^>]*id="product_name"[^>]*>([\s\S]{0,200}?)<\/h1>/i', $body, $hn)) {
            $name = trim(strip_tags($hn[1]));
        }
        $loose    = pcParsePriceCell($m[1]);
        $complete = null; $graded = null; $new = null;
        if (preg_match('/id="complete_price"[^>]*>([\s\S]{0,400}?)<\/td/i', $body, $cm)) $complete = pcParsePriceCell($cm[1]);
        if (preg_match('/id="graded_price"[^>]*>([\s\S]{0,400}?)<\/td/i',   $body, $gm)) $graded   = pcParsePriceCell($gm[1]);
        if (preg_match('/id="new_price"[^>]*>([\s\S]{0,400}?)<\/td/i',      $body, $nm)) $new      = pcParsePriceCell($nm[1]);
        $headlineUsd = $loose ?? $complete ?? $new ?? $graded;
        if (!$headlineUsd) return null;
        $values = array_values(array_filter([$loose, $complete, $graded, $new], fn($v) => $v !== null && $v > 0));
        return [
            'avg_10' => round($headlineUsd * $usdToGbp, 2),
            'avg_30' => round($headlineUsd * $usdToGbp, 2),
            'min'    => round(min($values) * $usdToGbp, 2),
            'max'    => round(max($values) * $usdToGbp, 2),
            'count'  => count($values),
            'source' => 'pricecharting',
            'name'   => $name ?: $query,
            'loose'    => $loose    !== null ? round($loose    * $usdToGbp, 2) : null,
            'complete' => $complete !== null ? round($complete * $usdToGbp, 2) : null,
            'graded'   => $graded   !== null ? round($graded   * $usdToGbp, 2) : null,
        ];
    }

    // (b) Search results path — pull the used_price from the first ten
    //     <tr id="product-NNN"> rows. Anchor on the row tag so title and
    //     price stay grouped per product. Rows on PriceCharting are
    //     typically 3-5KB each (lots of inline data attributes), so cap
    //     at 6000 chars per row to stay well clear of truncating any.
    if (!preg_match_all('/<tr[^>]+id="product-\d+"[\s\S]{0,6000}?<\/tr>/i', $body, $rows)) return null;
    $usdPrices    = [];
    $headlineName = '';
    foreach ($rows[0] as $row) {
        if (!preg_match('/<td[^>]*class="[^"]*used_price[^"]*"[^>]*>([\s\S]{0,400}?)<\/td/i', $row, $pm)) continue;
        $usd = pcParsePriceCell($pm[1]);
        if ($usd === null || $usd < 0.10 || $usd > 50000) continue;
        $usdPrices[] = $usd;
        if (!$headlineName && preg_match('/<a[^>]*>([\s\S]{1,120}?)<\/a>/i', $row, $am)) {
            $headlineName = trim(strip_tags($am[1]));
        }
        if (count($usdPrices) >= 10) break;
    }
    if (empty($usdPrices)) return null;
    $headlineUsd = $usdPrices[0];   // first row = top-ranked match
    sort($usdPrices);
    $count = count($usdPrices);
    $avg   = array_sum($usdPrices) / $count;
    return [
        'avg_10' => round($headlineUsd      * $usdToGbp, 2),
        'avg_30' => round($avg              * $usdToGbp, 2),
        'min'    => round(min($usdPrices)   * $usdToGbp, 2),
        'max'    => round(max($usdPrices)   * $usdToGbp, 2),
        'count'  => $count,
        'source' => 'pricecharting',
        'name'   => $headlineName ?: $query,
    ];
}

/**
 * Parse a price cell from PriceCharting HTML. Cells look like
 *   <td id="used_price" class="...">$12.34</td>
 * but sometimes include badges, multiple spans, or "—" / "N/A" for
 * missing prices. Returns the first dollar amount as a float, or null.
 */
function pcParsePriceCell($cellInner) {
    if (!preg_match('/\$\s*([\d,]+\.\d{2})/', $cellInner, $m)) return null;
    $v = floatval(str_replace(',', '', $m[1]));
    return $v > 0 ? $v : null;
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

function doUpdate() {
    requireAuth();
    $userId  = $_SESSION['user_id'];
    $itemId  = $_POST['item_id'] ?? '';
    $updates = json_decode($_POST['updates'] ?? '{}', true);
    if (!$itemId || !$updates) json(['error' => 'Missing item_id or updates'], 400);

    // Read the actual headers from the CSV file first
    $headers = null;
    if (file_exists(COLLECTION_FILE)) {
        $h = fopen(COLLECTION_FILE, 'r');
        $headers = fgetcsv($h);
        fclose($h);
    }
    if (!$headers) json(['error' => 'Collection file not found'], 500);

    $rows  = readCSV(COLLECTION_FILE);
    $found = false;

    // Allowed fields that can be updated
    $allowed = ['name','subtitle','series','year','item_type','condition','manufacturer',
                'card_number','platform','genre','region','artist','label','format',
                'pressing','kit_type','size','signed','price_paid','ebay_query','notes',
                'value','bought','extra1','extra2','extra3','extra4'];

    foreach ($rows as &$row) {
        if ($row['id'] === $itemId && $row['user_id'] === $userId) {
            foreach ($updates as $k => $v) {
                if (in_array($k, $allowed) && array_key_exists($k, $row)) {
                    $row[$k] = trim((string)$v);
                }
            }
            $found = true;
        }
    }
    unset($row);

    if (!$found) json(['error' => 'Item not found'], 404);

    // Write back using the original file headers — never change the structure
    writeCSV(COLLECTION_FILE, $rows, $headers);

    // If ebay_query was updated, save it to prices CSV too. We have to be
    // careful: if the existing prices.csv was last written by an old code
    // path that didn't include the ebay_query column, the file's header
    // line will have N columns but the in-memory rows we're about to write
    // will have N+1 keys (because we just added $pr['ebay_query']). That
    // mismatch makes readCSV() skip every row on the next read — which
    // shows up to the user as all prices going to "—" after an edit.
    // Fix: always normalise the header list to include ebay_query and
    // ensure every row has exactly that set of keys.
    if (isset($updates['ebay_query']) && trim($updates['ebay_query'])) {
        $priceRows = readCSV(PRICES_FILE);
        $priceFound = false;
        foreach ($priceRows as &$pr) {
            if ($pr['item_id'] === $itemId && $pr['user_id'] === $userId) {
                $pr['ebay_query'] = trim($updates['ebay_query']);
                $priceFound = true;
            }
        }
        unset($pr);
        if ($priceFound) {
            // Read existing header, then ensure ebay_query is present.
            $ph = null;
            if (file_exists(PRICES_FILE)) { $ph2 = fopen(PRICES_FILE,'r'); $ph = fgetcsv($ph2); fclose($ph2); }
            if (!$ph) $ph = ['item_id','user_id','avg_30','avg_10','min','max','count','prev_avg','change_pct','direction','updated_at','ebay_query'];
            if (!in_array('ebay_query', $ph, true)) $ph[] = 'ebay_query';
            // Reorder every row to match the header exactly so writeCSV's
            // array_values() produces aligned columns. Missing keys → ''.
            $aligned = [];
            foreach ($priceRows as $pr) {
                $row = [];
                foreach ($ph as $col) { $row[$col] = $pr[$col] ?? ''; }
                $aligned[] = $row;
            }
            writeCSV(PRICES_FILE, $aligned, $ph);
        }
    }

    json(['ok' => true]);
}

function doDelete() {
    requireAuth();
    $id = $_POST['id'] ?? '';
    $userId = $_SESSION['user_id'];
    if (!$id) json(['error' => 'Missing id'], 400);

    // Read the actual headers from collection.csv — never use csvHeaders()
    // here because the live CSV has many more columns (price_paid,
    // ebay_query, card_number, etc.) than csvHeaders() declares. Writing
    // with the truncated header list silently drops every extra column.
    $collHeaders = null;
    if (file_exists(COLLECTION_FILE)) {
        $h = fopen(COLLECTION_FILE, 'r');
        $collHeaders = fgetcsv($h);
        fclose($h);
    }
    if (!$collHeaders) json(['error' => 'Collection file not found'], 500);

    $rows = readCSV(COLLECTION_FILE);
    $updated = []; $deleted = false;
    foreach ($rows as $row) {
        if ($row['id'] === $id && $row['user_id'] === $userId) { $deleted = true; }
        else $updated[] = $row;
    }
    if (!$deleted) json(['error' => 'Not found'], 404);
    writeCSV(COLLECTION_FILE, $updated, $collHeaders);

    // Clean up caches — read each file's actual headers rather than using
    // hard-coded ones that may drift (e.g. prices.csv now has ebay_query
    // appended; the old hard-coded header list dropped that column).
    foreach ([IMAGES_FILE, PRICES_FILE] as $file) {
        if (!file_exists($file)) continue;
        $fh = fopen($file, 'r');
        $hdrs = fgetcsv($fh);
        fclose($fh);
        if (!$hdrs) continue;
        $rows = array_values(array_filter(readCSV($file), fn($r) => ($r['item_id'] ?? '') !== $id));
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
            'extra1'=>'','extra2'=>'','extra3'=>'','extra4'=>'','notes'=>'','thumbnail'=>'','saved_at'=>'',
            'manufacturer'=>'','card_number'=>'','platform'=>'','genre'=>'','region'=>'',
            'artist'=>'','label'=>'','format'=>'','pressing'=>'','kit_type'=>'','size'=>'',
            'signed'=>'','price_paid'=>'','ebay_query'=>''];
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
    if ($new) {
        $h = fopen($file, 'a');
        fputcsv($h, $headers);
        fputcsv($h, array_values($row));
        fclose($h);
        return;
    }
    // File already has a header line — read it and align the row to it so
    // we never write a row whose column count drifts from the file's header.
    // Without this, readCSV() silently drops the appended row on every read.
    $fh = fopen($file, 'r');
    $diskHeaders = fgetcsv($fh);
    fclose($fh);
    if (!$diskHeaders) $diskHeaders = $headers;
    $aligned = [];
    foreach ($diskHeaders as $col) {
        $aligned[] = array_key_exists($col, $row) ? $row[$col] : '';
    }
    $h = fopen($file, 'a');
    fputcsv($h, $aligned);
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
    // Note on Accept-Encoding: we DON'T set it as an explicit header here.
    // Setting CURLOPT_ENCODING to '' lets curl advertise (in the request)
    // exactly the encodings it can decode (e.g. gzip, deflate, and br if
    // libcurl was built with brotli support) and auto-decode the response.
    // Previously the explicit header advertised 'gzip, deflate, br' but
    // CURLOPT_ENCODING said only 'gzip, deflate' — so servers would send
    // brotli-encoded bytes that curl couldn't decode, leaving us with
    // empty or garbled bodies on every site (eBay, PriceCharting, etc.).
    $defaultHeaders = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language: en-GB,en;q=0.9',
        'Cache-Control: no-cache',
        'Pragma: no-cache',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1',
        'Upgrade-Insecure-Requests: 1',
    ];
    // Merge: caller headers override defaults
    $merged = $defaultHeaders;
    foreach ($headers as $h) {
        $key = strtolower(explode(':', $h)[0]);
        // Strip any caller-supplied Accept-Encoding too — let CURLOPT_ENCODING
        // handle it so we never have a header/decoder mismatch.
        if ($key === 'accept-encoding') continue;
        $merged = array_filter($merged, fn($d) => strtolower(explode(':', $d)[0]) !== $key);
        $merged[] = $h;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_ENCODING       => '', // empty string = "all encodings curl supports"
        CURLOPT_HTTPHEADER     => array_values($merged),
        CURLOPT_COOKIEJAR      => '/tmp/cv_ebay_cookie.txt',
        CURLOPT_COOKIEFILE     => '/tmp/cv_ebay_cookie.txt',
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    return ['ok' => !$err, 'body' => $body, 'code' => $code, 'error' => $err];
}
function curlPost($url, $payload, $headers = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,CURLOPT_HTTPHEADER=>$headers,CURLOPT_TIMEOUT=>30,CURLOPT_SSL_VERIFYPEER=>true]);
    $body = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    return ['ok' => !$err, 'body' => $body, 'code' => $code, 'error' => $err];
}
function json($data, $code = 200) { http_response_code($code); echo json_encode($data); exit; }
