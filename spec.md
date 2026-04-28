1. Product overview

- **App name:** Inventory Ledger (Pintventory)
- **Purpose:** Track inventory items with photo evidence and a complete purchase/sale ledger across vendors and customers.
- **Primary users / roles:**
	- **Owner / Admin:** full access, user/role management, exports, deletes/archives
	- **Staff:** CRUD inventory, purchases, sales, customers, vendors, storage
	- **Read-only (optional):** view-only reporting + item detail

- **Top success criteria:**
	- Add an item (with photos) and record purchases/sales in under 60 seconds.
	- Always know on-hand quantity and last-known unit cost/price for any item.
	- Export clean CSV for accounting/taxes without manual cleanup.
	- Works reliably in low-connectivity areas (Offline-First).

- **Workspace Management:**
	- Multiple isolated workspaces per user.
	- Automatic data isolation via `WorkspaceScope`.
	- Role-based access within workspaces (owner, editor, viewer).
	- Quick switching between workspaces.

2. Authentication

- **Login:** Google OAuth 2.0 (OIDC)
	- **Allowed domains:** none by default (optional @yourcompany.com restriction)
	- **User creation policy:** invite-only (admin issues invites by email; first login activates account)
	- **Session strategy:** server session + httpOnly cookie (rotate session on login)
	- **Logout:** clear session locally; do not sign out of Google globally

- **Security notes:**
	- **CSRF:** SameSite=Lax cookies + CSRF token on mutation endpoints
	- **Rate limiting:** per-IP + per-account on login callback and mutation endpoints
	- **Audit events:** login_success, login_failure, logout, invite_sent

3. Global app structure

- **Navigation:** left sidebar (Inventory, Customers, Vendors, Storage, Locations, Profile)
- **Global search:** inventory + customers + vendors (single search box, scoped results)
- **Sort / filters:** saved filters per user; default sort by `updated_at` desc
- **Pagination:** cursor or page-based; default 25 rows; remember last page per user
- **Empty states:** action-forward CTAs (e.g., “Add your first item”)
- **Toasts & validation:** toast on success/failure; inline field errors for validation
- **Connectivity:** global offline indicator and "Snap-and-Wait" sync status notifications

4. Core data model

This is SKU-style inventory (not unique physical objects, though "unique" type is supported). Primary entities:

- **InventoryItem**
	- `id` (uuid), `sku`, `title` (required), `description`, `evaluation`, `status` (in_stock | low_stock | out_of_stock | archived)
	- `item_type` (standard | unique) - default: standard
	- `quantity_on_hand` (integer, updated by transactions), `reorder_point`, `unit`, `tags` (string[]), `location` (deprecated text field)
	- `storage_location_id` (foreign key to `storage_locations`)
	- `created_at`, `updated_at`, `created_by_user_id`, `archived_at`

- **Storage** (formerly StorageLocation)
	- `id`, `name` (required), `description`, `user_id`, `created_at`, `updated_at`

- **Photo**
	- `id`, `inventory_item_id`, `storage_key`, `mime_type`, `caption`, `sort_order`, `created_at`

- **Vendor** / **Customer**
	- `id`, `name` (required), `contact_name`, `email`, `phone`, `address`, `notes`, `is_preferred`

- **OfflineSyncQueue** (Browser-only via IndexedDB)
	- `id` (auto-increment), `type` (CREATE_ITEM), `payload`, `photos` (File[]), `timestamp`, `status`

5. Pages

- **Inventory Page**
	- **Purpose:** find items fast, see on-hand, and act (purchase/sell/edit) without leaving the page.
	- **Primary actions:** Add Items (Scan/Manual), Edit, Quick Purchase, Quick Sale, Archive
	- **List columns:** Photo thumbnail, Title (with SKU), Status, Type, Qty on hand, Storage, Actions
	- **Filters:** Status, Item Type, Tag, Storage, Vendor, Customer, Low stock toggle (qty <= reorder_point), Updated date range
	- **Offline behavior:** Automatically synchronizes queued items when back online.

- **Storage Page**
	- **Purpose:** manage physical or logical storage areas.
	- **Primary actions:** Add Storage, Edit, Delete
	- **Columns:** Name, Description, Created At, Actions

- **Locations Page**
	- **Purpose:** Search and browse items by physical location hierarchy.

- **Profile Page**
	- **Purpose:** user identity, preferences, and admin user management
	- **Fields / prefs:** Display name (editable), Email (read-only), Role (admin-managed), Currency, Date format, Default tax handling, Notification prefs
	- **Admin actions:** Invite users, set roles, soft-disable users

6. Add Items Modal (Consolidated)

The primary entry point for adding inventory is a tabbed modal that supports multiple workflows:

- **Single Scan (AI):** Capture one photo to auto-generate metadata and market analysis. Supports direct "Take Photo" (camera) or "Open Gallery" workflows. Works offline (queues for later AI analysis).
- **Multi Scan (AI):** Capture one "shotgun" photo to identify multiple objects at once for batch import. Supports direct "Take Photo" (camera) or "Open Gallery" workflows. Works offline (queues items for later upload).
- **Manual:** Direct link to the classic manual entry form for precise control. Works offline (saves to browser sync queue).

---

7. Edit Inventory Modal (Standard)

The standard inventory modal is used for editing existing items or detailed manual creation:

- **Photos**
	- Camera support on mobile (where available) and file upload on desktop
	- Max images: 12; allowed types: jpg / png / webp; max size: 10MB each
	- Upload workflow supports previews (client-side), pending uploads, and submission with multipart or signed upload flow
	- Reorder images via drag/drop; delete is a soft delete with audit trail

- **Details tab**
	- Title: required (3–120 chars)
	- Description: plain text (optionally markdown later)
	- Evaluation: AI-assisted or manual assessment of the item
	- Item Type: "standard" or "unique"
	- Tags: freeform chips + autocomplete from existing tags (stored as string[])
	- Storage: Dropdown from `storage_locations`
	- SKU (auto-generate INV-YYYY-#### if blank), Reorder point (default 0), Unit (default "each"), Status
	- Modal includes "Back to Top" navigation and smooth scrolling to AI sections.

... [rest of file unchanged] ...
