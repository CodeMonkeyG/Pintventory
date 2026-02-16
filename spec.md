1. Product overview

App name: Inventory Ledger
One-line purpose: Track inventory items with photo evidence and a complete purchase/sale ledger across vendors and customers.
Primary users / roles:

Owner/Admin — full access, user/role management, exports, deletes/archives

Staff — CRUD inventory, purchases, sales, customers, vendors

Read-only (optional) — view-only reporting + item detail

Success criteria (top 3):

Add an item (with photos) and record purchases/sales in under 60 seconds.

Always know on-hand quantity and last-known unit cost/price for any item.

Export clean CSV for accounting/taxes without manual cleanup.

2. Authentication
Login Page (Google OAuth)

Provider: Google OAuth 2.0 (OIDC)
Allowed domains: none by default (support optional @yourcompany.com restriction)
User creation policy: invite-only (admin issues invites by email; first login activates account)
Session strategy: server session + httpOnly cookie (rotate session on login)
Logout behavior: clear session locally; do not attempt to sign out of Google globally

Security notes:

CSRF: SameSite=Lax cookies + CSRF token on mutation endpoints

Rate limiting: per-IP + per-account on login callback and mutation endpoints

Audit log events: login_success, login_failure, logout, invite_sent

3. Global app structure

Navigation: left sidebar (Inventory, Customers, Vendors, Profile)
Global search: inventory + customers + vendors (single search box, scoped results)
Sort/filter patterns: saved filters per user, default sort by updated_at desc
Pagination: cursor or page-based; default 25 rows; remember last page per user
Empty states: action-forward (“Add your first item”, “Create your first vendor”)
Error handling / toasts: toast on success/failure; inline field errors for validation

4. Core data model (recommended)

This assumes SKU-style items with quantities (not unique physical objects), which fits your “multiple purchases/sales per item” requirement.

Inventory Item

id (uuid)

sku (string, optional but recommended; auto-generated if blank)

title (string, required)

description (text, optional)

status (in_stock | low_stock | out_of_stock | archived)

quantity_on_hand (integer, derived or stored; recommended stored with transaction updates)

reorder_point (integer, default 0)

unit (each default)

tags (string[])

location (string, optional; e.g., “Garage shelf A3”)

created_at, updated_at

created_by_user_id

archived_at (nullable)

Photos

id

inventory_item_id

storage_key

mime_type

caption (optional)

sort_order

created_at

Vendor

id

name (required)

contact_name (optional)

email (optional)

phone (optional)

address (optional)

notes (optional)

is_preferred (bool, default false)

Customer

id

name (required)

contact_name (optional)

email (optional)

phone (optional)

address (optional)

notes (optional)

Purchase (transaction)

id

inventory_item_id

vendor_id

purchased_at (datetime, default now)

quantity_purchased (int, required, >0)

unit_cost (decimal(10,2), required)

shipping_cost (decimal(10,2), default 0)

tax_cost (decimal(10,2), default 0)

reference_number (string, optional)

notes (optional)

created_by_user_id

created_at

Sale (transaction)

id

inventory_item_id

customer_id

sold_at (datetime, default now)

quantity_sold (int, required, >0)

unit_price (decimal(10,2), required)

discount (decimal(10,2), default 0)

tax (decimal(10,2), default 0)

payment_status (paid | unpaid | partial, default paid)

notes (optional)

created_by_user_id

created_at

Policy decisions (filled):

Inventory item is a product definition with quantity tracking (SKU-style).

Partial sales are allowed (decrement quantity).

COGS/profit reporting: basic (last cost, average cost optional later).

5. Pages
Inventory Page

Purpose: find items fast, see on-hand, and act (purchase/sell/edit) without leaving the page.
Primary actions: Add Item, Edit, Quick Purchase, Quick Sale, Archive

List columns (reasonable defaults):

Photo thumbnail

Title (and SKU under it)

Status

Qty on hand

Last unit cost

Last unit price

Updated

Actions (•••)

Filters:

Status

Tag

Location

Vendor (has purchases from)

Customer (has sales to)

Low stock toggle (qty <= reorder_point)

Updated date range

Item detail view: separate route /inventory/:id (better for deep history + sharing links)

Profile Page

Purpose: user identity + preferences + (admin) user management.
Fields:

Name (from Google): editable display name

Email: read-only

Role: read-only (admin can change roles elsewhere)

Preferences:

Currency: USD default

Date format: locale default

Default tax handling: “exclude tax” default

Notification prefs: low-stock email (off by default)

Admin-only section:

