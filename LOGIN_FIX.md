# Login not working on live server

Do these steps **on the server** (cPanel / SSH).

## 1. Edit `.env` on the server

Open `.env` in your project root and set:

```env
APP_URL=https://dayare.sandbox.rw
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=
```

- Use **your real site URL** for `APP_URL` (no slash at the end). If your site is HTTPS, use `https://`.
- **SESSION_SECURE_COOKIE=true** is required for HTTPS so the session cookie is sent. If your site is HTTP only, use `false` or leave it blank.
- **SESSION_DOMAIN** leave empty (or delete the line).

Save the file.

## 2. Clear config

In Terminal (project folder):

```bash
php artisan config:clear
```

## 3. Try again in the browser

1. Clear cookies for your site (or use a private/incognito window).
2. Go to your site and log in with **test@example.com** / **password**.

---

If it still fails, try in `.env`:

```env
SESSION_DRIVER=cookie
```

Then run `php artisan config:clear` again, clear browser cookies, and log in once more.
