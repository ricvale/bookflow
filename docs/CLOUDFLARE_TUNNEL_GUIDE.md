# Cloudflare Quick Tunnel Guide (Zero-Config)

## ⚡ The 2-Second Public Demo
This is the **easiest** way to share your local BookFlow work with anyone on the internet.

- **✅ No Account Required**
- **✅ No Credit Card Required**
- **✅ No Complex Configuration**
- **✅ No Installation** (Runs inside your existing Docker stack)

---

### 1. Launch the Tunnel
Ensure your Docker containers are running:
```bash
docker compose up -d
```

### 2. Find your Public URL
Run this command to see the random URL Cloudflare gave you:
```bash
docker compose logs tunnel
```

Look for a line that looks like this:
`Your quick Tunnel has been created! Visit it at: https://something-random.trycloudflare.com`

### 3. Sharing
Anyone with that URL can now access your local BookFlow instance as long as your Docker container is running.

---

### ⚠️ Security Note
- **Public Access**: Anyone with the link can access your app. Do not put sensitive production data in your local database while the tunnel is active.
- **Temporary**: The URL will change if you restart the `tunnel` container.
- **Hardware**: The app is running on your computer. If you turn off your PC, the link will stop working.

---

### How it works (Technical)
We added a `tunnel` service to `docker-compose.yml` that connects to Cloudflare and routes traffic to our `nginx` service:
```yaml
  tunnel:
    image: cloudflare/cloudflared:latest
    command: tunnel --url http://nginx:80
```
