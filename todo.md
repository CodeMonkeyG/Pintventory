# Pintventory Roadmap & TODO

## 📂 1. Data Mobility & Onboarding (CURRENT PHASE)
- [ ] **CSV/Excel Import Tool**
    - [ ] Backend: Create import endpoint with validation.
    - [ ] Frontend: Build import wizard with field mapping (Map CSV columns to InventoryItem fields).
    - [ ] Support for bulk photo association via URLs or naming conventions.
- [ ] **Bulk Actions**
    - [ ] Frontend: Add checkboxes to Inventory list view.
    - [ ] Frontend: Implement "Bulk Action" menu (Change Location, Delete, Add Tags, Archive).
    - [ ] Backend: Create batch update/delete endpoints.

## 🏷️ 2. Physical-to-Digital Bridge
- [ ] **QR/Barcode Generation**
    - [ ] Generate unique QR codes for `StorageLocation` and `InventoryItem`.
    - [ ] "Print Label" functionality (Thermal printer compatible CSS/Layout).
- [ ] **Integrated Scanner**
    - [ ] Unified scanner that detects UPC (Barcodes) and Pintventory QR codes.
    - [ ] Logic to handle "Existing" barcodes (lookup by UPC/EAN).
    - [ ] Scan-to-Action: Scan a location QR to "Move Items" or "View Contents".

## 📊 3. Financial Intelligence
- [ ] **Insights Dashboard**
    - [ ] Net Profit calculation (Revenue - Cost - Fees).
    - [ ] Sales volume by category/tag.
- [ ] **COGS Reporting**
    - [ ] Value of current "In Stock" inventory for tax/accounting.
- [ ] **Export Enhancements**
    - [ ] QuickBooks/Xero compatible CSV formats.

## 🤖 4. Automated Workflow & Alerts
- [ ] **Proactive Notifications**
    - [ ] In-app and Email alerts for "Low Stock" items.
    - [ ] Daily/Weekly summary reports for workspace owners.
- [ ] **Activity Audit Trail**
    - [ ] Detailed "History" tab for every item/location showing who changed what.

## 🔗 5. Deep Platform Integration
- [ ] **Marketplace "Draft" Push**
    - [ ] eBay API Integration: Push AI analysis directly to a draft listing.
    - [ ] Etsy API Integration: Create draft listings from scan results.
- [ ] **Shipping Labels**
    - [ ] Integration with Shippo/PirateShip for label generation from Sales.

## 📱 6. Mobile & Offline Optimization
- [ ] **Offline First (PWA)**
    - [ ] Service Worker for offline access.
    - [ ] Background sync for "Snap-and-Wait" uploads in low-signal areas.
