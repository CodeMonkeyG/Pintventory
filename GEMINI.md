# Project Summary: Pintventory

Pintventory is a containerized inventory management system built with a Laravel (PHP) backend and a Vue.js frontend using Vuetify 3. It is designed to track SKU-style inventory items with photo evidence, supporting a full ledger of purchases and sales across multiple vendors and customers.

## 🚀 Key Features

- **Inventory Tracking:** Manage items with SKUs, descriptions, quantities, reorder points, and storage locations.
- **Transaction Ledger:** Complete history of purchases (from vendors) and sales (to customers) with automatic quantity adjustments.
- **Media Management:** Support for up to 12 photos per item with drag-and-drop reordering and mobile camera support.
- **AI Integration:** Automated image identification using LLMs (Gemini, Ollama) to suggest titles, descriptions, and tags.
- **Google OAuth 2.0:** Secure authentication using Google OIDC.
- **Responsive UI:** Modern, Material Design interface built with Vuetify 3.
- **Role-Based Access:** Support for Admin, Staff, and Read-only roles.

## 🛠 Technical Architecture

- **Backend:** Laravel 11+ (PHP 8.3)
- **Frontend:** Vue.js 3 with Vuetify 3
- **Database:** PostgreSQL
- **Orchestration:** Docker Compose (All services run in separate containers)
- **Web Server:** NGINX
- **Storage:** Local storage with S3-compatibility readiness

## 📈 Recent Improvements & Optimizations

### Backend
- **Database Indexing:** Optimized frequently queried columns for sales, purchases, and inventory items.
- **Query Scopes:** Centralized filtering and metric logic (e.g., `withRevenueMetrics`, `lowStock`) in Eloquent models.
- **Rate Limiting:** Implemented `RateLimitAiRequests` middleware to protect expensive AI endpoints.
- **Error Handling:** Enhanced AI drivers with robust try-catch wrappers and timeout configurations.

### Frontend
- **Vuetify 3 Migration:** Fully refactored the UI from custom CSS to Vuetify components for better accessibility and responsiveness.
- **Performance Caching:** Implemented store-level caching for vendors, customers, and item details to reduce redundant API calls.
- **Search Debouncing:** Added 300ms delays to search inputs to optimize server load.
- **Memory Management:** Automated Blob URL revocation in the photo gallery to prevent browser memory leaks.

## 📂 Core Data Model

- `InventoryItem`: The central entity tracking SKU, title, quantity, and status.
- `StorageLocation`: Hierarchical or named locations for stock.
- `Vendor` / `Customer`: Entities for tracking procurement and sales.
- `Purchase` / `Sale`: Transactional records linking items to vendors/customers.
- `Photo`: Media attachments for inventory items.

## 📝 Current Status

The application is in an advanced prototype stage with core CRUD operations, AI enhancements, and significant performance optimizations already implemented.
