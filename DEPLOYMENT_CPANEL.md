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

## 6. Sessions when your domain is HTTPS (secured)

**If the site is HTTPS** (e.g. `https://dayare.sandbox.rw` – “domain is now secured”):

1. In `.env` on the server set **exactly**:
   ```env
   APP_URL=https://dayare.sandbox.rw
   ```
   (Use `https://` and your real domain. No trailing slash.)

2. The app will set the session cookie as **Secure** when `APP_URL` starts with `https://`, so the browser sends it on HTTPS. You do **not** need to set `SESSION_SECURE_COOKIE` in `.env` (the app does it from `APP_URL`).

3. Run on the server:
   ```bash
   php artisan config:clear
   ```

4. In the browser: clear cookies for `dayare.sandbox.rw` (or use a private window), then open `https://dayare.sandbox.rw`, log in, and in the **same tab** click **Businesses** → **View** or **Facilities** or **Edit**. The session should persist.

**If the site is still HTTP** (e.g. `http://dayare.sandbox.rw`):

- Set `APP_URL=http://dayare.sandbox.rw` (use `http://`, not `https://`).
- Do **not** set `SESSION_SECURE_COOKIE=true` (leave it unset or set to `false`).  
  Otherwise the session cookie is marked “Secure” and the browser will not send it over HTTP, so you get sent to the login page when you click View / Facilities / Edit.
- The app will set the session cookie so it works over HTTP when `APP_URL` starts with `http://`.

*(HTTPS is covered above; the app sets the Secure cookie from APP_URL.)*

**If you are still redirected to login** when clicking View / Facilities / Edit, use the **cookie** session driver (session stored in the browser; no file or database needed; often fixes cPanel):

1. In `.env` add: `SESSION_DRIVER=cookie`
2. Run: `php artisan config:clear`
3. Clear browser cookies for the site, log in again, then try View/Facilities in the **same tab**.

You can also try `SESSION_DRIVER=file` and ensure `storage/framework/sessions` is writable.

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
| HTTPS | Set `APP_URL=https://...`; app sets Secure cookie automatically |

After changes to `.env`, always run:

```bash
php artisan config:clear
```

---

## 9. If you still get 404 or “Page not found” on View / Facilities / Edit

1. **Set APP_URL on the server**  
   In `.env` on the server set exactly the URL you use in the browser, for example:
   ```env
   APP_URL=https://dayare.sandbox.rw
   ```
   Then run: `php artisan config:clear`  
   The app uses this for all links (View, Facilities, Edit). If APP_URL is wrong or missing, links can point to localhost and you get 404.

2. **Check the link in the browser**  
   Right‑click **View** on a business → “Copy link address”. The link must start with your real domain (e.g. `https://dayare.sandbox.rw/...`). If it starts with `http://localhost`, APP_URL is not set or not applied (wrong .env or config cache).

3. **If you are sent to the login page with a message**  
   If you now see the **login page** with a message like “Your session may have expired. Please log in again.” instead of 404, the app is working but the session was not valid (e.g. cookie not sent). **Log in again**; the new session cookie should then work. If the site is **HTTPS**, ensure `APP_URL=https://dayare.sandbox.rw` in `.env` (the app sets the Secure cookie from APP_URL). Then run `php artisan config:clear`, clear site cookies, and log in again.
