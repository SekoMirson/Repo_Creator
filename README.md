# 🐙 GitHub Repo Creator — PHP + OAuth

Tek tıkla GitHub repository oluşturan PHP uygulaması.

## 📁 Dosyalar

```
github_repo_creator/
├── index.php         ← Ana sayfa (form + giriş)
├── callback.php      ← GitHub OAuth callback
├── create_repo.php   ← API endpoint (repo oluştur)
├── logout.php        ← Oturum kapat
└── README.md
```

---

## ⚙️ Kurulum

### 1. GitHub OAuth App Oluştur

1. GitHub → Settings → Developer settings → OAuth Apps → **New OAuth App**
2. Doldur:
   - **Application name:** `Repo Creator`
   - **Homepage URL:** `http://localhost:8000`
   - **Authorization callback URL:** `http://localhost:8000/callback.php`
3. **Client ID** ve **Client Secret** al

### 2. Kimlik Bilgilerini Ayarla

**Yöntem A — Environment Variables (Önerilen):**
```bash
export GITHUB_CLIENT_ID="your_client_id"
export GITHUB_CLIENT_SECRET="your_client_secret"
```

**Yöntem B — index.php ve callback.php içinde doğrudan:**
```php
define('GITHUB_CLIENT_ID', 'your_client_id');
define('GITHUB_CLIENT_SECRET', 'your_client_secret');
```

### 3. Sunucuyu Başlat

```bash
# PHP Built-in Server
cd github_repo_creator
php -S localhost:8000

# Apache/Nginx: klasörü web root'a koy
```

### 4. Tarayıcıda Aç

```
http://localhost:8000
```

---

## 🚀 Kullanım

1. **GitHub ile Giriş Yap** butonuna tıkla
2. GitHub izinlerini onayla
3. Formu doldur:
   - **Repository adı** (zorunlu)
   - **Açıklama** (isteğe bağlı)
   - **Public / Private** seç
   - **README.md** otomatik oluştur (isteğe bağlı)
4. **Repository Oluştur** butonuna tıkla
5. Clone URL'yi kopyala, repoya git!

---

## 🔒 Güvenlik

- OAuth 2.0 state parametresi ile CSRF koruması
- Token session'da saklanır (production'da encrypted session önerilir)
- Repo adı input validation ile temizlenir

---

## 📋 Gereksinimler

- PHP 7.4+
- `allow_url_fopen = On` (php.ini)
- HTTPS (production ortamı için zorunlu)
