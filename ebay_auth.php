<?php
/**
 * eBay OAuth2 token manager
 * Uses client_credentials grant (server-to-server, no user login needed)
 * Token cached in cv_ebay_token.json, auto-refreshed when expired
 */

function getEbayToken(): string {
    $credsPath = dirname(__DIR__) . '/cv_ebay_creds.json';
    $tokenPath = dirname(__DIR__) . '/cv_ebay_token.json';

    // Load cached token if still valid (with 5 min buffer)
    if (file_exists($tokenPath)) {
        $cached = json_decode(file_get_contents($tokenPath), true);
        if ($cached && isset($cached['expires_at']) && $cached['expires_at'] > time() + 300) {
            return $cached['access_token'];
        }
    }

    // Load credentials
    if (!file_exists($credsPath)) {
        throw new RuntimeException('cv_ebay_creds.json not found on server');
    }
    $creds = json_decode(file_get_contents($credsPath), true);
    if (!isset($creds['client_id'], $creds['client_secret'])) {
        throw new RuntimeException('cv_ebay_creds.json missing client_id or client_secret');
    }

    // Request new token
    $ch = curl_init('https://api.ebay.com/identity/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode($creds['client_id'] . ':' . $creds['client_secret']),
        ],
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type' => 'client_credentials',
            'scope'      => 'https://api.ebay.com/oauth/api_scope',
        ]),
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new RuntimeException("eBay token request failed (HTTP $httpCode): $body");
    }

    $data = json_decode($body, true);
    if (!isset($data['access_token'])) {
        throw new RuntimeException('eBay token response missing access_token');
    }

    // Cache it
    $data['expires_at'] = time() + (int)($data['expires_in'] ?? 7200);
    file_put_contents($tokenPath, json_encode($data));

    return $data['access_token'];
}
