<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once 'encryption.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'tamper_check') {
    header('Content-Type: application/json');
    $token_input = $_POST['token'] ?? '';
    $analysis = analyze_token($token_input, ['expected_alias' => 'detail']);

    echo json_encode([
        'status' => $analysis['status'],
        'reason' => $analysis['error'] ?? null,
        'meta' => $analysis['meta'] ?? null,
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

$users = [
    [
        'id' => 1007865,
        'nama' => 'Linbergh Atmaja',
        'email' => 'linbergh@security.net',
        'role' => 'Lead securityy Engineer',
        'location' => 'Jakarta, Indonesia',
        'summary' => 'Memimpin audit keamanan untuk produk digital dan menyusun standar enkripsi perusahaan.'
    ],
    [
        'id' => 1007872,
        'nama' => 'Skyu Pratama',
        'email' => 'skyu@security.net',
        'role' => 'Product Designer',
        'location' => 'Bandung, Indonesia',
        'summary' => 'Fokus pada pengalaman pengguna yang mulus dengan desain yang kaya interaksi dan micro-animation.'
    ],
    [
        'id' => 1007831,
        'nama' => 'Xzero One',
        'email' => 'xzero.one@security.net',
        'role' => 'AI Researcher',
        'location' => 'Jogja, Indonesia',
        'summary' => 'Meneliti algoritma AI yang dapat membantu mendeteksi gangguan keamanan jaringan secara real-time.'
    ],
    [
        'id' => 1007847,
        'nama' => 'John "Soap" Mactavish',
        'email' => 'soap.mactavish@security.net',
        'role' => 'Incident Response Specialist',
        'location' => 'Surabaya, Indonesia',
        'summary' => 'Bertugas mengurai insiden keamanan dan memastikan tim siap menghadapi ancaman siber terkini.'
    ],
    [
        'id' => 1007817,
        'nama' => 'Kyra Foster',
        'email' => 'Kyra@security.net',
        'role' => 'Cyber Inteligent',
        'location' => 'Tamboen, Indonesia',
        'summary' => 'Bertugas sebagai intelegensi negara demi menajaga keamanan ruang cyber'
    ],
];

$user_found = null;
$decrypted_id = null;
$token = $_GET['id'] ?? null;
$token_analysis = null;
$token_meta = [];
$invalid_reason = null;
$token_valid = false;
$aad_alias = DEFAULT_AAD_ALIAS;
$expires_at = null;
$issued_at = null;
$remaining_seconds = null;

if ($token) {
    $token_analysis = analyze_token($token, ['expected_alias' => 'detail']);
    $token_meta = $token_analysis['meta'] ?? [];
    $aad_alias = $token_meta['alias'] ?? DEFAULT_AAD_ALIAS;
    $expires_at = $token_meta['expires_at'] ?? null;
    $issued_at = $token_meta['issued_at'] ?? null;
    $remaining_seconds = $token_meta['remaining_seconds'] ?? ($expires_at ? max(0, $expires_at - time()) : null);

    if ($token_analysis['status'] === 'ok') {
        $decrypted_id = $token_analysis['value'];

        foreach ($users as $user) {
            if ((string) $user['id'] === (string) $decrypted_id) {
                $user_found = $user;
                $token_valid = true;
                break;
            }
        }

        if (!$token_valid) {
            $invalid_reason = 'ID tidak ditemukan dalam dataset demo.';
        }
    } else {
        $invalid_reason = $token_analysis['error'] ?? 'Token tidak valid.';
    }
}

if (!isset($_SESSION['audit']) || !is_array($_SESSION['audit'])) {
    $_SESSION['audit'] = [
        'valid' => 0,
        'invalid' => 0,
        'last_failure_reason' => null,
        'last_failure_at' => null,
        'last_valid_at' => null,
    ];
}

if ($token) {
    if ($token_valid && $user_found !== null) {
        $_SESSION['audit']['valid'] = ($_SESSION['audit']['valid'] ?? 0) + 1;
        $_SESSION['audit']['last_valid_at'] = time();
    } else {
        $_SESSION['audit']['invalid'] = ($_SESSION['audit']['invalid'] ?? 0) + 1;
        $_SESSION['audit']['last_failure_reason'] = $invalid_reason ?? 'Token tidak valid.';
        $_SESSION['audit']['last_failure_at'] = time();
    }
}

$aad_binding = get_aad_value($aad_alias);
$issued_label = $issued_at ? date('H:i:s', $issued_at) : '—';
$expires_label = $expires_at ? date('H:i:s', $expires_at) : '—';
$remaining_label = $remaining_seconds !== null ? gmdate('i:s', max(0, $remaining_seconds)) : '—';

$context_note = '';
if (!empty($token_meta['context'])) {
    $pairs = [];
    foreach ($token_meta['context'] as $key => $value) {
        $pairs[] = $key . ': ' . $value;
    }
    $context_note = implode(', ', $pairs);
}

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$base_path = rtrim(dirname($_SERVER['PHP_SELF'] ?? '/detail.php'), '/\\');
$base_path = $base_path === '' ? '' : $base_path . '/';
$share_url = $token ? $scheme . $host . $base_path . 'detail.php?id=' . rawurlencode($token) : $scheme . $host . $base_path . 'detail.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pengguna</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #1e293b, #0f172a);
            --accent: #38bdf8;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #020617;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header-gradient {
            background: var(--bg-gradient);
            position: relative;
            overflow: hidden;
        }

        .header-gradient::after {
            content: "";
            position: absolute;
            width: 380px;
            height: 380px;
            border-radius: 50%;
            background: rgba(56, 189, 248, 0.28);
            filter: blur(120px);
            top: -140px;
            right: -120px;
        }

        .glass-panel {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 24px;
            box-shadow: 0 24px 60px rgba(2, 6, 23, 0.6);
            backdrop-filter: blur(14px);
            overflow: hidden;
        }

        .info-chip {
            background: rgba(56, 189, 248, 0.18);
            color: var(--accent);
            padding: 0.4rem 0.9rem;
            border-radius: 999px;
            font-size: 0.75rem;
            letter-spacing: 0.06rem;
            text-transform: uppercase;
        }

        .divider {
            height: 1px;
            background: rgba(148, 163, 184, 0.16);
            margin: 1.5rem 0;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.2rem;
        }

        footer {
            background: rgba(15, 23, 42, 0.85);
            margin-top: auto;
        }

        .pulse {
            position: relative;
        }

        .pulse::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            border: 1px solid rgba(56, 189, 248, 0.25);
            animation: pulse 2.6s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.7;
            }

            100% {
                transform: scale(1.06);
                opacity: 0;
            }
        }

        .countdown-badge {
            background: rgba(56, 189, 248, 0.18);
            color: #38bdf8;
            border-radius: 999px;
            padding: 0.35rem 0.9rem;
            font-size: 0.8rem;
            letter-spacing: 0.04rem;
        }

        .token-utils {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .token-utils .btn {
            min-width: 160px;
        }

        .qr-wrapper {
            display: none;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 16px;
            padding: 1.25rem;
        }
    </style>
</head>

<body>
    <header class="header-gradient py-5">
        <div class="container py-4">
            <a href="index.php" class="btn btn-outline-light btn-sm mb-4">
                <i class="bi bi-arrow-left"></i>
                Kembali ke Beranda
            </a>
            <h1 class="display-6 fw-bold mb-3">Detail Pengguna</h1>
            <p class="lead text-light opacity-75 mb-0">Parameter URL dienkripsi menggunakan AES-256-GCM sebelum sampai ke halaman ini. Sistem kemudian mendekripsi token untuk menemukan data asli.</p>
        </div>
    </header>

    <main class="container my-5 flex-grow-1">
        <?php if ($user_found): ?>
            <section class="glass-panel p-4 p-lg-5">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-4">
                    <div>
                        <span class="info-chip mb-3 d-inline-block">Token tervalidasi</span>
                        <h2 class="fw-bold mb-1"><?= htmlspecialchars($user_found['nama']) ?></h2>
                        <p class="text-info mb-2 fw-semibold"><?= htmlspecialchars($user_found['role']) ?></p>
                        <p class="text-light opacity-75 mb-0"><?= htmlspecialchars($user_found['summary']) ?></p>
                    </div>
                    <div class="pulse rounded-4 border border-info border-opacity-25 p-3 text-light opacity-75 small w-100 w-md-auto">
                        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                            <div>
                                <p class="text-uppercase text-info fw-semibold mb-1">Token Enkripsi</p>
                                <p class="mb-0 text-light opacity-75">Terikat ke <?= htmlspecialchars($aad_binding) ?></p>
                            </div>
                            <span class="countdown-badge" id="tokenCountdown" data-expires="<?= htmlspecialchars((string) ($expires_at ?? '')) ?>" data-remaining="<?= htmlspecialchars((string) ($remaining_seconds ?? '')) ?>">
                                Sisa <?= htmlspecialchars($remaining_label) ?>
                            </span>
                        </div>
                        <p class="mt-3 mb-0 text-break" id="tokenValue"><?= htmlspecialchars($token) ?></p>
                        <div class="token-utils mt-3">
                            <button type="button" class="btn btn-outline-info btn-sm" id="copyTokenBtn" data-token="<?= htmlspecialchars($token) ?>">
                                <i class="bi bi-clipboard me-2"></i>Salin Token
                            </button>
                            <button type="button" class="btn btn-outline-light btn-sm" id="copyUrlBtn" data-url="<?= htmlspecialchars($share_url) ?>">
                                <i class="bi bi-link-45deg me-2"></i>Salin URL Aman
                            </button>
                            <button type="button" class="btn btn-outline-warning btn-sm" id="qrToggleBtn" data-url="<?= htmlspecialchars($share_url) ?>">
                                <i class="bi bi-qr-code me-2"></i>Tampilkan QR
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="tamperBtn" data-token="<?= htmlspecialchars($token) ?>">
                                <i class="bi bi-bug-fill me-2"></i>Simulasikan Token Rusak
                            </button>
                        </div>
                        <div id="tamperAlert" class="alert alert-danger mt-3 d-none" role="alert"></div>
                    </div>
                </div>

                <div class="qr-wrapper mt-3" id="qrWrapper">
                    <div class="d-flex flex-column align-items-center gap-3">
                        <canvas id="tokenQrCanvas" width="200" height="200"></canvas>
                        <p class="small text-light opacity-75 mb-0 text-center">QR ini memuat URL terenkripsi sehingga dapat dipindai tanpa membuka ID asli.</p>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="detail-grid">
                    <div>
                        <p class="text-uppercase text-secondary small mb-1">ID Asli</p>
                        <h3 class="h4 fw-semibold text-light"><?= htmlspecialchars($user_found['id']) ?></h3>
                    </div>
                    <div>
                        <p class="text-uppercase text-secondary small mb-1">Email</p>
                        <p class="mb-0 text-light opacity-80"><?= htmlspecialchars($user_found['email']) ?></p>
                    </div>
                    <div>
                        <p class="text-uppercase text-secondary small mb-1">Lokasi</p>
                        <p class="mb-0 text-light opacity-80"><?= htmlspecialchars($user_found['location']) ?></p>
                    </div>
                    <div>
                        <p class="text-uppercase text-secondary small mb-1">Status Token</p>
                        <span class="badge text-bg-success"><i class="bi bi-patch-check-fill me-1"></i>Valid</span>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="detail-grid">
                    <div>
                        <p class="text-uppercase text-secondary small mb-1">Alias AAD</p>
                        <p class="mb-0 text-light opacity-80"><?= htmlspecialchars($aad_alias) ?></p>
                    </div>
                    <div>
                        <p class="text-uppercase text-secondary small mb-1">Binding String</p>
                        <p class="mb-0 text-light opacity-80 text-break"><?= htmlspecialchars($aad_binding) ?></p>
                    </div>
                    <div>
                        <p class="text-uppercase text-secondary small mb-1">Dikeluarkan</p>
                        <p class="mb-0 text-light opacity-80"><?= htmlspecialchars($issued_label) ?></p>
                    </div>
                    <div>
                        <p class="text-uppercase text-secondary small mb-1">Kedaluwarsa</p>
                        <p class="mb-0 text-light opacity-80"><?= htmlspecialchars($expires_label) ?> (<?= htmlspecialchars($remaining_label) ?>)</p>
                    </div>
                    <div>
                        <p class="text-uppercase text-secondary small mb-1">Context</p>
                        <p class="mb-0 text-light opacity-80"><?= htmlspecialchars($context_note ?: '—') ?></p>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="border border-info border-opacity-25 rounded-4 p-4 h-100">
                            <h3 class="h6 text-info fw-semibold">Cara Kerja Dekripsi</h3>
                            <p class="small text-light opacity-75 mb-0">Token URL di-decode dari Base64URL, dipisah menjadi IV, authentication tag, dan ciphertext, lalu diolah dengan `openssl_decrypt` (mode GCM) untuk mendapatkan ID pengguna.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border border-warning border-opacity-25 rounded-4 p-4 h-100">
                            <h3 class="h6 text-warning fw-semibold">Catatan Keamanan</h3>
                            <p class="small text-light opacity-75 mb-0">Simpan kunci enkripsi secara terpisah, rotasi berkala, dan selalu gunakan transport layer aman (HTTPS) untuk menghindari kebocoran.</p>
                        </div>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <section class="glass-panel p-4 p-lg-5 text-center">
                <div class="display-4 text-warning mb-3">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h2 class="fw-bold mb-3">Token Tidak Valid</h2>
                <p class="text-light opacity-75 mb-2">Kami tidak dapat menemukan data untuk token ini. Pastikan URL yang Anda gunakan adalah hasil enkripsi dari sistem kami.</p>
                <?php if ($invalid_reason !== null): ?>
                    <p class="text-warning small mb-3">Detail: <?= htmlspecialchars($invalid_reason) ?></p>
                <?php endif; ?>
                <?php if ($token): ?>
                    <p class="text-light opacity-75 small mb-4">Token yang diterima: <span class="text-warning text-break"><?= htmlspecialchars($token) ?></span></p>
                <?php endif; ?>
                <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-arrow-repeat me-2"></i>Enkripsi Ulang dari Beranda
                </a>
            </section>
        <?php endif; ?>
    </main>

    <footer class="py-4 text-center text-light opacity-75 small">
        <span>&copy; <?= date('Y') ?> CryptoBangsa Showcase — Enkripsi URL</span>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
    <script>
        (function () {
            const countdownEl = document.getElementById('tokenCountdown');
            if (countdownEl) {
                let remaining = parseInt(countdownEl.dataset.remaining, 10);
                if (Number.isNaN(remaining)) {
                    remaining = null;
                }

                const formatTime = (seconds) => {
                    const clamped = Math.max(0, seconds);
                    const minutes = String(Math.floor(clamped / 60)).padStart(2, '0');
                    const secs = String(clamped % 60).padStart(2, '0');
                    return `${minutes}:${secs}`;
                };

                if (remaining !== null) {
                    let timerId = null;
                    const updateCountdown = () => {
                        countdownEl.textContent = `Sisa ${formatTime(remaining)}`;
                        if (remaining <= 0) {
                            clearInterval(timerId);
                        }
                        remaining -= 1;
                    };

                    updateCountdown();
                    timerId = setInterval(updateCountdown, 1000);
                }
            }

            const escapeHtml = (value) => {
                return String(value).replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;',
                })[char]);
            };

            const giveFeedback = (button, html) => {
                const original = button.innerHTML;
                button.innerHTML = html;
                button.disabled = true;
                setTimeout(() => {
                    button.innerHTML = original;
                    button.disabled = false;
                }, 1400);
            };

            const copyToClipboard = async (text) => {
                if (!text) {
                    throw new Error('Tidak ada konten untuk disalin');
                }
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    return navigator.clipboard.writeText(text);
                }
                const tempInput = document.createElement('textarea');
                tempInput.value = text;
                tempInput.style.position = 'fixed';
                tempInput.style.opacity = '0';
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                return Promise.resolve();
            };

            const copyTokenBtn = document.getElementById('copyTokenBtn');
            if (copyTokenBtn) {
                copyTokenBtn.addEventListener('click', () => {
                    copyToClipboard(copyTokenBtn.dataset.token || '')
                        .then(() => giveFeedback(copyTokenBtn, '<i class="bi bi-check-lg me-2"></i>Tersalin!'))
                        .catch(() => giveFeedback(copyTokenBtn, '<i class="bi bi-exclamation-circle me-2"></i>Gagal menyalin'));
                });
            }

            const copyUrlBtn = document.getElementById('copyUrlBtn');
            if (copyUrlBtn) {
                copyUrlBtn.addEventListener('click', () => {
                    copyToClipboard(copyUrlBtn.dataset.url || '')
                        .then(() => giveFeedback(copyUrlBtn, '<i class="bi bi-check-lg me-2"></i>URL tersalin'))
                        .catch(() => giveFeedback(copyUrlBtn, '<i class="bi bi-exclamation-circle me-2"></i>Gagal menyalin'));
                });
            }

            const qrWrapper = document.getElementById('qrWrapper');
            const qrCanvas = document.getElementById('tokenQrCanvas');
            const qrToggleBtn = document.getElementById('qrToggleBtn');
            let qrVisible = false;
            let qrInstance = null;

            if (qrToggleBtn && qrWrapper && qrCanvas && typeof QRious !== 'undefined') {
                const originalHtml = qrToggleBtn.innerHTML;
                qrToggleBtn.addEventListener('click', () => {
                    qrVisible = !qrVisible;
                    if (qrVisible) {
                        if (!qrInstance) {
                            qrInstance = new QRious({
                                element: qrCanvas,
                                value: qrToggleBtn.dataset.url || '',
                                size: 200,
                                level: 'H',
                            });
                        } else {
                            qrInstance.set({ value: qrToggleBtn.dataset.url || '' });
                        }
                        qrWrapper.style.display = 'block';
                        qrToggleBtn.innerHTML = '<i class="bi bi-eye-slash me-2"></i>Sembunyikan QR';
                    } else {
                        qrWrapper.style.display = 'none';
                        qrToggleBtn.innerHTML = originalHtml;
                    }
                });
            }

            const tamperBtn = document.getElementById('tamperBtn');
            const tamperAlert = document.getElementById('tamperAlert');

            const mutateToken = (token) => {
                if (!token) {
                    return token;
                }
                const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';
                const chars = token.split('');
                let index = Math.floor(Math.random() * chars.length);
                if (chars[index] === '.') {
                    index = (index + 1) % chars.length;
                }
                const current = chars[index];
                let replacement = alphabet[Math.floor(Math.random() * alphabet.length)];
                if (replacement === current) {
                    replacement = alphabet[(alphabet.indexOf(replacement) + 1) % alphabet.length];
                }
                chars[index] = replacement;
                return chars.join('');
            };

            if (tamperBtn && tamperAlert) {
                tamperBtn.addEventListener('click', () => {
                    const original = tamperBtn.dataset.token || '';
                    const mutated = mutateToken(original);

                    if (!mutated || mutated === original) {
                        tamperAlert.classList.remove('d-none');
                        tamperAlert.classList.remove('alert-success');
                        tamperAlert.classList.add('alert-danger');
                        tamperAlert.innerHTML = 'Tidak dapat memodifikasi token untuk simulasi.';
                        return;
                    }

                    const body = new URLSearchParams();
                    body.append('action', 'tamper_check');
                    body.append('token', mutated);

                    fetch('detail.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body,
                    })
                        .then((response) => response.json())
                        .then((data) => {
                            tamperAlert.classList.remove('d-none', 'alert-success', 'alert-info');
                            if (data.status === 'invalid') {
                                const reason = data.reason ? escapeHtml(String(data.reason)) : 'Token ditolak oleh autentikasi GCM.';
                                tamperAlert.classList.add('alert-danger');
                                tamperAlert.innerHTML = `<strong>Token rusak terdeteksi!</strong><br><span class="small">Mutasi: ${escapeHtml(mutated)}</span><br>${reason}`;
                            } else {
                                tamperAlert.classList.add('alert-info');
                                tamperAlert.innerHTML = '<strong>Token masih lolos dekode.</strong> Sistem akan menolak ketika alias atau TTL tidak sesuai.';
                            }
                        })
                        .catch(() => {
                            tamperAlert.classList.remove('d-none');
                            tamperAlert.classList.remove('alert-success');
                            tamperAlert.classList.add('alert-danger');
                            tamperAlert.textContent = 'Tidak dapat memeriksa token yang dimodifikasi.';
                        });
                });
            }
        })();
    </script>
</body>

</html>
