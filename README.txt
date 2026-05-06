# CollectorVault — GoDaddy Setup Guide

## Files to Upload

Upload ALL of these to your GoDaddy public_html folder:

```
public_html/
├── .htaccess
├── index.php          ← Login page (this is your homepage)
├── scanner.php        ← Scanner app
├── collection.php     ← Collection gallery
├── api.php            ← Backend API
├── nav.php            ← Shared navigation
├── shared.css.php     ← Shared styles
├── categories.js.php  ← Category definitions
├── toast.php          ← Shared toast notification
├── logout.php         ← Logout handler
├── data/              ← MUST create this folder (auto-protected)
└── uploads/           ← MUST create this folder (for thumbnails)
```

## GoDaddy Upload Steps

1. Log into GoDaddy → My Products → Web Hosting → Manage
2. Open **cPanel → File Manager**
3. Navigate to `public_html`
4. Upload all PHP files directly into `public_html`
5. Create two empty folders: `data` and `uploads`
6. Set folder permissions:
   - `data/`    → 755 (right-click → Permissions)
   - `uploads/` → 755

## First Use

1. Visit your domain (e.g. yourdomain.com)
2. Click "Create Account" and register your first user
3. Sign in and start scanning!

## Multiple Users

Any number of people can register and sign in. Each user's
collection is separate — they only see their own items.

## API Keys

API keys are NOT stored in this repo. They live on the server
outside the web root, one file per key:

  /home/u133725179/cv_gemini_key.txt   — Gemini API key
  /home/u133725179/cv_openai_key.txt   — Optional OpenAI fallback key

api.php reads them at runtime via cv_read_secret(). To rotate a
key, edit the file on the server (FTP or hPanel File Manager).
Never commit a key to this repo — GitHub secret scanning will
flag it and Google will auto-revoke the key.

## Data Files (auto-created)

- data/users.csv      — User accounts (passwords are hashed)
- data/collection.csv — All scanned items for all users
- uploads/            — Compressed item thumbnails

## Security Notes

- The data/ folder is blocked from web access via .htaccess
- Passwords are stored using PHP's bcrypt (password_hash)
- Sessions are used for authentication
- Users can only see and delete their own items
