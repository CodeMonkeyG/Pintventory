1. Product overview

- **App name:** Inventory Ledger (Pintventory)
- **Purpose:** Track inventory items with photo evidence and a complete purchase/sale ledger across vendors and customers.
- **Primary users / roles:**
	- **Owner / Admin:** full access, user/role management, exports, deletes/archives
	- **Staff:** CRUD inventory, purchases, sales, customers, vendors, storage locations
	- **Read-only (optional):** view-only reporting + item detail

- **Top success criteria:**
	- Add an item (with photos) and record purchases/sales in under 60 seconds.
	- Always know on-hand quantity and last-known unit cost/price for any item.
	- Export clean CSV for accounting/taxes without manual cleanup.

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

- **Navigation:** left sidebar (Inventory, Customers, Vendors, Storage Locations, Profile)
- **Global search:** inventory + customers + vendors (single search box, scoped results)
- **Sort / filters:** saved filters per user; default sort by `updated_at` desc
- **Pagination:** cursor or page-based; default 25 rows; remember last page per user
- **Empty states:** action-forward CTAs (e.g., “Add your first item”)
- **Toasts & validation:** toast on success/failure; inline field errors for validation

4. Core data model

This is SKU-style inventory (not unique physical objects, though "unique" type is supported). Primary entities:

- **InventoryItem**
	- `id` (uuid), `sku`, `title` (required), `description`, `evaluation`, `status` (in_stock | low_stock | out_of_stock | archived)
	- `item_type` (standard | unique) - default: standard
	- `quantity_on_hand` (integer, updated by transactions), `reorder_point`, `unit`, `tags` (string[]), `location` (deprecated text field)
	- `storage_location_id` (foreign key to `storage_locations`)
	- `created_at`, `updated_at`, `created_by_user_id`, `archived_at`

- **StorageLocation**
	- `id`, `name` (required), `description`, `user_id`, `created_at`, `updated_at`

- **Photo**
	- `id`, `inventory_item_id`, `storage_key`, `mime_type`, `caption`, `sort_order`, `created_at`

- **Vendor** / **Customer**
	- `id`, `name` (required), `contact_name`, `email`, `phone`, `address`, `notes`, `is_preferred`

- **Purchase** (transaction)
	- `id`, `inventory_item_id`, `vendor_id`, `purchased_at`, `quantity_purchased` (>0), `unit_cost` (decimal), `shipping_cost`, `tax_cost`, `reference_number`, `notes`, `created_by_user_id`, `created_at`

- **Sale** (transaction)
	- `id`, `inventory_item_id`, `customer_id`, `sold_at`, `quantity_sold` (>0), `unit_price` (decimal), `discount`, `tax`, `payment_status` (paid|unpaid|partial), `notes`, `created_by_user_id`, `created_at`

- **Policy decisions:**
	- Inventory items are product definitions with quantity tracking (SKU-style).
	- Quantity is required (default 0); purchases increase and sales decrease `quantity_on_hand`.
	- Partial sales are allowed; basic COGS/profit reporting via last or average cost later.

5. Pages

- **Inventory Page**
	- **Purpose:** find items fast, see on-hand, and act (purchase/sell/edit) without leaving the page.
	- **Primary actions:** Add Item, Edit, Quick Purchase, Quick Sale, Archive
	- **List columns:** Photo thumbnail, Title (with SKU), Status, Type, Qty on hand, Location, Actions
	- **Filters:** Status, Item Type, Tag, Storage Location, Vendor, Customer, Low stock toggle (qty <= reorder_point), Updated date range
	- **Item detail:** separate route `/inventory/:id` for deep history and sharing links

- **Storage Locations Page**
	- **Purpose:** manage physical or logical storage areas.
	- **Primary actions:** Add Location, Edit, Delete
	- **Columns:** Name, Description, Created At, Actions

- **Profile Page**
	- **Purpose:** user identity, preferences, and admin user management
	- **Fields / prefs:** Display name (editable), Email (read-only), Role (admin-managed), Currency, Date format, Default tax handling, Notification prefs
	- **Admin actions:** Invite users, set roles, soft-disable users

- **Customers Page**
	- **Purpose:** manage customers and see what they bought
	- **Columns:** Name, Contact, Last Purchase, Total Orders, Total Revenue (optional)
	- **Detail:** sales ledger (filterable), notes, address/contact

- **Vendors Page**
	- **Purpose:** manage vendors and see purchase history
	- **Columns:** Name, Contact, Last Purchase, Total POs, Total Spend (optional)
	- **Detail:** purchase ledger (filterable), preferred flag, notes

6. Add / Edit Inventory Modal (expanded)

The Inventory modal is a key UI surface and has been extended with the following behavior and UX:

