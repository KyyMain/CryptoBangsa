<?php

define('ENCRYPTION_KEY', '00435a2f62fc8b58035c2b94ddf447500a17747bf51159668ce8b0bb515faa14');

define('ENCRYPTION_METHOD', 'aes-256-gcm');

define('AUTH_TAG_LENGTH', 16);

define('SEPARATOR', '.');

define('EMPTY_AAD', '');

define('BINARY_KEY_LENGTH', 32);

define('TOKEN_TTL_SECONDS', 300);

define('DEFAULT_AAD_ALIAS', 'global');

define('AAD_ALIAS_MAP', [
    'global' => 'global-demo-binding',
    'detail' => 'detail.php-route-binding',
]);

/**
 * Mengembalikan kunci biner sepanjang 32 byte.
 */
function get_encryption_key()
{
    $hex_key = ENCRYPTION_KEY;
    $binary_key = hex2bin($hex_key);

    if ($binary_key !== false && strlen($binary_key) === BINARY_KEY_LENGTH) {
        return $binary_key;
    }

    // Fallback: gunakan string apa adanya (openssl akan melakukan pemotongan/padding).
    return substr($hex_key, 0, BINARY_KEY_LENGTH);
}

function get_aad_value(string $alias): string
{
    $map = AAD_ALIAS_MAP;

    if (isset($map[$alias])) {
        return $map[$alias];
    }

    return $map[DEFAULT_AAD_ALIAS] ?? EMPTY_AAD;
}

function normalize_ttl(array $options): int
{
    $ttl = isset($options['ttl']) ? (int) $options['ttl'] : TOKEN_TTL_SECONDS;

    return $ttl > 0 ? $ttl : TOKEN_TTL_SECONDS;
}

function build_payload_array($value, string $alias, array $options): array
{
    $issued = time();
    $ttl = normalize_ttl($options);
    $context = $options['context'] ?? [];

    if (!is_array($context)) {
        $context = ['raw' => $context];
    }

    $payload = [
        'version' => 1,
        'value' => (string) $value,
        'issued_at' => $issued,
        'expires_at' => $issued + $ttl,
        'alias' => $alias,
        'context' => array_filter($context, static fn($item) => $item !== null && $item !== ''),
    ];

    return $payload;
}

function perform_encryption(string $plaintext, string $aad, string $alias)
{
    $key = get_encryption_key();
    $iv_length = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    $iv = random_bytes($iv_length);
    $tag = '';

    $ciphertext = openssl_encrypt($plaintext, ENCRYPTION_METHOD, $key, OPENSSL_RAW_DATA, $iv, $tag, $aad, AUTH_TAG_LENGTH);

    if ($ciphertext === false || $tag === '') {
        return false;
    }

    $segments = [
        base64url_encode($alias),
        base64url_encode($iv),
        base64url_encode($tag),
        base64url_encode($ciphertext),
    ];

    return [
        'token' => implode(SEPARATOR, $segments),
        'iv' => $iv,
        'tag' => $tag,
        'ciphertext' => $ciphertext,
        'alias' => $alias,
        'payload' => $plaintext,
    ];
}

/**
 * Melakukan Base64URL encode.
 */
function base64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Melakukan Base64URL decode secara ketat.
 */
function base64url_decode($data)
{
    $padded = $data;
    $remainder = strlen($padded) % 4;
    if ($remainder) {
        $padded .= str_repeat('=', 4 - $remainder);
    }

    $decoded = base64_decode(strtr($padded, '-_', '+/'), true);

    return $decoded === false ? false : $decoded;
}

/**
 * Fungsi untuk mengenkripsi data (dengan autentikasi).
 * @param string $data Data yang akan dienkripsi.
 * @return string|false Token terenkripsi (Base64URL) atau false jika gagal.
 */
function encrypt_data($data, array $options = [])
{
    $alias = $options['aad_alias'] ?? DEFAULT_AAD_ALIAS;
    $aad = get_aad_value($alias);
    $payload_array = build_payload_array($data, $alias, $options);

    $payload_json = json_encode($payload_array, JSON_UNESCAPED_SLASHES);

    if ($payload_json === false) {
        return false;
    }

    $result = perform_encryption($payload_json, $aad, $alias);

    if ($result === false) {
        return false;
    }

    return $result['token'];
}

/**
 * Fungsi untuk mendekripsi data terenkripsi.
 * @param string $token Token terenkripsi (Base64URL).
 * @return string|false Data asli, atau false jika gagal.
 */
function decrypt_data($token)
{
    $analysis = analyze_token($token);

    if ($analysis['status'] !== 'ok') {
        return false;
    }

    return $analysis['value'];
}

