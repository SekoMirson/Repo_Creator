<?php
session_start();

define('GITHUB_CLIENT_ID', getenv('GITHUB_CLIENT_ID') ?: 'Ov23liHJsbePkqud6aGn');

$isLoggedIn = isset($_SESSION['github_token']) && isset($_SESSION['github_user']);
$user       = $_SESSION['github_user'] ?? null;
$authError  = $_SESSION['auth_error'] ?? null;
unset($_SESSION['auth_error']);

// Generate CSRF state
if (!isset($_SESSION['oauth_state'])) {
    $_SESSION['oauth_state'] = bin2hex(random_bytes(16));
}
$oauthState = $_SESSION['oauth_state'];
$oauthUrl   = "https://github.com/login/oauth/authorize?client_id=" . urlencode(GITHUB_CLIENT_ID) . "&scope=repo&state=" . urlencode($oauthState);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GitHub Repo Creator</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --gh-green: #2ea043;
    --gh-green-hover: #3fb950;
    --gh-dark: #0d1117;
    --gh-surface: #161b22;
    --gh-border: #30363d;
    --gh-text: #e6edf3;
    --gh-muted: #7d8590;
    --gh-accent: #58a6ff;
    --gh-danger: #f85149;
  }

  * { box-sizing: border-box; }

  body {
    background: var(--gh-dark);
    color: var(--gh-text);
    font-family: 'Sora', sans-serif;
    min-height: 100vh;
    background-image:
      radial-gradient(ellipse 80% 50% at 50% -20%, rgba(46,160,67,0.15), transparent),
      radial-gradient(ellipse 60% 40% at 80% 100%, rgba(88,166,255,0.08), transparent);
  }

  .navbar-brand {
    font-weight: 800;
    font-size: 1.4rem;
    letter-spacing: -0.5px;
    color: var(--gh-text) !important;
  }

  .navbar {
    background: rgba(22,27,34,0.9) !important;
    backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--gh-border);
    padding: 0.8rem 1rem;
  }

  .hero-section {
    text-align: center;
    padding: 5rem 1rem 3rem;
  }

  .hero-badge {
    display: inline-block;
    background: rgba(46,160,67,0.15);
    border: 1px solid rgba(46,160,67,0.4);
    color: var(--gh-green-hover);
    padding: 0.3rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 1.5rem;
  }

  .hero-title {
    font-size: clamp(2.2rem, 5vw, 3.5rem);
    font-weight: 800;
    letter-spacing: -1.5px;
    line-height: 1.1;
    margin-bottom: 1rem;
    background: linear-gradient(135deg, #e6edf3 0%, #58a6ff 50%, #2ea043 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .hero-sub {
    color: var(--gh-muted);
    font-size: 1.1rem;
    max-width: 480px;
    margin: 0 auto 2.5rem;
    line-height: 1.7;
  }

  .card-main {
    background: var(--gh-surface);
    border: 1px solid var(--gh-border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 40px rgba(0,0,0,0.4);
  }

  .card-header-custom {
    background: rgba(22,27,34,0.6);
    border-bottom: 1px solid var(--gh-border);
    padding: 1.2rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
  }

  .card-header-custom .dot {
    width: 12px; height: 12px;
    border-radius: 50%;
  }

  .card-body-custom { padding: 2rem; }

  .form-label {
    color: var(--gh-text);
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
  }

  .form-control, .form-select {
    background: var(--gh-dark) !important;
    border: 1px solid var(--gh-border) !important;
    color: var(--gh-text) !important;
    border-radius: 8px !important;
    padding: 0.65rem 1rem;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.9rem;
    transition: all 0.2s;
  }

  .form-control:focus, .form-select:focus {
    border-color: var(--gh-accent) !important;
    box-shadow: 0 0 0 3px rgba(88,166,255,0.15) !important;
    outline: none !important;
  }

  .form-control::placeholder { color: var(--gh-muted) !important; }

  .form-text { color: var(--gh-muted); font-size: 0.78rem; margin-top: 0.35rem; }

  .visibility-card {
    background: var(--gh-dark);
    border: 1.5px solid var(--gh-border);
    border-radius: 10px;
    padding: 1rem 1.2rem;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.8rem;
  }

  .visibility-card:hover { border-color: var(--gh-accent); background: rgba(88,166,255,0.05); }
  .visibility-card.selected { border-color: var(--gh-green); background: rgba(46,160,67,0.08); }
  .visibility-card input[type="radio"] { display: none; }
  .visibility-card .vis-icon { font-size: 1.3rem; }
  .visibility-card .vis-title { font-weight: 700; font-size: 0.95rem; }
  .visibility-card .vis-desc { color: var(--gh-muted); font-size: 0.78rem; }

  .form-check-input:checked {
    background-color: var(--gh-green) !important;
    border-color: var(--gh-green) !important;
  }

  .form-check-label { color: var(--gh-text); font-size: 0.9rem; }

  .btn-github-login {
    background: #fff;
    color: #24292f;
    border: none;
    padding: 0.8rem 2rem;
    border-radius: 10px;
    font-weight: 700;
    font-size: 1rem;
    font-family: 'Sora', sans-serif;
    display: inline-flex;
    align-items: center;
    gap: 0.7rem;
    transition: all 0.2s;
    text-decoration: none;
  }

  .btn-github-login:hover {
    background: #f0f0f0;
    color: #24292f;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
  }

  .btn-create {
    background: linear-gradient(135deg, #2ea043, #3fb950);
    border: none;
    color: #fff;
    padding: 0.85rem 2rem;
    border-radius: 10px;
    font-weight: 700;
    font-size: 1rem;
    font-family: 'Sora', sans-serif;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    transition: all 0.2s;
    cursor: pointer;
    margin-top: 0.5rem;
  }

  .btn-create:hover:not(:disabled) {
    background: linear-gradient(135deg, #3fb950, #56d364);
    transform: translateY(-1px);
    box-shadow: 0 8px 25px rgba(46,160,67,0.3);
  }

  .btn-create:disabled { opacity: 0.5; cursor: not-allowed; }

  .user-chip {
    background: var(--gh-surface);
    border: 1px solid var(--gh-border);
    border-radius: 50px;
    padding: 0.4rem 1rem 0.4rem 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
  }

  .user-chip img {
    width: 30px; height: 30px;
    border-radius: 50%;
    border: 2px solid var(--gh-green);
  }

  .user-chip span { font-weight: 600; font-size: 0.9rem; color: var(--gh-text); }

  .btn-logout {
    background: transparent;
    border: 1px solid var(--gh-border);
    color: var(--gh-muted);
    border-radius: 8px;
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
    font-family: 'Sora', sans-serif;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
  }

  .btn-logout:hover { border-color: var(--gh-danger); color: var(--gh-danger); }

  /* Result Panel */
  #result-panel {
    display: none;
    animation: slideIn 0.4s ease;
  }

  @keyframes slideIn {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .result-success {
    background: rgba(46,160,67,0.1);
    border: 1px solid rgba(46,160,67,0.4);
    border-radius: 12px;
    padding: 1.5rem;
  }

  .result-error {
    background: rgba(248,81,73,0.1);
    border: 1px solid rgba(248,81,73,0.4);
    border-radius: 12px;
    padding: 1.5rem;
  }

  .result-title { font-weight: 800; font-size: 1.1rem; margin-bottom: 0.8rem; }
  .result-success .result-title { color: var(--gh-green-hover); }
  .result-error .result-title { color: var(--gh-danger); }

  .repo-info-row {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    font-size: 0.88rem;
  }

  .repo-info-row:last-child { border-bottom: none; }
  .repo-info-row .label { color: var(--gh-muted); min-width: 80px; font-size: 0.8rem; }
  .repo-info-row .val { font-family: 'JetBrains Mono', monospace; word-break: break-all; }
  .repo-info-row a { color: var(--gh-accent); text-decoration: none; }
  .repo-info-row a:hover { text-decoration: underline; }

  .copy-btn {
    background: none;
    border: 1px solid var(--gh-border);
    color: var(--gh-muted);
    border-radius: 5px;
    padding: 0.15rem 0.5rem;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.2s;
    margin-left: auto;
    flex-shrink: 0;
    font-family: 'Sora', sans-serif;
  }

  .copy-btn:hover { border-color: var(--gh-accent); color: var(--gh-accent); }
  .copy-btn.copied { border-color: var(--gh-green); color: var(--gh-green); }

  .spinner-border-sm { width: 1rem; height: 1rem; }

  .login-prompt {
    text-align: center;
    padding: 3rem 2rem;
  }

  .login-icon { font-size: 4rem; margin-bottom: 1.2rem; }
  .login-title { font-size: 1.5rem; font-weight: 800; margin-bottom: 0.6rem; }
  .login-sub { color: var(--gh-muted); margin-bottom: 2rem; }

  .features-list {
    display: flex;
    gap: 0.6rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 2rem;
  }

  .feature-pill {
    background: rgba(88,166,255,0.1);
    border: 1px solid rgba(88,166,255,0.25);
    color: var(--gh-accent);
    border-radius: 20px;
    padding: 0.3rem 0.9rem;
    font-size: 0.8rem;
    font-weight: 600;
  }

  .separator {
    border: none;
    border-top: 1px solid var(--gh-border);
    margin: 1.5rem 0;
  }

  /* Footer */
  footer {
    text-align: center;
    padding: 2rem;
    color: var(--gh-muted);
    font-size: 0.8rem;
    border-top: 1px solid var(--gh-border);
    margin-top: 4rem;
  }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <i class="bi bi-github me-2"></i>Repo Creator
    </a>
    <div class="ms-auto d-flex align-items-center gap-2">
      <?php if ($isLoggedIn && $user): ?>
        <div class="user-chip">
          <img src="<?= htmlspecialchars($user['avatar_url'] ?? '') ?>" alt="avatar">
          <span><?= htmlspecialchars($user['login'] ?? '') ?></span>
        </div>
        <a href="logout.php" class="btn-logout"><i class="bi bi-box-arrow-right"></i> Çıkış</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Hero -->
<div class="hero-section">
  <div class="hero-badge"><i class="bi bi-lightning-fill me-1"></i> Otomatik Repo Oluşturucu</div>
  <h1 class="hero-title">GitHub Repository<br>Saniyeler İçinde</h1>
  <p class="hero-sub">OAuth ile güvenli giriş yap, repo bilgilerini doldur, tek tıkla oluştur.</p>
</div>

<!-- Main Card -->
<div class="container" style="max-width: 620px; padding-bottom: 3rem;">

  <?php if ($authError): ?>
  <div class="alert" style="background:rgba(248,81,73,0.1);border:1px solid rgba(248,81,73,0.4);color:#f85149;border-radius:10px;margin-bottom:1.5rem;">
    <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($authError) ?>
  </div>
  <?php endif; ?>

  <div class="card-main">
    <div class="card-header-custom">
      <div class="dot" style="background:#ff5f57"></div>
      <div class="dot" style="background:#febc2e"></div>
      <div class="dot" style="background:#28c840"></div>
      <span style="margin-left:0.5rem;font-size:0.85rem;color:var(--gh-muted);font-family:'JetBrains Mono',monospace;">
        <?= $isLoggedIn ? 'github.com/' . htmlspecialchars($user['login'] ?? '') . '/new-repo' : 'github.com/login/oauth' ?>
      </span>
    </div>

    <?php if (!$isLoggedIn): ?>
    <!-- LOGIN PROMPT -->
    <div class="login-prompt">
      <div class="login-icon">🐙</div>
      <div class="login-title">GitHub ile Bağlan</div>
      <p class="login-sub">Repository oluşturmak için GitHub hesabınla giriş yapman gerekiyor.</p>
      <div class="features-list">
        <span class="feature-pill"><i class="bi bi-lock me-1"></i> OAuth Güvenli</span>
        <span class="feature-pill"><i class="bi bi-eye me-1"></i> Public / Private</span>
        <span class="feature-pill"><i class="bi bi-file-text me-1"></i> README Oluştur</span>
      </div>
      <a href="<?= htmlspecialchars($oauthUrl) ?>" class="btn-github-login">
        <i class="bi bi-github" style="font-size:1.3rem"></i>
        GitHub ile Giriş Yap
      </a>
    </div>

    <?php else: ?>
    <!-- REPO FORM -->
    <div class="card-body-custom">

      <div class="mb-4">
        <label class="form-label"><i class="bi bi-box me-1" style="color:var(--gh-accent)"></i> Repository Adı <span style="color:var(--gh-danger)">*</span></label>
        <input type="text" id="repo-name" class="form-control" placeholder="my-awesome-project" autocomplete="off" spellcheck="false">
        <div class="form-text">Küçük harf, rakam, tire ve alt çizgi kullanabilirsin.</div>
      </div>

      <div class="mb-4">
        <label class="form-label"><i class="bi bi-card-text me-1" style="color:var(--gh-accent)"></i> Açıklama</label>
        <textarea id="repo-desc" class="form-control" rows="2" placeholder="Bu repository ne için? (isteğe bağlı)" style="resize:vertical"></textarea>
      </div>

      <div class="mb-4">
        <label class="form-label d-block mb-2"><i class="bi bi-shield me-1" style="color:var(--gh-accent)"></i> Görünürlük</label>
        <div class="row g-2">
          <div class="col-6">
            <label class="visibility-card selected" id="card-public">
              <input type="radio" name="visibility" value="public" checked>
              <span class="vis-icon">🌍</span>
              <div>
                <div class="vis-title">Public</div>
                <div class="vis-desc">Herkes görebilir</div>
              </div>
            </label>
          </div>
          <div class="col-6">
            <label class="visibility-card" id="card-private">
              <input type="radio" name="visibility" value="private">
              <span class="vis-icon">🔒</span>
              <div>
                <div class="vis-title">Private</div>
                <div class="vis-desc">Sadece sen görürsün</div>
              </div>
            </label>
          </div>
        </div>
      </div>

      <hr class="separator">

      <div class="mb-4">
        <div class="form-check d-flex align-items-center gap-2" style="padding-left:0">
          <input class="form-check-input m-0" type="checkbox" id="auto-init" style="width:1.1rem;height:1.1rem;cursor:pointer;flex-shrink:0">
          <label class="form-check-label" for="auto-init" style="cursor:pointer">
            <i class="bi bi-file-earmark-text me-1" style="color:var(--gh-green)"></i>
            <strong>README.md</strong> otomatik oluştur
            <div style="font-size:0.78rem;color:var(--gh-muted);margin-top:0.1rem">İlk commit olarak README dosyası eklenir</div>
          </label>
        </div>
      </div>

      <button class="btn-create" id="btn-create" onclick="createRepo()">
        <i class="bi bi-plus-circle-fill"></i>
        Repository Oluştur
      </button>

    </div>

    <!-- RESULT PANEL -->
    <div id="result-panel" style="padding:0 2rem 2rem;">
    </div>

    <?php endif; ?>
  </div>
</div>

<footer>
  <i class="bi bi-github me-1"></i> GitHub Repo Creator &mdash; PHP + Bootstrap &mdash; OAuth 2.0
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Visibility card toggle
  document.querySelectorAll('.visibility-card').forEach(card => {
    card.addEventListener('click', () => {
      document.querySelectorAll('.visibility-card').forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
    });
  });

  async function createRepo() {
    const btn = document.getElementById('btn-create');
    const nameEl = document.getElementById('repo-name');
    const resultPanel = document.getElementById('result-panel');

    const name = nameEl.value.trim();
    const description = document.getElementById('repo-desc').value.trim();
    const isPrivate = document.querySelector('input[name="visibility"]:checked').value === 'private';
    const autoInit = document.getElementById('auto-init').checked;

    // Validate
    if (!name) {
      nameEl.style.borderColor = 'var(--gh-danger)';
      nameEl.focus();
      setTimeout(() => nameEl.style.borderColor = '', 2000);
      return;
    }

    if (!/^[a-zA-Z0-9_.\-]+$/.test(name)) {
      showError('Repository adı sadece harf, rakam, tire, alt çizgi ve nokta içerebilir.');
      return;
    }

    // Loading state
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span> Oluşturuluyor...`;
    resultPanel.style.display = 'none';

    try {
      const res = await fetch('create_repo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, description, private: isPrivate, auto_init: autoInit })
      });

      const data = await res.json();

      if (data.success) {
        showSuccess(data.repo);
      } else {
        showError(data.error || 'Bilinmeyen bir hata oluştu.');
      }
    } catch (err) {
      showError('Sunucuya bağlanılamadı. Lütfen tekrar dene.');
    } finally {
      btn.disabled = false;
      btn.innerHTML = `<i class="bi bi-plus-circle-fill"></i> Repository Oluştur`;
    }
  }

  function showSuccess(repo) {
    const panel = document.getElementById('result-panel');
    panel.style.display = 'block';
    panel.innerHTML = `
      <div class="result-success">
        <div class="result-title">✅ Repository Başarıyla Oluşturuldu!</div>
        <div class="repo-info-row">
          <span class="label">İsim</span>
          <span class="val">${escHtml(repo.full_name)}</span>
        </div>
        ${repo.description ? `<div class="repo-info-row"><span class="label">Açıklama</span><span class="val">${escHtml(repo.description)}</span></div>` : ''}
        <div class="repo-info-row">
          <span class="label">Görünür</span>
          <span class="val">${repo.private ? '🔒 Private' : '🌍 Public'}</span>
        </div>
        <div class="repo-info-row">
          <span class="label">URL</span>
          <a class="val" href="${escHtml(repo.html_url)}" target="_blank">${escHtml(repo.html_url)}</a>
        </div>
        <div class="repo-info-row">
          <span class="label">Clone</span>
          <span class="val" style="font-size:0.82rem;">${escHtml(repo.clone_url)}</span>
          <button class="copy-btn" onclick="copyText('${escHtml(repo.clone_url)}', this)"><i class="bi bi-clipboard"></i> Kopyala</button>
        </div>
        <div class="repo-info-row">
          <span class="label">SSH</span>
          <span class="val" style="font-size:0.82rem;">${escHtml(repo.ssh_url)}</span>
          <button class="copy-btn" onclick="copyText('${escHtml(repo.ssh_url)}', this)"><i class="bi bi-clipboard"></i> Kopyala</button>
        </div>
        <div style="margin-top:1.2rem;">
          <a href="${escHtml(repo.html_url)}" target="_blank" class="btn-create" style="text-decoration:none;display:inline-flex;width:auto;padding:0.6rem 1.4rem;">
            <i class="bi bi-box-arrow-up-right"></i> Repoya Git
          </a>
        </div>
      </div>`;
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function showError(msg) {
    const panel = document.getElementById('result-panel');
    panel.style.display = 'block';
    panel.innerHTML = `
      <div class="result-error">
        <div class="result-title">❌ Hata Oluştu</div>
        <div style="font-size:0.9rem;color:var(--gh-muted)">${escHtml(msg)}</div>
      </div>`;
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
      btn.innerHTML = '<i class="bi bi-check2"></i> Kopyalandı';
      btn.classList.add('copied');
      setTimeout(() => {
        btn.innerHTML = '<i class="bi bi-clipboard"></i> Kopyala';
        btn.classList.remove('copied');
      }, 2000);
    });
  }

  function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str || ''));
    return d.innerHTML;
  }

  // Enter key on name field
  document.getElementById && document.getElementById('repo-name')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') createRepo();
  });
</script>
</body>
</html>
