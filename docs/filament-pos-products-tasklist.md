# ✅ Filament POS — “Products & Stock” Task List (for Codex in VS Code)

> You said: fresh Laravel project with **Filament** and **Laravel Boost MCP server** installed.  
> Goal: Build the **Product module** end-to-end with clean, small, completable tasks you can hand to Codex.

---

## How to use this file

1. Work top-to-bottom.  
2. For each task, copy the **“Prompt to Codex”** block into VS Code’s chat and run it.  
3. After Codex finishes, run the suggested commands and check the **Acceptance** items.

---

## 0) Project hygiene

### 0.1 Configure basic app settings ✅
**Prompt to Codex**
```
Edit .env to set:
APP_NAME="Aaliyaa POS"
APP_TIMEZONE=Asia/Colombo
FILESYSTEM_DISK=public
QUEUE_CONNECTION=database
CACHE_DRIVER=file
SESSION_DRIVER=file

Also: create queue and session tables with php artisan queue:table and php artisan session:table, then add their migrations.
```
**Acceptance**
- `.env` updated; migrations for `jobs`, `job_batches`, and `sessions` exist.

---

## 1) Database: core catalogs

### 1.1 Create base migrations (brands, categories, collections, sizes, colors, taxes, locations) ✅
**Prompt to Codex**
```
Create Laravel migrations and Eloquent models (with factories) for:
- brands: id, name (unique), slug, created_at/updated_at.
- categories: id, parent_id nullable FK self, name, slug, created_at/updated_at.
- collections: id, name, start_date nullable, end_date nullable, created_at/updated_at.
- sizes: id, name, sort_order int default 0.
- colors: id, name, hex nullable, sort_order int default 0.
- taxes: id, name, rate decimal(5,2), is_inclusive boolean default false.
- locations: id, name, type enum('store','warehouse'), created_at/updated_at.

Add slugs with unique indexes where relevant. Setup foreign keys and indexes.
```
**Acceptance**
- Migrations & models exist; `php artisan migrate` succeeds.

### 1.2 Seed reference data ✅
**Prompt to Codex**
```
Add seeders for:
- sizes: XS,S,M,L,XL with sort_order.
- colors: Black (#000000), White (#FFFFFF), Red (#FF0000).
- taxes: VAT 15% exclusive; VAT 15% inclusive=false.
- locations: Colombo Store (store), Main Warehouse (warehouse).
- brands: Aaliyaa, Generic.
- categories: Women (parent), Dresses (child of Women).

Register DatabaseSeeder to call these. Ensure idempotent seeding.
```
**Acceptance**
- `php artisan db:seed` populates tables; running twice doesn’t duplicate.

---

## 2) Products & Variants

### 2.1 Migrations for products and variants ✅
**Prompt to Codex**
```
Create migrations & models:
- products: id, name, slug unique, sku_prefix nullable, brand_id FK, category_id FK, collection_id FK nullable, season nullable string, description text nullable, care_instructions text nullable, material_composition string nullable, hs_code string nullable, default_tax_id FK nullable, status enum('draft','active','discontinued') default 'draft', soft deletes, timestamps.
- product_variants: id, product_id FK, sku unique, barcode unique nullable, size_id FK nullable, color_id FK nullable, cost_price decimal(10,2) default 0, mrp decimal(10,2) nullable, selling_price decimal(10,2) default 0, reorder_point int nullable, reorder_qty int nullable, weight_grams int nullable, status enum('active','inactive') default 'active', soft deletes, timestamps.
- product_images: id, product_id FK, variant_id FK nullable, path string, is_primary boolean default false, sort_order int default 0.

Add indexes for frequent lookups (product_id, size_id, color_id, sku).
```
**Acceptance**
- Migrations run; models created with relations stubs.

