<?php
require_once 'encryption.php';

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

if ($token) {
    $decrypted_id = decrypt_data($token);

    if ($decrypted_id !== false) {
        foreach ($users as $user) {
            if ((string) $user['id'] === (string) $decrypted_id) {
                $user_found = $user;
                break;
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
                        <p class="text-uppercase text-info fw-semibold mb-2">Token Enkripsi</p>
                        <p class="mb-0"><?= htmlspecialchars($token) ?></p>
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
                <p class="text-light opacity-75 mb-4">Kami tidak dapat menemukan data untuk token ini. Pastikan URL yang Anda gunakan adalah hasil enkripsi dari sistem kami.</p>
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
</body>

</html>
