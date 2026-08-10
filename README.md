# Pintventory (Inventory Ledger System)

**Pintventory** is a modern, containerized inventory management system designed for tracking SKU-based inventory with multi-photo evidence, an offline-first PWA architecture, AI-powered item recognition, and a complete transaction ledger across vendors and customers.

---

## 🚀 Key Features

* **SKU & Stock Management:** Track standard and unique inventory items with custom SKUs, descriptions, quantities on hand, reorder alerts, and physical storage locations.
* **Media & Photo Attachments:** Support for up to 12 high-resolution photos per item with camera capture support on mobile, drag-and-drop reordering, and light-box inspection.
* **AI-Powered Object Scanning:**
  * **Single Item Scan:** Capture a single item to extract metadata, assign tags, and generate market evaluation estimates across eBay, Etsy, and Facebook Marketplace.
  * **Multi-Item "Shotgun" Scan:** Capture collections of objects in a single photo for automated multi-item batch identification and listing.
* **Offline-First PWA ("Snap-and-Wait"):** Built with `vite-plugin-pwa` and an IndexedDB browser sync queue. Users can create items and attach photos in low-connectivity environments; queued changes automatically synchronize sequentially once network connectivity is restored.
* **Multi-Tenant Workspaces:** Support for isolated workspaces per user with role-based access control (Owner, Editor, Viewer).
* **Transaction Ledger:** Historical logging of purchases from vendors and sales to customers with automated quantity adjustments.
* **Authentication:** Google OAuth 2.0 (OIDC) authentication with session-based security.

---

## 🛠 Technology Stack

* **Backend:** Laravel 11+ / PHP 8.3 FPM
* **Frontend:** Vue.js 3, Vuetify 3 (Material Design), Pinia Store, Vite PWA
* **Databases:** PostgreSQL 15 (Server) & Browser IndexedDB (Client Sync Queue)
* **AI Engines:** Gemini 2.5 Flash, Ollama (Llava/Bakllava), OpenAI GPT-4o
* **Orchestration & Proxy:** Docker Compose & NGINX

---

## 🐳 Docker Services & Network Topology

Pintventory is containerized to run seamlessly alongside other applications behind a host reverse proxy:

| Container Name | Service | Internal Port | Mapped Host Port | Description |
| :--- | :--- | :--- | :--- | :--- |
| `pintventory-nginx` | NGINX Web Server | `80`, `443` | `8082`, `8442` | Routes `/api`, `/auth`, `/sanctum` to Laravel; `/` to Vue Vite app |
| `pintventory-laravel` | Laravel PHP-FPM | `9000` | Internal | Backend REST API & database interactions |
| `pintventory-vue` | Vue 3 / Vite PWA | `5173` | `5174` | Frontend web application & HMR server |
| `pintventory-postgres` | PostgreSQL 15 | `5432` | `5433` | Primary database store |

---

## 💻 Quick Start

### 1. Launch Containers
```bash
docker compose up -d
```

### 2. Access the Application
* **Local Web Interface:** `https://localhost:8442` or `http://localhost:8082`

---

## 🌐 Bare Metal NGINX Reverse Proxy Setup

To run Pintventory alongside other containerized stacks on the host machine, the containerized NGINX listening ports are mapped to host ports `8082` (HTTP) and `8442` (HTTPS).

The host bare-metal NGINX proxy routes domain traffic as follows:
* `http://pintventory.com` -> `http://127.0.0.1:8082`
* `https://pintventory.com` -> `https://127.0.0.1:8442`

See [nginx-baremetal.conf](../nginx-baremetal.conf) for the full multi-site host NGINX configuration.