- **Modal modes:** Create | Edit
- **Tabs:** Details (default), Photos, Purchases, Sales — Purchases/Sales show only in Edit mode
- **Save behavior:** Save, Save & Add Another (Create flow), Save (Edit)

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
	- Storage Location: Dropdown from `storage_locations`
	- SKU (auto-generate INV-YYYY-#### if blank), Reorder point (default 0), Unit (default "each"), Status

- **Purchases (Vendors)**
	- Inline subtable with “Add Purchase” form
	- Fields: Vendor, Date, Qty, Unit Cost, Shipping, Tax, Reference #, Notes
	- Adding a purchase increases `quantity_on_hand`; editing recalculates quantities (respecting chronological constraints)

- **Sales (Customers)**
	- Inline subtable with “Add Sale” form
	- Fields: Customer, Date, Qty, Unit Price, Discount, Tax, Payment Status, Notes
	- Adding a sale decreases `quantity_on_hand`; selling beyond on-hand is blocked by default (admin toggle to allow negative)

- **Validation & UX**
	- Required: title; purchases require vendor/qty/unit_cost; sales require customer/qty/unit_price
	- Inline validation and on-submit checks
	- Confirm modal close when there are unsaved changes

- **Audit events:** item_created, item_updated, item_archived, photo_added, photo_removed, photo_reordered, purchase_added, purchase_updated, purchase_deleted, sale_added, sale_updated, sale_deleted, inventory_adjusted

7. Non-functional requirements

- **Performance targets:**
	- Inventory list: <500ms server time for first page (cache where possible)
	- Item detail: <800ms including ledger rows (paginate ledger)

- **Operational:**
	- **Storage:** S3-compatible object storage for photos; CDN optional
	- **Backups:** daily DB backups; configurable retention (default 30 days)
	- **Logging / audit:** immutable audit log table; exportable by admin
	- **Security:** least-privilege, encrypt at rest, TLS in transit

- **Offline:** none for v1

8. API surface

Authentication/Users

- `GET /me`

Inventory

- `GET /inventory?query=&status=&tag=&vendor_id=&customer_id=&low_stock=&page=`
- `GET /inventory/:id`
- `POST /inventory` (create)
- `PUT /inventory/:id` (update)
- `POST /inventory/:id/archive` and `POST /inventory/:id/unarchive`

Photos

- `POST /inventory/:id/photos` (multipart upload or signed upload workflow)
- `DELETE /photos/:id`
- `PUT /photos/:id` (reorder / update caption)

Transactions

- `POST /inventory/:id/purchases`
- `PUT /purchases/:id`
- `DELETE /purchases/:id`
- `POST /inventory/:id/sales`
- `PUT /sales/:id`
- `DELETE /sales/:id`

Vendors / Customers

- `GET|POST|PUT /vendors`
- `GET|POST|PUT /customers`

Storage Locations

- `GET|POST|PUT|DELETE /storage-locations`

Security & Auth

- **Auth:** cookie session with CSRF protection
- **Authorization (RBAC):**
	- **Admin:** full access
	- **Staff:** CRUD inventory, transactions, vendors, customers, locations; cannot hard-delete; can archive
	- **Read-only:** GET-only

9. Open questions and recommended defaults

- Inventory semantics: SKU + quantity (recommended)
- Quantity required: yes (default 0; purchases/sales adjust)
- Taxes/fees: optional fields on transactions, not mandatory
- Profit reporting: basic (last cost or simple average cost; advanced reporting later)
- Multi-user permissions: RBAC as above
- Import / export: CSV export in v1; import later
- Mobile-first: responsive UI; camera upload supported

10. AI Function Endpoints

- **Purpose:** expose a small set of server-side AI helper endpoints that behave like normal REST API routes but map to predetermined sequences of actions (including calls to external LLM/image services). These endpoints provide higher-level, repeatable automations (e.g., image identification, tagging, suggested title/description generation) while preserving auditability and RBAC.

- **Design principles:**
	- Routes are RESTful and protected by existing auth/CSRF/RBAC rules.
	- Each route maps to a named server method that orchestrates one or more API calls (e.g., upload image to storage, call LLM with a prompt, persist results).
	- Inputs and outputs are strictly typed JSON (or multipart for file uploads).
	- Rate limiting and audit logging apply.

- **Initial function: Image Identify (LLM-assisted)**
	- **Route:** `POST /ai/image-identify`
	- **Auth:** cookie session + CSRF; staff+ or admin only (configurable)
	- **Payload:** multipart/form-data with `file` (image); optional JSON field `model` (string) and `options` (object)
	- **Behavior:**
		1. Server accepts the image and stores it temporarily (or uploads to configured storage).
		2. Server sends the image (or a signed URL) to a configurable LLM/image-analysis pipeline (could be a multimodal LLM or an image-tagging service) with a deterministic prompt template.
		3. The LLM returns structured output which the server normalizes to JSON: `{ title: string, description: string, tags?: string[], confidence?: number }`.
		4. Server returns the normalized JSON to the client and optionally persists the suggested title/description as a `suggestion` record for review.
	- **Response (200):**
		{
			"title": "Suggested title",
			"description": "Suggested description",
			"tags": ["tag1","tag2"],
			"confidence": 0.87
		}
	- **Errors:** 4xx for bad input/auth; 5xx for external LLM failures with safe error messaging.

- **Configuration & auditing:**
	- Admins can configure which LLM provider/model to use and per-tenant limits.
	- All AI function calls are logged (who invoked, timestamp, model used, input meta, and normalized output). Raw LLM responses may be stored separately with restricted access.

- **Privacy & safety:**
	- Uploaded images are retained only as required for processing and audit; retention policies configurable.
	- Do not expose raw LLM system prompts to clients.

- **Future expansions:**
	- Additional endpoints for batch image tagging, suggested SKU generation, auto-categorization, or natural-language search enhancements.
