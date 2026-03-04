# Fix: "Redirected to login" when clicking View / Facilities / Edit

Do these **3 steps** on your cPanel server. No need to understand why — just follow them.

---

## Step 1: Edit `.env` on the server

Open the `.env` file in your project root (where `artisan` is) and set this line **exactly** as below. Use your real site URL; no space before or after `=` and **no slash at the end**.

```env
APP_URL=https://dayare.sandbox.rw
```

If your site is `https://yoursite.com`, use that instead:

```env
APP_URL=https://yoursite.com
```

Save the file.

---

## Step 2: Run this command on the server

In cPanel, open **Terminal** (or use SSH). Go to your project folder (the one that contains `artisan`) and run:

```bash
php artisan config:clear
```

You should see no error. If you see "command not found", make sure you are in the correct folder (type `ls` and you should see `artisan`, `app`, `config`, etc.).

---

## Step 3: Clear cookies and log in again

1. In your browser, **clear cookies** for your site (e.g. dayare.sandbox.rw).  
   - Or: open a **private/incognito** window.
2. Go to your site: `https://dayare.sandbox.rw` (or your URL).
3. **Log in**.
4. **In the same tab**, click **Businesses** → then **View** or **Facilities** or **Edit**.

It should work. If you open the link in a new tab, try again in the same tab.

---

## If it still sends you to login

1. In `.env` on the server, add this line (or change it if it already exists):

```env
SESSION_DRIVER=cookie
```

2. Run again:

```bash
php artisan config:clear
```

3. Clear browser cookies (or use private window), log in again, and try View/Facilities in the **same tab**.

---

## Summary

| Step | What to do |
|------|------------|
| 1 | In `.env`: set `APP_URL=https://dayare.sandbox.rw` (your real URL, no trailing slash) |
| 2 | In Terminal: `php artisan config:clear` |
| 3 | In browser: clear cookies (or private window) → log in → click View/Facilities in the **same tab** |
| If still broken | In `.env` add `SESSION_DRIVER=cookie`, run `php artisan config:clear` again, clear cookies, log in, try again |

That’s it.
