# Free Deployment Options

## Overview
This guide focuses on platforms that allow you to host BookFlow for **free** without requiring a credit card for identity verification.

---

> **🚀 PRO TIP: Instant Public Sharing (0-Config)**
> If you are just doing a demo, you don't need "Hosting". Use the **Cloudflare Quick Tunnel** built into the `docker-compose.yml`. 
> - **No account, domain, or credit card required.**
> - **Works instantly** with your local Docker data.
> - [View Quick Tunnel Guide](./CLOUDFLARE_TUNNEL_GUIDE.md)

---


## 1. AlwaysData (Recommended for PHP/MariaDB) ⭐

**Best for**: Traditional PHP/MariaDB stack with zero setup and **no credit card**.

AlwaysData is a high-quality professional host that offers a generous free tier. It supports modern PHP (8.4), MariaDB, and even SSH/Git deployments.

### Free Tier
- ✅ **100MB Storage** (Enough for this app)
- ✅ **MariaDB/MySQL included**
- ✅ **PHP 8.4 Support**
- ✅ **No Credit Card Required**
- ✅ **SSL included**
- ✅ **Git / SSH / FTP**

### Setup
1. Create an account at [alwaysdata.com](https://www.alwaysdata.com/).
2. Create a "PHP" site through their dashboard.
3. Point the site to your `public/` folder.
4. Create a MariaDB database and update your `.env` credentials.

---

## 2. Hugging Face Spaces (Best for Docker) 🚀

**Best for**: Permanent free Docker hosting with **no credit card**.

Hugging Face "Spaces" can run any Docker container for free. It is extremely robust and provides 16GB of RAM.

### Free Tier
- ✅ **2 vCPU / 16GB RAM**
- ✅ **Permanent Static URL**
- ✅ **No Credit Card Required**
- ✅ **Docker Native**

### Setup
1. Create a "Space" on [Hugging Face](https://huggingface.co/spaces).
2. Choose **Docker** as the SDK.
3. Push your code to the Space's Git repository.
4. Use a free external database (like AlwaysData) since Spaces storage is ephemeral.

---

## 3. Koyeb

**Best for**: Modern Docker experience without credit card.

### Free Tier
- ✅ **1 Web Service** (Nano instance)
- ✅ **512MB RAM**
- ✅ **No Credit Card** (for Hobby plan in most regions)

### Setup
1. Connect your GitHub account to [Koyeb](https://www.koyeb.com/).
2. Select your repository and deploy.

---

## 4. Render.com

**Best for**: Quick Docker demos.

### Free Tier
- ✅ **Web service** (512MB RAM)
- ✅ **No Credit Card**
- ✅ **Automatic HTTPS**

### Limitations
- ⚠️ Database (Postgres) expires after 90 days.
- ⚠️ MariaDB is NOT available on the free tier.

---

## Comparison (No Credit Card Required)

| Platform | Type | Database | Persistency | Best For |
|----------|------|----------|-------------|----------|
| **AlwaysData** | Shared | MariaDB | ✅ Permanent | PHP + MariaDB |
| **Hugging Face**| Docker | None | ✅ Permanent | Performance |
| **Koyeb** | Docker | None | ✅ Permanent | Modern Docker |
| **Render** | Docker | Postgres | ⚠️ 90 Days | Quick Demos |

---

## Summary Selection

1.  **Easiest way to show the app live**: Use **AlwaysData**. It fits the PHP/MariaDB nature of the project perfectly without changing code.
2.  **Best Performance**: Use **Hugging Face Spaces** for the app and AlwaysData for the database.
3.  **Local Demo**: If you just want to show someone the app running on your machine, see the [Cloudflare Quick Tunnel Guide](./CLOUDFLARE_TUNNEL_GUIDE.md) (No account/domain/card required).

---

## Next Steps

1. Register at your chosen provider (AlwaysData recommended).
2. Push your code to GitHub.
3. Link the provider to your GitHub repo.
4. Update your `.env` file with the host's credentials.

See [DEPLOYMENT.md](./DEPLOYMENT.md) for detailed production configurations.