### 2.2 Model relations & accessors ✅
**Prompt to Codex**
```
In models, implement relations and helpful accessors:
- Product belongsTo Brand/Category/Collection/Tax; hasMany Variants & Images.
- ProductVariant belongsTo Product/Size/Color; hasMany Images.
- Product::primaryImage(): hasOne through the images where is_primary = true.
- ProductVariant::display_name attribute combining product name + size/color chips.
```
**Acceptance**
- Tinker can resolve `$product->variants`, `$variant->product`, `$product->primaryImage`.

### 2.3 SKU & barcode generators (service class) ✅
**Prompt to Codex**
```
Create app/Services/IdentifierService.php with:
- makeSku(Product $product, ?Size $size, ?Color $color): string (use product->sku_prefix or slug + size/color codes).
- makeBarcode(ProductVariant $variant): string (Code128-compatible string; ensure uniqueness).
Call these in model creating events for ProductVariant if null values provided.
```
**Acceptance**
- Creating a variant auto-fills `sku` and `barcode` when absent.

---

## 3) Stock system (multi-location)

### 3.1 Stock tables ✅
**Prompt to Codex**
```
Create migrations & models:
- stock_levels: id, location_id FK, variant_id FK, on_hand int default 0, reserved int default 0; unique(location_id, variant_id).
- stock_movements: id, variant_id FK, location_id FK, quantity int (signed), reason enum('opening','purchase','sale','return','transfer','correction'), reference_type nullable string, reference_id nullable bigint, notes nullable text, created_by FK users nullable, timestamps.

Add simple business method on ProductVariant:
- adjustStock(location_id, quantity, reason, meta array) => updates stock_levels and creates stock_movements within a transaction.
```
**Acceptance**
- Calling adjustStock creates a movement and updates on_hand.

### 3.2 Opening stock command ✅
**Prompt to Codex**
```
Create an artisan command:
php artisan stock:open {variant_id} {location_id} {qty}
It should call adjustStock with reason = 'opening' and guard against duplicates by allowing positive or zero qty only.
```
**Acceptance**
- Running the command updates tables correctly.

---

## 4) Filament Admin

### 4.1 Filament resources for catalogs (simple CRUD) ✅
**Prompt to Codex**
```
Generate Filament Resources for: Brand, Category (with parent select), Collection, Size, Color, Tax, Location.
Each with:
- Table: name columns, search, sort, soft delete if applicable.
- Form: minimal fields with validation; slugs auto-generated where needed.
Group under navigation "Catalog".
```
**Acceptance**
- All show in sidebar; create/edit works.

### 4.2 Filament ProductResource with Variant Repeater ✅
**Prompt to Codex**
```
Create Filament ProductResource:
- Form sections:
  - Main: name (required), brand, category (required), collection, season, material_composition, care_instructions, hs_code, default_tax, status.
  - Media: Image uploader (multiple, reorderable, mark primary).
  - Variants (Repeater): size, color, sku (auto; editable), barcode (generate button), cost_price, mrp, selling_price, reorder_point, reorder_qty, weight_grams, status.
- On save: persist variants; for images, write to storage/public with responsive previews.

Table:
- Columns: name, brand, category, variants_count, total_on_hand (computed), status, updated_at.
- Actions: Edit, View, Quick “Adjust Stock” modal per variant (location + +/- qty + reason).
- Filters: category, brand, status.
Navigation group "Products".
```
**Acceptance**
- Can create a product with variants, upload images, and see computed columns.

### 4.3 Opening Stock modal (Filament Action) ✅
**Prompt to Codex**
```
Within ProductResource table action:
- Add "Opening Stock" modal that loops over variants with inputs [location select, opening qty].
- On submit: calls adjustStock(..., reason='opening') for each non-empty row.
- Show success toast and refresh.
```
**Acceptance**
- Bulk opening stock works; movements logged.

### 4.4 Barcode print action ✅
**Prompt to Codex**
```
Add table action "Print Barcodes":
- Accept selected variants, generate a simple Blade printable view with barcode (using picqer/php-barcode-generator or milon/barcode), sku, product name, size, color, price.
- Open in new tab (print-friendly CSS).
```
**Acceptance**
- Printable page renders with barcodes for selected variants.

