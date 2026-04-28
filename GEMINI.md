# Project Summary: Pintventory

Pintventory is a containerized inventory management system built with a Laravel (PHP) backend and a Vue.js frontend using Vuetify 3. It is designed to track SKU-style inventory items with photo evidence, supporting a full ledger of purchases and sales across multiple vendors and customers.

## 🚀 Key Features

- **Inventory Tracking:** Manage items with SKUs, descriptions, quantities, reorder points, and storage areas.
- **Transaction Ledger:** Complete history of purchases (from vendors) and sales (to customers) with automatic quantity adjustments.
- **Media Management:** Support for up to 12 photos per item with drag-and-drop reordering and mobile camera support.
- **AI-Powered Workflows:**
    - **Single Item Scan:** Rapid identification and metadata extraction for a single object.
    - **Multi Item Scan:** High-throughput "shotgun" scanning of collections to identify multiple gems from one photo.
- **Deep Market Analysis:** Automated price estimation and listing strategy for eBay, Facebook Marketplace, and Etsy.
- **Offline-First PWA:** Full support for mobile installation, offline asset caching, and "Snap-and-Wait" data persistence when connection is lost.
- **Google OAuth 2.0:** Secure authentication using Google OIDC.
- **Responsive UI:** Modern, Material Design interface built with Vuetify 3 with support for multiple "Neutral" and "High-Contrast" themes.

## 🛠 Technical Architecture

- **Backend:** Laravel 11+ (PHP 8.3)
- **Frontend:** Vue.js 3 with Vuetify 3 + Vite PWA
- **Database:** PostgreSQL + Browser IndexedDB (Offline Queue)
- **Orchestration:** Docker Compose (All services run in separate containers)
- **AI Engines:** Gemini 2.0/2.5 Flash, Ollama (Llava/Bakllava), OpenAI GPT-4o
- **Web Server:** NGINX

## 📈 Recent Improvements & Optimizations

### Mobile & Offline
- **PWA Integration:** Added `vite-plugin-pwa` for service worker management and app manifest support.
- **"Snap-and-Wait" Sync:** Implemented an IndexedDB-backed sync queue that allows users to save new items and photos while offline.
- **Auto-Synchronization:** Items queued while offline are automatically uploaded sequentially when connectivity is restored.
- **Offline UI Indicators:** Added a global connectivity monitor and snackbar notifications to alert users when they are disconnected.

### Frontend
- **Enhanced Scanner UX:** Added direct camera support via `capture` attributes and split "Take Photo" vs "Open Gallery" inputs for better mobile control.
- **Inventory List Interactions:** Refined row-click behavior to open edit modals while preserving checkbox selection for bulk operations.
- **Page-Aware Multi-Select:** Fixed "Select All" logic to correctly handle paginated results and ensure accurate batch updates.
- **Modal UX Improvements:** Added smooth scrolling, "Back to Top" navigation, and section-specific jumping for large item detail views.
- **Data Consistency:** Implemented forced cache invalidation in the Pinia store to ensure UI reflects inventory changes immediately after transactions and scans.
- **Terminology Simplification:** Renamed "Storage Locations" to "Storage" across the entire UI for a cleaner interface.
- **Vite Docker Stability:** Configured HMR watcher to ignore system directories (`/proc`, `/sys`), preventing random reloads in containerized environments.

## 📂 Core Data Model

- `InventoryItem`: The central entity tracking SKU, title, quantity, and status.
- `Storage`: Hierarchical or named locations for stock (formerly StorageLocation).
- `Vendor` / `Customer`: Entities for tracking procurement and sales.
- `Purchase` / `Sale`: Transactional records linking items to vendors/customers.
- `Photo`: Media attachments for inventory items.

## 📝 Current Status

The application is in an advanced prototype stage with core CRUD operations, AI enhancements, and significant performance optimizations already implemented.
