<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once 'encryption.php';

$users = [
    [
        'id' => 1007865,
        'nama' => 'Linbergh Atmaja',
        'email' => 'linbergh@security.net',
        'role' => 'Lead Security Engineer',
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

$highlight_user = $users[0];
$plain_url = sprintf('detail.php?id=%d', $highlight_user['id']);
$detail_options = [
    'aad_alias' => 'detail',
    'context' => [
        'route' => 'detail.php',
        'highlight_id' => $highlight_user['id'],
    ],
];

$journey_verbose = encrypt_data_verbose($highlight_user['id'], $detail_options);

if ($journey_verbose === false) {
    $highlight_token = encrypt_data($highlight_user['id'], $detail_options);
    $journey_verbose = null;
} else {
    $highlight_token = $journey_verbose['token'];
}

$encrypted_url = $highlight_token !== false
    ? 'detail.php?id=' . $highlight_token
    : 'detail.php';

$journey_steps = [];

if ($journey_verbose !== null) {
    $aad_binding = get_aad_value($journey_verbose['alias']);
    $pretty_payload = json_encode($journey_verbose['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    $journey_steps = [
        [
            'key' => 'plain',
            'label' => 'Plain',
            'title' => 'Nilai Asli',
            'description' => 'ID pengguna sebelum proses kriptografi dimulai.',
            'content' => (string) $highlight_user['id'],
            'language' => 'text',
        ],
        [
            'key' => 'payload',
            'label' => 'Payload',
            'title' => 'Payload JSON',
            'description' => 'Nilai dibungkus bersama metadata TTL dan konteks.',
            'content' => $pretty_payload,
            'language' => 'json',
        ],
        [
            'key' => 'aad',
            'label' => 'AAD',
            'title' => 'Additional Authenticated Data',
            'description' => 'AAD memastikan token hanya valid di jalur tertentu.',
            'content' => "Alias: {$journey_verbose['alias']}\nBinding: {$aad_binding}",
            'language' => 'text',
        ],
        [
            'key' => 'components',
            'label' => 'IV & Tag',
            'title' => 'IV • Tag • Ciphertext',
            'description' => 'Komponen kriptografi yang dihasilkan dari AES-256-GCM.',
            'content' => "IV (b64): {$journey_verbose['iv_base64']}\nTag (b64): {$journey_verbose['tag_base64']}\nCipher (b64): {$journey_verbose['cipher_base64']}",
            'language' => 'text',
        ],
        [
            'key' => 'token',
            'label' => 'Token',
            'title' => 'Token Final',
            'description' => 'Setelah Base64URL, token siap dipasang sebagai query string.',
            'content' => $journey_verbose['token'],
            'language' => 'text',
        ],
    ];
}

$audit_defaults = [
    'valid' => 0,
    'invalid' => 0,
    'last_failure_reason' => null,
    'last_failure_at' => null,
    'last_valid_at' => null,
];

$audit_snapshot = array_merge($audit_defaults, $_SESSION['audit'] ?? []);

$last_failure_reason = $audit_snapshot['last_failure_reason'] ?? null;
$last_failure_label = $last_failure_reason ? $last_failure_reason : 'Belum ada kegagalan';
$last_failure_time_label = isset($audit_snapshot['last_failure_at']) && $audit_snapshot['last_failure_at']
    ? date('H:i:s', $audit_snapshot['last_failure_at'])
    : '-';
$last_valid_time_label = isset($audit_snapshot['last_valid_at']) && $audit_snapshot['last_valid_at']
    ? date('H:i:s', $audit_snapshot['last_valid_at'])
    : '-';

$demo_plain_input = '';
$demo_encrypt_token = null;
$demo_encrypt_error = null;
$demo_encrypt_meta = null;
$demo_token_input = '';
$demo_decrypt_output = null;
$demo_decrypt_error = null;
$demo_decrypt_meta = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $demo_mode = $_POST['demo_mode'] ?? '';

    if ($demo_mode === 'encrypt') {
        $demo_plain_input = trim($_POST['demo_plain'] ?? '');

        if ($demo_plain_input === '') {
            $demo_encrypt_error = 'Masukkan teks yang ingin dienkripsi.';
        } else {
            $verbose = encrypt_data_verbose($demo_plain_input, [
                'context' => [
                    'mode' => 'demo-encrypt',
                ],
            ]);

            if ($verbose === false) {
                $demo_encrypt_error = 'Terjadi kesalahan saat mengenkripsi data.';
            } else {
                $demo_encrypt_token = $verbose['token'];
                $demo_encrypt_meta = $verbose;
            }
        }
    } elseif ($demo_mode === 'decrypt') {
        $demo_token_input = trim($_POST['demo_token'] ?? '');

        if ($demo_token_input === '') {
            $demo_decrypt_error = 'Tempel token terenkripsi yang ingin diuji.';
        } else {
            $analysis = analyze_token($demo_token_input);

            if ($analysis['status'] !== 'ok') {
                $demo_decrypt_error = $analysis['error'] ?? 'Token tidak dapat didekripsi. Pastikan format dan kuncinya sesuai.';
            } else {
                $demo_decrypt_output = $analysis['value'];
                $demo_decrypt_meta = $analysis['meta'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Showcase Enkripsi URL</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #141e30, #243b55);
            --accent: #38bdf8;
            --accent-soft: rgba(56, 189, 248, 0.18);
            --glass: rgba(255, 255, 255, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .navbar {
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(20px);
        }

        .navbar-brand {
            padding: 0;
        }

        .navbar-brand .brand-logo {
            height: 48px;
            width: auto;
            display: block;
            filter: drop-shadow(0 6px 18px rgba(30, 64, 175, 0.32));
        }

        @media (max-width: 576px) {
            .navbar-brand .brand-logo {
                height: 40px;
            }
        }

        .hero {
            background: var(--bg-gradient);
            position: relative;
            overflow: hidden;
        }

        .hero::before,
        .hero::after {
            content: "";
            position: absolute;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.6;
            animation: float 12s ease-in-out infinite;
        }

        .hero::before {
            background: rgba(56, 189, 248, 0.5);
            top: -160px;
            right: -80px;
        }

        .hero::after {
            background: rgba(34, 211, 238, 0.4);
            bottom: -120px;
            left: -60px;
            animation-delay: -4s;
        }

        @keyframes float {
            0%,
            100% {
                transform: translateY(0) scale(1);
            }

            50% {
                transform: translateY(-18px) scale(1.05);
            }
        }

        .hero .btn-primary {
            background: var(--accent);
            border: none;
            box-shadow: 0 18px 34px rgba(56, 189, 248, 0.28);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }

        .hero .btn-primary:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 24px 42px rgba(56, 189, 248, 0.42);
        }

        .glass-card {
            background: var(--glass);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 20px;
            transition: transform 0.45s ease, border-color 0.45s ease, box-shadow 0.45s ease;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.45);
        }

        .glass-card:hover {
            transform: translateY(-10px);
            border-color: rgba(56, 189, 248, 0.55);
            box-shadow: 0 32px 60px rgba(15, 23, 42, 0.7);
        }

        .tag {
            background: var(--accent-soft);
            color: var(--accent);
            padding: 0.35rem 0.8rem;
            border-radius: 999px;
            font-size: 0.75rem;
            letter-spacing: 0.08rem;
            text-transform: uppercase;
        }

        .url-card {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.05);
        }

        code {
            color: #f1f5f9;
            font-size: 0.9rem;
        }

        .journey-card {
            background: rgba(15, 23, 42, 0.75);
            border: 1px solid rgba(56, 189, 248, 0.25);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 28px 50px rgba(8, 14, 35, 0.65);
        }

        .journey-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .journey-nav button {
            border: 1px solid rgba(56, 189, 248, 0.3);
            background: rgba(56, 189, 248, 0.1);
            color: #bae6fd;
            border-radius: 999px;
            padding: 0.35rem 0.9rem;
            font-size: 0.8rem;
            letter-spacing: 0.05rem;
            text-transform: uppercase;
        }

        .journey-nav button.active {
            background: rgba(56, 189, 248, 0.3);
            color: #0ea5e9;
            border-color: rgba(125, 211, 252, 0.6);
        }

        .journey-step {
            display: none;
        }

        .journey-step.active {
            display: block;
        }

        .journey-code {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(148, 163, 184, 0.16);
            border-radius: 16px;
            padding: 1rem;
            font-size: 0.8rem;
            color: #e2e8f0;
            max-height: 240px;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .audit-card {
            background: rgba(15, 23, 42, 0.68);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: 0 22px 44px rgba(8, 14, 35, 0.55);
        }

        .audit-metric {
            font-size: 2.4rem;
            font-weight: 700;
            line-height: 1;
        }

        .countdown-badge {
            background: rgba(56, 189, 248, 0.18);
            color: #38bdf8;
            border-radius: 999px;
            padding: 0.4rem 0.9rem;
            font-size: 0.85rem;
            letter-spacing: 0.04rem;
        }

        .token-utils button {
            min-width: 160px;
        }

        .text-accent {
            color: var(--accent);
        }

        .fade-up {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }

        .fade-up.in-view {
            opacity: 1;
            transform: translateY(0);
        }

        footer {
            background: rgba(15, 23, 42, 0.85);
            border-top: 1px solid rgba(148, 163, 184, 0.16);
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark navbar-expand-lg sticky-top py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="assets/cryptobangsa-logo.svg" alt="Logo CryptoBangsa" class="brand-logo">
                <span class="visually-hidden">CryptoBangsa</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto gap-2">
                    <li class="nav-item"><a class="nav-link" href="#pengguna">Daftar Pengguna</a></li>
                    <li class="nav-item"><a class="nav-link" href="#demo-url">Demo URL</a></li>
                    <li class="nav-item"><a class="nav-link" href="#insight">Insight</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="hero text-white py-5">
        <div class="container py-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <span class="tag mb-3 d-inline-block">Modern Encryption Showcase</span>
                    <h1 class="display-5 fw-bold mb-3">Enkripsi URL Interaktif By Kelompok 2</h1>
                    <p class="lead text-light opacity-75">Website demonstrasi ini memanfaatkan AES-256-GCM untuk menyembunyikan parameter sensitif di URL. Nikmati antarmuka responsif, animasi halus, dan data dummy yang dirancang untuk eksplorasi.</p>
                    <div class="d-flex flex-wrap gap-3 mt-4">
                        <a class="btn btn-primary btn-lg px-4" href="#pengguna">
                            <i class="bi bi-shield-lock-fill me-2"></i>Lihat Daftar Terenkripsi
                        </a>
                        <div class="d-flex align-items-center gap-2 text-light opacity-75">
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="glass-card p-4 fade-up">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="bg-dark bg-opacity-50 rounded-circle d-flex align-items-center justify-content-center" style="width:60px;height:60px;">
                                <i class="bi bi-link-45deg fs-2 text-accent"></i>
                            </div>
                            <div>
                                <p class="text-uppercase fw-semibold mb-1 small text-info">Sample URL</p>
                                <h2 class="h4 mb-0">Proteksi Query String</h2>
                            </div>
                        </div>
                        <p class="small text-light opacity-75">Perbandingan langsung antara URL polos dan URL terenkripsi dari salah satu pengguna.</p>
                        <div class="url-card mb-3">
                            <p class="text-uppercase small text-warning mb-1">Tanpa Enkripsi</p>
                            <code><?= htmlspecialchars($plain_url) ?></code>
                        </div>
                        <div class="url-card">
                            <p class="text-uppercase small text-success mb-1">Dengan Enkripsi AES-256-GCM + Base64URL</p>
                            <code><?= htmlspecialchars($encrypted_url) ?></code>
                        </div>
                    </div>
                    <?php if (!empty($journey_steps)): ?>
                        <div class="journey-card mt-4 fade-up" id="tokenJourney">
                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                                <div>
                                    <p class="text-uppercase small text-info mb-1">Token Journey Visualizer</p>
                                    <h3 class="h5 fw-semibold mb-0">Jejak Enkripsi Bertahap</h3>
                                </div>
                                <div class="journey-nav" role="tablist">
                                    <?php foreach ($journey_steps as $idx => $step): ?>
                                        <button type="button" class="btn btn-sm<?= $idx === 0 ? ' active' : '' ?>" data-step="<?= htmlspecialchars($step['key']) ?>">
                                            <?= htmlspecialchars($step['label']) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php foreach ($journey_steps as $idx => $step): ?>
                                <div class="journey-step<?= $idx === 0 ? ' active' : '' ?>" data-step-target="<?= htmlspecialchars($step['key']) ?>">
                                    <h4 class="h6 text-info fw-semibold mb-1"><?= htmlspecialchars($step['title']) ?></h4>
                                    <p class="small text-light opacity-75 mb-3"><?= htmlspecialchars($step['description']) ?></p>
                                    <pre class="journey-code" data-language="<?= htmlspecialchars($step['language']) ?>"><?= htmlspecialchars($step['content']) ?></pre>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section id="pengguna" class="py-5 position-relative">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <span class="tag">Featured Team</span>
                        <h2 class="h1 fw-bold mt-2">Daftar Pengguna Terenkripsi</h2>
                        <p class="text-light opacity-75 mb-0">Klik kartu untuk membuka detail menggunakan parameter URL yang telah dienkripsi.</p>
                    </div>
                    <div class="d-none d-lg-flex flex-column text-end text-light opacity-75">
                        <span><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Realtime AES-256 Encryption</span>
                    </div>
                </div>
                <div class="row g-4">
                    <?php foreach ($users as $user): ?>
                        <?php
                        $token = encrypt_data($user['id'], [
                            'aad_alias' => 'detail',
                            'context' => [
                                'route' => 'detail.php',
                                'user_id' => $user['id'],
                            ],
                        ]);
                        $encrypted_href = $token !== false ? 'detail.php?id=' . $token : '#';
                        ?>
                        <div class="col-12 col-md-6 col-xl-3 fade-up">
                            <a href="<?= $encrypted_href ?>" class="text-decoration-none text-reset">
                                <article class="glass-card h-100 p-4">
                                    <div class="mb-3">
                                        <span class="badge text-bg-dark bg-opacity-75 border border-light border-opacity-10">
                                            <i class="bi bi-hash me-1"></i>ID terenkripsi
                                        </span>
                                    </div>
                                    <h3 class="h4 fw-semibold mb-1"><?= htmlspecialchars($user['nama']) ?></h3>
                                    <p class="mb-2 text-info small"><?= htmlspecialchars($user['role']) ?></p>
                                    <p class="mb-1 text-light opacity-75 small"><i class="bi bi-geo-alt me-2"></i><?= htmlspecialchars($user['location']) ?></p>
                                    <p class="text-white-50 small mb-3"><?= htmlspecialchars($user['summary']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center text-decoration-underline text-info small">
                                        <span><?= htmlspecialchars($user['email']) ?></span>
                                        <i class="bi bi-arrow-right-circle-fill"></i>
                                    </div>
                                </article>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section id="audit" class="py-5">
            <div class="container fade-up">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                    <div>
                        <span class="tag">Security Ops</span>
                        <h2 class="h3 fw-bold mt-2 mb-0">Audit Dashboard Panel</h2>
                        <p class="text-light opacity-75 small mb-0">Statistik singkat hasil validasi token dari halaman detail.</p>
                    </div>
                    <div class="text-light opacity-75 small">
                        <i class="bi bi-activity text-info me-2"></i>Pembaruan dihitung per sesi demo ini.
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-12 col-lg-4">
                        <div class="audit-card h-100 border-success border-opacity-25">
                            <p class="small text-success text-uppercase mb-2">Token Valid</p>
                            <p class="audit-metric text-success mb-2"><?= htmlspecialchars((string) $audit_snapshot['valid']) ?></p>
                            <p class="text-light opacity-75 small mb-0">Jumlah token yang berhasil diverifikasi dan menampilkan data asli.</p>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="audit-card h-100 border-danger border-opacity-25">
                            <p class="small text-danger text-uppercase mb-2">Token Gagal</p>
                            <p class="audit-metric text-danger mb-2"><?= htmlspecialchars((string) $audit_snapshot['invalid']) ?></p>
                            <p class="text-light opacity-75 small mb-1">Alasan terakhir:</p>
                            <p class="text-light small mb-0"><?= htmlspecialchars($last_failure_label) ?></p>
                            <p class="text-light opacity-75 small mb-0">Waktu: <?= htmlspecialchars($last_failure_time_label) ?></p>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="audit-card h-100 border-info border-opacity-25">
                            <p class="small text-info text-uppercase mb-2">Aktivitas Terakhir</p>
                            <ul class="list-unstyled text-light opacity-75 small mb-0">
                                <li class="mb-1"><i class="bi bi-clock-history me-2 text-info"></i>Valid terakhir: <?= htmlspecialchars($last_valid_time_label) ?></li>
                                <li><i class="bi bi-bug me-2 text-warning"></i>Gagal terakhir: <?= htmlspecialchars($last_failure_time_label) ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="demo-url" class="py-5">
            <div class="container">
                <div class="row align-items-center g-4">
                    <div class="col-lg-6 fade-up">
                        <h2 class="fw-bold mb-3">Kenapa Perlu Enkripsi URL?</h2>
                        <p class="text-light opacity-75">Parameter query kerap membawa informasi sensitif seperti ID pengguna, token transaksi, atau data personal lain. Dengan mengenkripsi nilai tersebut, kita menambah lapisan keamanan dan membuat URL lebih aman untuk dibagikan.</p>
                        <ul class="list-unstyled text-light opacity-75">
                            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Menjauhkan data ID asli dari mata publik.</li>
                            <li class="mb-2"><i class="bi bi-lightbulb-fill text-warning me-2"></i>Mudah diintegrasikan dengan sistem yang sudah ada.</li>
                            <li><i class="bi bi-phone-fill text-info me-2"></i>Sepenuhnya responsif di berbagai perangkat.</li>
                        </ul>
                    </div>
                    <div class="col-lg-6 fade-up">
                        <div class="glass-card p-4">
                            <h3 class="h5 fw-semibold mb-3">Langkah Singkat</h3>
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex gap-3">
                                    <span class="badge rounded-pill bg-primary">1</span>
                                    <div>
                                        <h4 class="h6 fw-semibold mb-1">Encrypt</h4>
                                        <p class="small text-light opacity-75 mb-0">Nilai ID diolah menggunakan `openssl_encrypt` dengan algoritma AES-256-GCM, menghasilkan token yang terautentikasi.</p>
                                    </div>
                                </div>
                                <div class="d-flex gap-3">
                                    <span class="badge rounded-pill bg-primary">2</span>
                                    <div>
                                        <h4 class="h6 fw-semibold mb-1">Encode</h4>
                                        <p class="small text-light opacity-75 mb-0">Token kemudian diubah ke format Base64URL agar ramah terhadap URL tanpa perlu encoding tambahan.</p>
                                    </div>
                                </div>
                                <div class="d-flex gap-3">
                                    <span class="badge rounded-pill bg-primary">3</span>
                                    <div>
                                        <h4 class="h6 fw-semibold mb-1">Share</h4>
                                        <p class="small text-light opacity-75 mb-0">Query terenkripsi siap dipakai tanpa mengorbankan pengalaman pengguna.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="demo-live" class="py-5">
            <div class="container">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
                    <div>
                        <span class="tag">Live Token Lab</span>
                        <h2 class="h1 fw-bold mt-2">Coba Enkripsi &amp; Dekripsi Sendiri</h2>
                        <p class="text-light opacity-75 mb-0">Masukkan teks biasa untuk melihat token terenkripsi atau tempel token untuk diuji balik ke data asli.</p>
                    </div>
                    <div class="text-light opacity-75 small">
                        <i class="bi bi-lightbulb-fill text-warning me-2"></i>Token memakai format Base64URL dengan pemisah titik.
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-12 col-lg-6 fade-up">
                        <div class="glass-card h-100 p-4">
                            <h3 class="h4 fw-semibold mb-3">Demo Enkripsi</h3>
                            <p class="text-light opacity-75 small mb-4">AES-256-GCM menghasilkan ciphertext sekaligus authentication tag sehingga token tidak bisa dimodifikasi diam-diam.</p>
                            <form method="post" class="d-flex flex-column gap-3" autocomplete="off">
                                <input type="hidden" name="demo_mode" value="encrypt">
                                <div>
                                    <label for="demo_plain" class="form-label text-light small">Teks yang akan dienkripsi</label>
                                    <textarea class="form-control bg-transparent text-light border-secondary" id="demo_plain" name="demo_plain" rows="4" placeholder="cth: ID pengguna 15"><?php echo htmlspecialchars($demo_plain_input); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary align-self-start">
                                    <i class="bi bi-lock-fill me-2"></i>Enkripsi Sekarang
                                </button>
                            </form>
                            <?php if ($demo_encrypt_error): ?>
                                <div class="alert alert-warning mt-3 mb-0">
                                    <?= htmlspecialchars($demo_encrypt_error) ?>
                                </div>
                            <?php elseif ($demo_encrypt_token !== null): ?>
                                <div class="mt-3">
                                    <label class="form-label text-light small">Token Hasil Enkripsi</label>
                                    <textarea class="form-control bg-transparent text-accent border-secondary" rows="3" readonly><?= htmlspecialchars($demo_encrypt_token) ?></textarea>
                                    <?php if ($demo_encrypt_meta !== null): ?>
                                        <?php
                                        $demo_expires_at = $demo_encrypt_meta['payload']['expires_at'] ?? null;
                                        $demo_ttl = $demo_expires_at !== null ? max(0, $demo_expires_at - time()) : null;
                                        ?>
                                        <p class="text-light opacity-75 small mt-2 mb-0">
                                            <i class="bi bi-hourglass-split me-2 text-info"></i>Kedaluwarsa dalam <?= htmlspecialchars($demo_ttl !== null ? $demo_ttl . ' detik' : '—') ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6 fade-up">
                        <div class="glass-card h-100 p-4">
                            <h3 class="h4 fw-semibold mb-3">Demo Dekripsi</h3>
                            <p class="text-light opacity-75 small mb-4">Tempel token Base64URL untuk melihat apakah sistem dapat memverifikasi dan mengembalikan teks aslinya.</p>
                            <form method="post" class="d-flex flex-column gap-3" autocomplete="off">
                                <input type="hidden" name="demo_mode" value="decrypt">
                                <div>
                                    <label for="demo_token" class="form-label text-light small">Token terenkripsi</label>
                                    <textarea class="form-control bg-transparent text-light border-secondary" id="demo_token" name="demo_token" rows="4" placeholder="cth: <?= htmlspecialchars($encrypted_url !== 'detail.php' ? substr($encrypted_url, strlen('detail.php?id=')) : '') ?>"><?php echo htmlspecialchars($demo_token_input); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-outline-info align-self-start">
                                    <i class="bi bi-unlock-fill me-2"></i>Dekripsi Token
                                </button>
                            </form>
                            <?php if ($demo_decrypt_error): ?>
                                <div class="alert alert-danger mt-3 mb-0">
                                    <?= htmlspecialchars($demo_decrypt_error) ?>
                                </div>
                            <?php elseif ($demo_decrypt_output !== null): ?>
                                <div class="mt-3">
                                    <label class="form-label text-light small">Hasil Dekripsi</label>
                                    <textarea class="form-control bg-transparent text-accent border-secondary" rows="3" readonly><?= htmlspecialchars($demo_decrypt_output) ?></textarea>
                                    <?php if ($demo_decrypt_meta !== null): ?>
                                        <ul class="list-unstyled text-light opacity-75 small mb-0 mt-2">
                                            <li><i class="bi bi-shield-lock me-2 text-success"></i>Alias AAD: <?= htmlspecialchars($demo_decrypt_meta['alias'] ?? '—') ?></li>
                                            <li><i class="bi bi-stopwatch me-2 text-warning"></i>Sisa waktu: <?= htmlspecialchars(isset($demo_decrypt_meta['remaining_seconds']) && $demo_decrypt_meta['remaining_seconds'] !== null ? $demo_decrypt_meta['remaining_seconds'] . ' detik' : '—') ?></li>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="insight" class="py-5">
            <div class="container fade-up">
                <div class="glass-card p-4 p-lg-5">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-7">
                            <h2 class="fw-bold mb-3">Insight Tambahan</h2>
                            <p class="text-light opacity-75">Selain enkripsi, selalu pertimbangkan validasi server-side, pembatasan akses, dan logging aman. Kombinasi praktik baik akan menjaga platform tetap tangguh terhadap serangan.</p>
                        </div>
                        <div class="col-lg-5">
                            <div class="d-flex flex-column gap-3 text-light opacity-75">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-shield-shaded fs-3 text-success"></i>
                                    <div>
                                        <h4 class="h6 fw-semibold mb-1">Data Validation</h4>
                                        <p class="small mb-0">Selalu validasi token terenkripsi sebelum diproses lebih lanjut.</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-diagram-3 fs-3 text-warning"></i>
                                    <div>
                                        <h4 class="h6 fw-semibold mb-1">Key Rotation</h4>
                                        <p class="small mb-0">Atur strategi rotasi kunci agar keamanan tetap maksimal.</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi bi-cloud-check fs-3 text-info"></i>
                                    <div>
                                        <h4 class="h6 fw-semibold mb-1">Secure Delivery</h4>
                                        <p class="small mb-0">Pastikan transport layer (HTTPS) juga terlindungi.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="py-4 mt-auto">
        <div class="container d-flex flex-column flex-xl-row justify-content-between align-items-center gap-2 text-light opacity-75 small">
            <span>&copy; <?= date('Y') ?> CryptoBangsa Showcase</span>
            <span><i class="bi bi-code-slash me-1"></i>Dibuat dengan PHP, Bootstrap 5, dan AES-256-GCM</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                }
            });
        }, { threshold: 0.15 });

        document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));

        const journeyEl = document.getElementById('tokenJourney');
        if (journeyEl) {
            const buttons = journeyEl.querySelectorAll('.journey-nav button');
            const steps = journeyEl.querySelectorAll('.journey-step');

            const setActiveStep = (key) => {
                steps.forEach(step => {
                    step.classList.toggle('active', step.dataset.stepTarget === key);
                });
                buttons.forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.step === key);
                });
            };

            buttons.forEach(btn => {
                btn.addEventListener('click', () => setActiveStep(btn.dataset.step));
            });
        }
    </script>
</body>

</html>