---

## 5) Data quality & conveniences

### 5.1 Slug + unique rules
**Prompt to Codex**
```
Ensure unique slugs for brands/categories/collections/products.
Add request validation rules in Filament forms; show inline errors.
```
**Acceptance**
- Duplicate names produce validation messages.

### 5.2 Factories for demo data
**Prompt to Codex**
```
Create factories to generate:
- 10 brands, 5 top-level categories, each with 2 children.
- 12 products each with 3–5 variants from random sizes/colors.
- Random images using placeholders.
- Random stock at 2 locations (run adjustStock with opening reason).
Add a seeder to build this dataset quickly.
```
**Acceptance**
- `php artisan db:seed --class=DemoDataSeeder` yields a clickable demo.

---

## 6) Security & roles

### 6.1 Policies / Spatie permissions
**Prompt to Codex**
```
Install spatie/laravel-permission.
Create roles: admin, manager, cashier.
Permissions: view products, create products, update products, adjust stock, manage catalogs.
Wire Filament pages to check permissions; hide actions if not authorized.
```
**Acceptance**
- Different users see appropriate actions only.

---

## 7) Testing & checks

### 7.1 Pest feature tests
**Prompt to Codex**
```
Add Pest tests:
- Creating product with variants persists all fields.
- adjustStock updates stock_levels and creates stock_movements atomically.
- Opening Stock modal action triggers movements.
- Barcode print route returns 200 and contains expected SKU text.
```
**Acceptance**
- `php artisan test` passes.

### 7.2 Makefiles & scripts
**Prompt to Codex**
```
Add a Makefile with targets:
- make fresh: drop + migrate + seed
- make demo: migrate:fresh + demo seeder
- make test: run tests
Document these in README.
```
**Acceptance**
- `make demo` works end-to-end.

---

## 8) Nice-to-haves (optional)

### 8.1 Attribute system (future-proof)
**Prompt to Codex**
```
Add generic attributes:
- attributes (id, name), attribute_values (id, attribute_id, value)
- variant_attribute_values (variant_id, attribute_value_id) pivot.
Expose a small multiselect on Variant repeater for arbitrary attributes.
```
**Acceptance**
- Variant can hold extra attributes beyond size/color.

### 8.2 Simple dashboard widgets
**Prompt to Codex**
```
Add Filament dashboard widgets:
- Low stock variants (reorder_point > 0 && on_hand <= reorder_point).
- Top categories by variant count.
- Inventory value = sum(cost_price * on_hand) across locations.
```
**Acceptance**
- Widgets render with live counts.

---

## 9) Dev UX with MCP (Boost)

### 9.1 Dev containers & scripts
**Prompt to Codex**
```
Add npm scripts for vite, php scripts for ide-helper (if installed), and a simple task.json for VS Code to run:
- php artisan serve
- php artisan queue:work
- npm run dev
Document how to run three terminals in parallel.
```
**Acceptance**
- One-command developer onboarding documented.

---

## 10) README Quickstart

### 10.1 Author a concise README
**Prompt to Codex**
```
Write README.md with:
- Stack and modules
- Install steps (composer, npm, env, key, storage link)
- Database & demo seeding
- Admin login seeding (email: admin@example.com / password: password)
- Common commands (make targets)
- Notes on printing barcodes and storage/public symlink
```
**Acceptance**
- New dev can get running in <10 minutes.

---

## Commands to run along the way

```
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan test
php artisan serve
npm install && npm run dev
make demo
```

---

## Done criteria

- Can add a product with variants, upload images, set prices.  
- Can open stock by location, adjust stock, and print barcodes.  
- Catalogs manageable via Filament.  
- Basic roles/permissions enforced.  
- Demo data + tests pass.

---