Invite users by email

Set roles: Admin / Staff / Read-only

Disable user (soft disable)

Customers Page

Purpose: manage customers and see what they bought.
List columns: Name, Contact, Last Purchase, Total Orders, Total Revenue (optional)
Customer detail: sales ledger (filterable), notes, address/contact
Merge duplicates: optional “merge” tool (admin-only) later

Vendors Page

Purpose: manage vendors and see what you bought.
List columns: Name, Contact, Last Purchase, Total POs, Total Spend (optional)
Vendor detail: purchase ledger (filterable), preferred flag, notes
Preferred vendor: yes (one-click toggle)

6. Add/Edit Inventory Modal (expanded defaults)
Modal layout

Mode: Create | Edit
Tabs: Details, Photos, Purchases, Sales (Edit mode shows Purchases/Sales; Create can start at Details/Photos)
Save behavior: Save, Save & Add Another (Create), Save (Edit)

Photos

Camera enabled: yes on mobile (if available), upload always supported

Max images: 12

Allowed types/size: jpg/png/webp up to 10MB each

Reorder images: yes (drag/drop)

Delete image: soft delete (keeps audit reference)

Details

Title: required, 3–120 chars

Description: plain text (markdown later)

Tags: freeform chips + autocomplete from existing tags

Location: optional

SKU: optional; auto-generate INV-YYYY-#### if blank

Reorder point: default 0

Unit: default each

Status: derived from qty unless archived; allow manual override for archived

Purchases (from Vendors)

UI: subtable with “Add Purchase” button
Fields per row: Vendor, Date, Qty, Unit Cost, Shipping, Tax, Reference #, Notes

Rules (filled):

Adding a purchase increases quantity_on_hand.

Editing purchase rows is allowed, but:

If sales exist after that purchase, still allowed (recalculate qty and enforce non-negative rule; see below).

Returns/cancellations: supported via negative “adjustment purchase” later; for now use an “Inventory Adjustment” action (see Validation).

Sales (to Customers)

UI: subtable with “Add Sale” button
Fields per row: Customer, Date, Qty, Unit Price, Discount, Tax, Payment Status, Notes

Rules (filled):

Adding a sale decreases quantity_on_hand.

Prevent selling more than on-hand: hard block by default (admin can enable “allow negative” later).

Refund/return: v1 uses a “Return” entry (sale with negative qty) or separate “Return” type later; recommended v1: Inventory Adjustment instead.

Validation & UX

Required fields: title; for purchase rows (vendor, qty, unit cost); for sale rows (customer, qty, unit price)

Validation timing: inline + on-submit

Confirm close with unsaved changes: yes

Audit trail events:

item_created, item_updated, item_archived

photo_added, photo_removed, photo_reordered

purchase_added, purchase_updated, purchase_deleted

sale_added, sale_updated, sale_deleted

inventory_adjusted (manual correction)

7. Non-functional requirements (reasonable defaults)

Performance targets:

Inventory list loads < 500ms server time for first page (cached where possible)

Item detail < 800ms including ledger rows (paginate ledger)

Offline support: none (v1)
Storage: S3-compatible object storage for photos + CDN (optional)
Backups & retention: daily DB backups; 30-day retention (configurable)
Logging/audit: immutable audit log table; exportable by admin
Compliance: none specific; follow least-privilege, encrypt at rest, TLS in transit

8. API surface (filled placeholders)

GET /me

GET /inventory?query=&status=&tag=&vendor_id=&customer_id=&low_stock=&page=

GET /inventory/:id

POST /inventory

PUT /inventory/:id

POST /inventory/:id/archive / POST /inventory/:id/unarchive

POST /inventory/:id/photos (multipart or signed upload workflow)

DELETE /photos/:id

POST /inventory/:id/purchases

PUT /purchases/:id

DELETE /purchases/:id

POST /inventory/:id/sales

PUT /sales/:id

DELETE /sales/:id

GET/POST/PUT /vendors...

GET/POST/PUT /customers...

Auth: cookie session with CSRF protection
Authorization (RBAC):

Admin: all

Staff: no user management; cannot hard-delete; can archive

Read-only: GET only

9. Open questions (with recommended defaults)

Inventory semantics: SKU + quantity (recommended)

Quantity required: yes (default 0; purchases/sales adjust)

Taxes/fees: supported as optional fields on transactions (do not force)

Profit reporting: basic (last cost/last price + gross margin per sale later)

Multi-user permissions: RBAC as above

Import/export: CSV export (v1), import later

Mobile-first: responsive UI; camera upload supported