# Deploying DayareMeat on cPanel

If the app works on **localhost** but not on **cPanel**, check the following.

---

## 1. Document root must point to `public`

Laravel must be served from the **`public`** folder, not the project root.

- In cPanel: **Domains** → your domain → **Document Root** (or **Root Directory**).
- Set it to the folder that **contains** `index.php` and `.htaccess` from Laravel’s `public` folder.

**Correct:**  
`/home/youruser/dayaremeat/public`  
(or wherever your project’s `public` folder is)

**Wrong:**  
`/home/youruser/public_html` if that is the project root (no `public`).

**Option A – Recommended:**  
Put the full project (app, bootstrap, config, public, etc.) outside `public_html`, e.g. in `dayaremeat/`, and set document root to `dayaremeat/public`.

**Option B:**  
Point document root to `public_html` and put **only** the contents of Laravel’s `public` folder there (index.php, .htaccess, etc.), then edit `public/index.php`: change `__DIR__.'/../` to the path where your `bootstrap` and `vendor` folders live (e.g. `__DIR__.'/../dayaremeat/'`). This is more error‑prone; Option A is simpler.

---

## 2. `.env` on the server

- Copy `.env.example` to `.env` on the server (or create `.env` with the same keys).
- Set at least:

```env
APP_NAME=dayare
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dayare.sandbox.rw
APP_KEY=base64:xxxx...
```

- **APP_URL** must be the **exact** URL you use in the browser (e.g. `https://dayare.sandbox.rw`). Wrong APP_URL causes wrong links and 404s.
- Generate a key on the server:  
  `php artisan key:generate`
- Set **DB_** variables to your cPanel MySQL database (host often `localhost`).

---

## 3. PHP version

- Laravel 11 needs **PHP 8.2+**.
- In cPanel: **Select PHP Version** (or **MultiPHP Manager**) and choose **8.2** or **8.3** for this domain.

---

## 4. Writable folders

From the **project root** (where `artisan` is), run once (via SSH or cPanel Terminal):

```bash
chmod -R 775 storage bootstrap/cache
```

If the web server user is different, you may need to set ownership (e.g. `chown -R user:user storage bootstrap/cache`). Laravel needs to write to `storage/` and `bootstrap/cache/`.

---

## 5. Composer and migrations

On the server, in the **project root** (parent of `public`):

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

If you don’t use SSH, run these via cPanel **Terminal** or a **Setup / Deployment** script.

---

## 6. Sessions and HTTPS

If the site is **HTTPS**:

- In `.env` set:
  - `APP_URL=https://dayare.sandbox.rw`
  - `SESSION_SECURE_COOKIE=true`  
  so the session cookie is sent and login works.

---

## 7. Vite / frontend assets

If you use Vite, build assets **before** uploading:

```bash
npm ci
npm run build
```

Upload the built files (e.g. `public/build/`) to the server. Do **not** run `npm run dev` on the server.

---

## 8. Quick checklist

| Check | Action |
|-------|--------|
| Document root | Points to `public` folder |
| `.env` exists | In project root on server |
| `APP_URL` | Same as browser URL (e.g. `https://dayare.sandbox.rw`) |
| `APP_KEY` | Set (`php artisan key:generate`) |
| PHP | 8.2+ selected for domain |
| DB_* | Correct cPanel MySQL credentials |
| storage & bootstrap/cache | Writable (775) |
| vendor/ | Present (`composer install`) |
| Migrations | Run (`php artisan migrate --force`) |
| Config cache | Cleared (`php artisan config:clear`) |
| HTTPS | `SESSION_SECURE_COOKIE=true` if using HTTPS |

After changes to `.env`, always run:

```bash
php artisan config:clear
```

---

## 9. If you still get 404 on View / Facilities / Edit

- Confirm **APP_URL** in `.env` on the server is exactly the URL you use (e.g. `https://dayare.sandbox.rw`).
- Run `php artisan config:clear` on the server.
- In the browser, right‑click **View** → Copy link. The link should start with your domain, not `localhost`. If it still shows localhost, APP_URL is not applied (wrong .env or cache).
