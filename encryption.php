<?php

define('ENCRYPTION_KEY', '00435a2f62fc8b58035c2b94ddf447500a17747bf51159668ce8b0bb515faa14');

define('ENCRYPTION_METHOD', 'aes-256-gcm');

define('AUTH_TAG_LENGTH', 16);

define('SEPARATOR', '.');

define('EMPTY_AAD', '');

define('BINARY_KEY_LENGTH', 32);

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
function encrypt_data($data)
{
    $key = get_encryption_key();
    $iv_length = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    $iv = random_bytes($iv_length);
    $tag = '';

    $ciphertext = openssl_encrypt($data, ENCRYPTION_METHOD, $key, OPENSSL_RAW_DATA, $iv, $tag, EMPTY_AAD, AUTH_TAG_LENGTH);

    if ($ciphertext === false || $tag === '') {
        return false;
    }

    $segments = [
        base64url_encode($iv),
        base64url_encode($tag),
        base64url_encode($ciphertext),
    ];

    return implode(SEPARATOR, $segments);
}

/**
 * Fungsi untuk mendekripsi data terenkripsi.
 * @param string $token Token terenkripsi (Base64URL).
 * @return string|false Data asli, atau false jika gagal.
 */
function decrypt_data($token)
{
    $key = get_encryption_key();

    $parts = explode(SEPARATOR, $token);
    if (count($parts) !== 3) {
        return false;
    }

    [$iv_encoded, $tag_encoded, $cipher_encoded] = $parts;

    $iv = base64url_decode($iv_encoded);
    $tag = base64url_decode($tag_encoded);
    $ciphertext = base64url_decode($cipher_encoded);

    if ($iv === false || $tag === false || $ciphertext === false) {
        return false;
    }

    if ($iv === '' || $tag === '' || $ciphertext === '') {
        return false;
    }

    $plaintext = openssl_decrypt($ciphertext, ENCRYPTION_METHOD, $key, OPENSSL_RAW_DATA, $iv, $tag, EMPTY_AAD);

    return $plaintext === false ? false : $plaintext;
}