function encrypt_data_verbose($data, array $options = [])
{
    $alias = $options['aad_alias'] ?? DEFAULT_AAD_ALIAS;
    $aad = get_aad_value($alias);
    $payload_array = build_payload_array($data, $alias, $options);
    $payload_json = json_encode($payload_array, JSON_UNESCAPED_SLASHES);

    if ($payload_json === false) {
        return false;
    }

    $result = perform_encryption($payload_json, $aad, $alias);

    if ($result === false) {
        return false;
    }

    return [
        'token' => $result['token'],
        'alias' => $alias,
        'payload_json' => $payload_json,
        'payload' => $payload_array,
        'segments' => explode(SEPARATOR, $result['token']),
        'iv_base64' => base64url_encode($result['iv']),
        'iv_hex' => bin2hex($result['iv']),
        'tag_base64' => base64url_encode($result['tag']),
        'tag_hex' => bin2hex($result['tag']),
        'cipher_base64' => base64url_encode($result['ciphertext']),
        'cipher_hex' => bin2hex($result['ciphertext']),
    ];
}

function analyze_token(string $token, array $options = []): array
{
    $parts = explode(SEPARATOR, $token);
    $part_count = count($parts);

    if ($part_count !== 4 && $part_count !== 3) {
        return [
            'status' => 'invalid',
            'error' => 'Format token tidak sesuai',
            'value' => null,
            'meta' => null,
        ];
    }

    if ($part_count === 4) {
        [$alias_encoded, $iv_encoded, $tag_encoded, $cipher_encoded] = $parts;
        $alias_decoded = base64url_decode($alias_encoded);
        $alias = $alias_decoded !== false && $alias_decoded !== '' ? $alias_decoded : DEFAULT_AAD_ALIAS;
    } else {
        [$iv_encoded, $tag_encoded, $cipher_encoded] = $parts;
        $alias = DEFAULT_AAD_ALIAS;
    }

    $expected_alias = $options['expected_alias'] ?? null;
    if ($expected_alias !== null && $alias !== $expected_alias) {
        return [
            'status' => 'invalid',
            'error' => 'Alias AAD tidak cocok',
            'value' => null,
            'meta' => ['alias' => $alias],
        ];
    }

    $iv = base64url_decode($iv_encoded);
    $tag = base64url_decode($tag_encoded);
    $ciphertext = base64url_decode($cipher_encoded);

    if ($iv === false || $tag === false || $ciphertext === false) {
        return [
            'status' => 'invalid',
            'error' => 'Segment token tidak valid',
            'value' => null,
            'meta' => ['alias' => $alias],
        ];
    }

    if ($iv === '' || $tag === '' || $ciphertext === '') {
        return [
            'status' => 'invalid',
            'error' => 'Segment token kosong',
            'value' => null,
            'meta' => ['alias' => $alias],
        ];
    }

    $aad = get_aad_value($alias);
    $key = get_encryption_key();

    $plaintext = openssl_decrypt($ciphertext, ENCRYPTION_METHOD, $key, OPENSSL_RAW_DATA, $iv, $tag, $aad);

    if ($plaintext === false) {
        return [
            'status' => 'invalid',
            'error' => 'Autentikasi GCM gagal',
            'value' => null,
            'meta' => ['alias' => $alias],
        ];
    }

    $decoded = json_decode($plaintext, true);

    if (!is_array($decoded)) {
        return [
            'status' => 'ok',
            'value' => $plaintext,
            'meta' => [
                'alias' => $alias,
                'legacy' => true,
            ],
            'error' => null,
        ];
    }

    $now = time();
    $expires_at = isset($decoded['expires_at']) ? (int) $decoded['expires_at'] : null;
    $issued_at = isset($decoded['issued_at']) ? (int) $decoded['issued_at'] : null;

    if ($expires_at !== null && $expires_at < $now) {
        return [
            'status' => 'invalid',
            'error' => 'Token telah kedaluwarsa',
            'value' => null,
            'meta' => [
                'alias' => $alias,
                'issued_at' => $issued_at,
                'expires_at' => $expires_at,
                'context' => $decoded['context'] ?? [],
            ],
        ];
    }

    return [
        'status' => 'ok',
        'value' => $decoded['value'] ?? '',
        'meta' => [
            'alias' => $alias,
            'issued_at' => $issued_at,
            'expires_at' => $expires_at,
            'context' => $decoded['context'] ?? [],
            'payload' => $decoded,
            'remaining_seconds' => $expires_at !== null ? max(0, $expires_at - $now) : null,
        ],
        'error' => null,
    ];
}
