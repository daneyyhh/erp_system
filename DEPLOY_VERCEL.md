# 🚀 Hosting your Enlight ERP on Vercel

Since you've configured your **Cloud Database**, you can now host the entire application for free on Vercel.

## 🛠️ Step 1: Pre-Flight Check
Before deploying, you **MUST** allow Vercel's servers to talk to your database:
1.  Go to your **Cloud Database Console** (e.g., TiDB, Supabase, etc.).
2.  Find the **Security / Firewall / Allowed IP Addresses** section.
3.  Add `0.0.0.0/0` to allow Vercel's dynamic IP addresses.
    *   *Why?* Vercel uses dynamic IP addresses that change constantly. This setting allows the Vercel-hosted app to always find your database.

## 📦 Step 2: Deploy using Vercel CLI
If you have the Vercel CLI installed on your computer, run these commands in your project folder:

```bash
# 1. Login if you haven't
vercel login

# 2. Deploy to production
vercel --prod
```

## 🔗 Step 3: Connect GitHub (Recommended)
The best way to "Host it" permanently is via GitHub:
1.  Create a new repository on **GitHub**.
2.  Push your code:
    ```bash
    git init
    git add .
    git commit -m "Deploy Enlight ERP"
    git remote add origin YOUR_REPO_URL
    git push -u origin main
    ```
3.  Go to **Vercel.com**, click **"Add New Project"**, and select your GitHub repo.
4.  Vercel will detect the `vercel.json` I created and host your site automatically!

## 🌐 Step 4: Add Your Custom Domain
To host Project ERP on your own domain (e.g., `www.your-erp-site.com`):
1. In the **Vercel Dashboard**, go to **Settings** -> **Domains**.
2. Enter your domain name and click **Add**.
3. Vercel will give you a **Value** (likely an IP address for an A record or a CNAME link).
4. Log in to your domain provider (GoDaddy, Namecheap, etc.) and update your **DNS Records** with the values Vercel provided.
5. Vercel will automatically issue a **Free SSL Certificate** once the records propagate.

## ✅ Cloud Health Link
Once live on your domain, visit:
`https://your-domain.com/cloud_init.php` - To set up your database tables.
