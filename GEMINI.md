# Project Summary: Pintventory

Pintventory is a containerized inventory management system built with a Laravel (PHP) backend and a Vue.js frontend using Vuetify 3. It is designed to track SKU-style inventory items with photo evidence, supporting a full ledger of purchases and sales across multiple vendors and customers.

## 🚀 Key Features

- **Inventory Tracking:** Manage items with SKUs, descriptions, quantities, reorder points, and storage locations.
- **Transaction Ledger:** Complete history of purchases (from vendors) and sales (to customers) with automatic quantity adjustments.
- **Media Management:** Support for up to 12 photos per item with drag-and-drop reordering and mobile camera support.
- **AI-Powered Workflows:**
    - **Single Item Scan:** Rapid identification and metadata extraction for a single object.
    - **Multi Item Scan:** High-throughput "shotgun" scanning of collections to identify multiple gems from one photo.
- **Deep Market Analysis:** Automated price estimation and listing strategy for eBay, Facebook Marketplace, and Etsy.
- **Google OAuth 2.0:** Secure authentication using Google OIDC.
- **Responsive UI:** Modern, Material Design interface built with Vuetify 3 with support for multiple "Neutral" and "High-Contrast" themes.
- **Role-Based Access:** Support for Admin, Staff, and Read-only roles.

## 🛠 Technical Architecture

- **Backend:** Laravel 11+ (PHP 8.3)
- **Frontend:** Vue.js 3 with Vuetify 3
- **Database:** PostgreSQL
- **Orchestration:** Docker Compose (All services run in separate containers)
- **AI Engines:** Gemini 2.0/2.5 Flash, Ollama (Llava/Bakllava), OpenAI GPT-4o
- **Web Server:** NGINX
- **Storage:** Local storage with S3-compatibility readiness

## 📈 Recent Improvements & Optimizations

### Backend
- **Multi-Item Scan Endpoint:** Added `shotgunScan` to AI providers and `AiManager` for batch object detection.
- **Extended AI Timeouts:** Increased API and cURL timeouts to 60s to support deep multi-item analysis.
- **Database Indexing:** Optimized frequently queried columns for sales, purchases, and inventory items.
- **Query Scopes:** Centralized filtering and metric logic (e.g., `withRevenueMetrics`, `lowStock`) in Eloquent models.
- **Rate Limiting:** Implemented `RateLimitAiRequests` middleware to protect expensive AI endpoints.

### Frontend
- **Consolidated "Add Items" UI:** Merged Single Scan, Multi Scan, and Manual entry into a single tabbed dialog with swipe support.
- **Intentional Scanning:** Removed automatic camera triggers on scanner tabs to ensure a more controlled, user-initiated experience.
- **Advanced Photo Gallery:** Implemented comprehensive zoom (up to 500%), drag-to-pan, and double-click toggle functionality for detailed photo inspection.
- **Marketplace Integration:** Real-time generation of platform-specific search links (eBay, FB, Etsy) within scan results.
- **Theme Neutralization:** Shifted AI scanning UIs to a neutral theme palette that adapts to the user's active theme.
- **Performance Caching:** Implemented store-level caching for vendors, customers, and item details to reduce redundant API calls.
- **Memory Management:** Automated Blob URL revocation in the photo gallery to prevent browser memory leaks.

## 📂 Core Data Model

- `InventoryItem`: The central entity tracking SKU, title, quantity, and status.
- `StorageLocation`: Hierarchical or named locations for stock.
- `Vendor` / `Customer`: Entities for tracking procurement and sales.
- `Purchase` / `Sale`: Transactional records linking items to vendors/customers.
- `Photo`: Media attachments for inventory items.

## 📝 Current Status

The application is in an advanced prototype stage with core CRUD operations, AI enhancements, and significant performance optimizations already implemented.
