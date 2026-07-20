# FAZAT — Udhërrëfyesi i Ndërtimit

**Rregulli i vetëm:** nuk kalon në fazën tjetër pa i plotësuar të gjitha kutitë e "E kryer kur".
Jo "pothuajse". Jo "e rregulloj më vonë". Nëse diçka nuk kalon, ndalu dhe rregulloje.

**Shënim:** `PLAN.md` është burimi i vërtetës për skemën dhe vendimet. Ky dokument është radha e punës.

---

## FAZA 0 — Themeli

**Qëllimi:** projekt që ngrihet, lidhet me Supabase, dhe Claude Code e di ku është.

### Hapat manualë (ti, jo Claude Code)

```powershell
laravel new realestate --vue
cd realestate
npm install
composer require spatie/laravel-translatable
composer require tightenco/ziggy
git init
```

Kopjo `PLAN.md` dhe `FAZAT.md` në rrënjë. Krijo projektin në Supabase, merr connection string
(**Session pooler**, port 5432 — jo Transaction pooler, se prish prepared statements).

`.env`:
```
DB_CONNECTION=pgsql
DB_HOST=aws-0-eu-central-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.xxxxxxxx
DB_PASSWORD=...

FILESYSTEM_DISK=supabase
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=eu-central-1
AWS_BUCKET=properties
AWS_ENDPOINT=https://xxxxxxxx.supabase.co/storage/v1/s3
AWS_USE_PATH_STYLE_ENDPOINT=true
```

### Prompti

```
This is a fresh Laravel 12 + Vue 3 + Inertia + Tailwind project. Read PLAN.md and FAZAT.md
in the root — PLAN.md is the source of truth for the schema and all architectural
decisions.

Tasks for this phase only:
1. Write CLAUDE.md documenting: the stack, the conventions (service layer for business
   logic, FormRequests for validation, policies for authorization, always write feature
   tests, prices are integer cents, nothing about the agency is ever hardcoded), and the
   project structure.
2. Configure the Supabase S3 disk in config/filesystems.php named "supabase".
3. Verify the PostgreSQL connection works.
4. Set up Pest for testing.

Do NOT write any feature code, migrations, or models. Ask me about anything ambiguous in
PLAN.md before you finish.
```

### E kryer kur
- [ ] `php artisan migrate` kalon pa gabim në Supabase
- [ ] `npm run dev` ngre serverin dhe faqja e Laravel-it hapet
- [ ] `php artisan tinker` → `Storage::disk('supabase')->put('test.txt','ok')` → skedari duket në Supabase
- [ ] `CLAUDE.md` ekziston dhe i përmban konventat
- [ ] `php artisan test` kalon
- [ ] `.env` **nuk** është në git

**Commit:** `chore: project scaffold, supabase connection, conventions`

---

## FAZA 1 — Skema dhe të dhënat

**Qëllimi:** bazë e plotë me 200 prona realiste. Pa UI fare.

> Kjo është faza që të tundon ta anashkalosh. Mos. Pa të dhëna të mira, faza 5 nuk testohet dot.

### Prompti

```
Implement section 2 of PLAN.md completely: migrations, models, relationships, casts,
factories, seeders.

Non-negotiable details:
- Prices are bigint cents. Write a Price cast (Casts/MoneyCast) — never store floats.
- Translatable fields (title, description, name, bio, content) are JSON columns using
  spatie/laravel-translatable with locales sq, en, de.
- properties.is_exclusive is a boolean, SEPARATE from listing_type (sale|rent).
- Add every index listed in the schema — all of them.
- Property model: booted() hook that (a) generates reference_code as PRO-{id padded to 4}
  after create, (b) generates slug from the sq title, (c) writes a
  property_price_histories row on price change.
- Setting model: a static all-as-array method, cached forever, invalidated on saved/deleted.
- lead_notes is polymorphic (notable_type, notable_id).

Seeders:
- LocationSeeder: all Kosovo municipalities as type=municipality, plus Prishtina
  neighborhoods as type=neighborhood with parent_id and real lat/lng (Dardania, Ulpiana,
  Mati 1, Bregu i Diellit, Kodra e Diellit, Arbëria, Sunny Hill, Lakrishte, Qafa,
  Veternik, Kalabria, Fushë Kosovë, Çagllavicë).
- FeatureSeeder: both groups exactly as listed in PLAN.md.
- SettingSeeder: every key from PLAN.md with sensible placeholder values.
- UserSeeder: 1 admin + 3 agents with Kosovo phone numbers.
- PropertySeeder: 200 properties. Realistic: apartments €70k-180k, houses €150k-400k,
  land €15k-90k, rent €250-900/month. Varied categories, statuses (mostly published,
  some draft/sold/rented), 3-8 images each with placeholder URLs, random features,
  lat/lng scattered around the assigned neighborhood, spread published_at over 8 months.

No controllers, no routes, no UI. When done, run the seeders and show me a table of
10 sample properties with their agent, location, and formatted price.
```

### E kryer kur
- [ ] `php artisan migrate:fresh --seed` kalon i pastër nga zero
- [ ] `Property::count()` → 200
- [ ] `Property::first()->price` kthen cent (integer), jo float
- [ ] `Property::first()->reference_code` → `PRO-0001`
- [ ] `$p->update(['price' => 5000000])` → krijon rresht në `property_price_histories`
- [ ] `Property::first()->setLocale('de')->title` punon
- [ ] Në Supabase → Indexes: i sheh të gjithë indekset e PLAN.md
- [ ] `Location::where('type','neighborhood')->count()` ≥ 12

**Commit:** `feat: database schema, models, seeders with 200 realistic properties`

---

## FAZA 2 — Auth, role, policies

**Qëllimi:** matrica e lejeve e zbatuar dhe e testuar. Ende pa UI të dashboard-it.

### Prompti

```
Implement section 3 of PLAN.md — authentication and authorization.

- Login only. DELETE the public registration route, controller, and page entirely.
  Accounts are created by the admin.
- Add a role enum cast (admin|agent) and helpers: $user->isAdmin(), $user->isAgent().
- EnsureUserIsAdmin middleware, aliased as 'admin'.
- Dashboard route group: auth + verified.

PropertyPolicy:
  viewAny  = any authenticated
  view     = any authenticated
  create   = any authenticated
  update   = admin OR property.agent_id === user.id
  delete   = admin OR property.agent_id === user.id
  publish  = same as update

LeadPolicy — shared by ContactMessage, PropertyOffer, PropertyRequest (register all three
in AuthServiceProvider pointing to it):
  viewAny  = any authenticated
  view     = admin OR lead.assigned_to === user.id
  update   = admin OR lead.assigned_to === user.id
  assign   = admin ONLY
  delete   = admin ONLY

UserPolicy: everything admin only. An admin cannot delete themselves.

Write a Pest feature test for EVERY row of the permission matrix in PLAN.md section 3 —
both the allowed case and the denied case. That is roughly 30 tests. Write all of them.
```

### E kryer kur
- [ ] `/register` kthen 404
- [ ] Agjenti provon të editojë pronën e një agjenti tjetër → 403
- [ ] Agjenti provon `/dashboard/settings` → 403
- [ ] Agjenti provon `/dashboard/agents` → 403
- [ ] Admini editon çdo pronë → 200
- [ ] Agjenti sheh lead-in e caktuar për vete → 200; atë të tjetrit → 403
- [ ] Agjenti provon të caktojë një lead → 403
- [ ] `php artisan test` → të gjitha jeshile, ~30 teste politikash

**Commit:** `feat: auth, roles, policies with full permission test coverage`

---

## FAZA 3 — Settings + shell i dashboard-it

**Qëllimi:** dashboard që hapet, sidebar që di kush je, cilësime që ndërrojnë brandin.

### Prompti

```
Build the settings system and the dashboard shell.

Settings page (/dashboard/settings, admin only) with tabs:
- Branding: agency_name, logo, logo_dark, favicon, primary_color (color picker),
  watermark_enabled
- Contact: phone, whatsapp, email, address, office_lat, office_lng (with a small Leaflet
  map to drop the pin)
- Social: facebook, instagram, tiktok, linkedin, youtube, app_store_url, play_store_url
- Content: about_content, terms_content, privacy_content, faq_content — a rich text editor
  with a language tab (SQ / EN / DE) for each

Image uploads go to the supabase disk. Saving invalidates the settings cache.

Dashboard shell:
- Sidebar: Përmbledhje, Pronat, Inbox (Mesazhe / Oferta / Kërkesa), Agjentët, Cilësimet.
  Agents do NOT see Agjentët or Cilësimet — hide via @can, not CSS.
- Badge counts next to each Inbox item: for admin = count where status='new';
  for agent = count where status='new' AND assigned_to = me. Share these via a single
  Inertia shared prop, computed in one query per type — not N queries.
- Overview page: 4 stat cards (my properties / published / new leads / this month's
  leads — scoped by role) plus the 5 most recent leads.

Settings must be available to every Vue page via an Inertia shared prop, read from the
cached array — one cache hit per request, not one per key.
```

### E kryer kur
- [ ] Ndërrimi i `agency_name` te cilësimet → ndryshon në sidebar dhe në footer
- [ ] Ngarkimi i logos → shfaqet, ruhet në Supabase
- [ ] `primary_color` → ndryshon ngjyrën e butonave (CSS variable)
- [ ] Agjenti nuk i sheh linqet Agjentët/Cilësimet në sidebar
- [ ] Badge-at tregojnë numra të saktë për rolin
- [ ] Laravel Debugbar: faqja e dashboard-it < 15 query
- [ ] Asnjëherë `config('app.name')` ose emër i fiksuar — vetëm nga `settings`

**Commit:** `feat: settings system and dashboard shell with role-aware sidebar`

---

## FAZA 4A — CRUD i pronave (pa foto)

**Qëllimi:** krijim/editim/fshirje prone me të gjitha fushat. Fotot vijnë veç.

### Prompti

```
Build the property CRUD in the dashboard — WITHOUT image upload (that is the next phase).

/dashboard/properties:
- Table with: primary image thumb, reference_code, title, category badge, listing_type
  badge, exclusive badge, price, location, agent, status badge, views.
- Filters: status, category, listing_type, agent (admin only), search by ref code or title.
- Agents see all properties, but Edit/Delete buttons appear only on their own (@can).
- Sortable columns. Paginated, 20 per page.

Create/Edit form, sectioned:
1. Basics: listing_type toggle, is_exclusive checkbox, category, status
2. Content: title + description with SQ/EN/DE language tabs (SQ required, others optional)
3. Price: price input in EUROS shown to the user, converted to cents on save
   (write a reusable MoneyInput.vue), price_negotiable
4. Details: surface_m2, land_surface_m2, bedrooms, bathrooms, floor, total_floors,
   year_built, parking_spaces — show/hide fields per category (land has no bedrooms)
5. Location: municipality select → neighborhood select (dependent), address_line, and a
   Leaflet map where the agent drags a pin to set lat/lng. Pin defaults to the selected
   neighborhood centre.
6. Features: checkbox groups, infrastructure and furnishing
7. Documents: has_possession_sheet (yes/no/unknown), document_type (notary/lawyer/none)
8. SEO: meta_title, meta_description (collapsed by default)

Architecture:
- StorePropertyRequest / UpdatePropertyRequest for validation
- PropertyService for create/update logic
- On create, agent_id = auth()->id() automatically. Only admin may reassign it.
- Publishing sets published_at.
- Authorize every action with PropertyPolicy.

Feature tests: create, update, delete, agent cannot edit another agent's property,
price converts euros→cents correctly, agent_id is auto-assigned.
```

### E kryer kur
- [ ] Krijoj pronë → `PRO-0201` gjenerohet vetë
- [ ] Fut `114000` € → në bazë ruhet `11400000`
- [ ] Ndërroj çmimin → `property_price_histories` merr rresht të ri
- [ ] Zgjedh "Land" → fushat e dhomave zhduken
- [ ] Zgjedh komunën → lagjet filtrohen; pin-i shkon te qendra e lagjes
- [ ] Agjenti krijon pronë → `agent_id` = ai, pa e prekur
- [ ] Agjenti e sheh pronën e tjetrit por pa buton Edito
- [ ] Titulli në DE ruhet dhe kthehet saktë
- [ ] Testet jeshile

**Commit:** `feat: property CRUD with translations, location picker, price history`

---

## FAZA 4B — Fotot

**Qëllimi:** ngarkim i shumëfishtë, rirenditje, primare, watermark, thumbnails.

### Prompti

```
Add image management to the property form.

- Multi-file upload with drag & drop, plus a click-to-browse fallback.
- Validate: jpeg/png/webp ONLY. Explicitly block SVG. Max 8MB each, max 30 per property.
  Validate the real mime type, not the extension.
- On upload, via an ImageService:
  1. Resize the original to max 1920px wide, convert to webp, quality 85
  2. Generate a 400px thumbnail
  3. If settings.watermark_enabled, composite the agency logo bottom-right at 40% opacity
     on the large version only, not the thumbnail
  4. Store both on the supabase disk under properties/{property_id}/
- Drag to reorder (vuedraggable), persisting sort_order.
- Click a star to set is_primary — exactly one per property, enforced in the model.
- Delete an image: confirm, remove from disk and DB, reassign primary if it was primary.
- Uploads work on the create form too (before the property exists) — use a temp session
  key and attach on save.
- Show upload progress per file.

Use intervention/image v3. Feature tests: upload, SVG rejected, reorder persists, only one
primary, delete cleans up the disk.
```

### E kryer kur
- [ ] Ngarkoj 5 foto njëherësh → të gjitha duken me progres
- [ ] Provoj `.svg` → refuzohet me mesazh të qartë
- [ ] Ndërroj `.svg` në `.jpg` dhe provoj → prapë refuzohet (mime check)
- [ ] Tërheq foton e tretë në vend të parë → ruhet pas refresh
- [ ] Vendos primare → yll i vetëm, të tjerat çlirohen
- [ ] Watermark duket në të madhen, jo në thumbnail
- [ ] Fshij foton primare → një tjetër bëhet primare vetë
- [ ] Në Supabase: `properties/201/` përmban webp + thumb

**Commit:** `feat: property image management with watermark and reordering`

---

## FAZA 5 — Listimi publik dhe filtrat

**Qëllimi:** faqja që e vendos projektin. Filtra të shpejtë, URL që i ruan.

> Kjo fazë meriton dyfishin e kohës që mendon. Nëse kjo punon mirë, gjithçka duket profesionale.

### Prompti

```
Build the public property listing page per section 5 of PLAN.md. This is the most
important page in the entire project — take your time and do it properly.

PropertyFilter class (app/Filters/PropertyFilter.php):
- Translates query params into Eloquent scopes. Every filter composes with every other.
- All filtering server-side. Only status=published is ever visible publicly.
- Params: type (sale|rent), exclusive (bool), category[], price_min, price_max,
  surface_min, surface_max, location (id), bedrooms, possession (with|without),
  documents (notary|lawyer), furnishing[] (multi, matches ANY), sort, page

Filter bar UI:
  Row 1: [Për shitje] [Me qira] [Ekskluzive] — segmented control
  Row 2: category chips with icons — multi-select
  Row 3: Price range · Surface range · Location (searchable, municipality→neighborhood) ·
         [Më shumë ▾]
  "Më shumë" panel: possession sheet, documents, bedrooms (All/1/2/3/4/5+), furnishing
  Active filters shown as removable chips. A "Clear all" button.

Behaviour — this is the part that matters:
- Filter state lives entirely in the URL query string. Shareable. Back button works.
- Inertia router.get with only: ['properties'], preserveState: true, preserveScroll: true
  — filtering NEVER causes a full page reload.
- Debounce ranges and search at 300ms. Instant for chips and toggles.
- Pagination preserves every filter.
- Sort: newest, price asc, price desc, surface desc.
- Result count always visible: "247 prona"
- Loading state: skeleton cards, not a spinner.
- Design the empty state properly with a "clear filters" action — users WILL hit it.

Property card: primary image with a lazy loader, exclusive ribbon if is_exclusive,
listing_type badge, price, title, location, bedrooms/bathrooms/surface icons,
reference_code, favourite heart.

Favourites: localStorage only, no auth, no table. A useFavourites composable.
The heart fills instantly (optimistic). A /favorites page reads the IDs and fetches them.

Performance:
- Eager load agent, primaryImage, location. Zero N+1.
- Write a test that asserts the query count stays constant whether 1 or 50 properties
  are returned.
- Cache the filter facets (locations, features) — invalidate on save.

Feature tests: each filter in isolation, five combinations, URL state survives pagination,
draft properties never appear publicly, query count assertion.
```

### E kryer kur
- [ ] Klikoj një filtër → **pa reload të faqes**, vetëm rezultatet ndryshojnë (kontrollo Network tab)
- [ ] Kopjoj URL-në, e hap në dritare inkognito → të njëjtat rezultate
- [ ] Butoni Back kthen filtrin e kaluar
- [ ] Kombinoj: Me qira + Apartment + €300-600 + Dardani + 2 dhoma → rezultate të sakta
- [ ] Faqja 3 → filtrat mbeten
- [ ] Prona `draft` nuk duket kurrë publikisht
- [ ] Debugbar: numri i query-ve i njëjtë me 1 dhe me 50 prona
- [ ] Favorit → refresh → hearti mbetet i mbushur
- [ ] Empty state duket i dizajnuar, jo i harruar
- [ ] Në celular: filtrat në bottom sheet, të përdorshëm me një dorë

**Commit:** `feat: public listing with composable filters and URL state`

---

## FAZA 6A — Faqja e detajeve

### Prompti

```
Build the public property detail page at /properties/{slug}.

Layout — left column:
- Image gallery: large primary, thumbnail strip, click for a lightbox with keyboard
  arrows and swipe on touch. Preload the next image.
- Title, location, reference_code, published date, views count.
- Price. If price_negotiable, show a "negotiable" tag.
- Specs grid: surface, land surface, bedrooms, bathrooms, floor/total, year built,
  parking — only render what exists for that category.
- Description (in the active locale, falling back to sq).
- "Property characteristics and information": features in two columns with check icons,
  grouped infrastructure / furnishing. Include possession sheet and document type here.
- Price history chart (ApexCharts area, green) — ONLY if 2+ history rows exist, otherwise
  hide the whole section.
- Location: Leaflet map with the pin. Show the neighbourhood, never the exact street for
  privacy — use a circle of ~200m radius instead of a precise marker.

Right column, sticky:
- Agent card: photo, name, phone, "Call" and "WhatsApp" buttons. The WhatsApp link is
  prefilled: "Përshëndetje, më intereson prona {reference_code} - {title}"
- Contact form (the design in the reference screenshot): Full Name, Phone, Message.
  Posts to contact_messages with property_id. Rate limited 3/hour per IP. Honeypot field.
  On success, an inline confirmation — not an alert.

Below: "Prona të ngjashme" — 3 published properties, same category, same municipality,
price within ±25%, excluding this one. If fewer than 3 match, relax to same category only.

Also:
- Increment views_count once per session per property, not on every load.
- Open Graph + Twitter meta tags with the primary image.
- JSON-LD RealEstateListing schema.
- 404 for non-published properties (unless the viewer is authenticated).

Feature tests: contact message stores with property_id and auto-assigns to the property's
agent, rate limit triggers, draft returns 404 for guests, similar properties logic.
```

### E kryer kur
- [ ] Galeria: lightbox hapet, shigjetat punojnë, swipe në celular
- [ ] WhatsApp → hap chat me mesazhin e parambushur me ref code
- [ ] Dërgoj mesazh → shfaqet në Inbox **i caktuar te agjenti i pronës**, pa ndërhyrje
- [ ] Dërgoj 4 mesazhe brenda një ore → i katërti bllokohet
- [ ] Prona me një çmim të vetëm → seksioni i historikut fshihet fare
- [ ] Prona `draft` si vizitor → 404
- [ ] Ndaj linkun në Facebook → foto dhe titull i saktë
- [ ] Rifreskoj 5 herë → `views_count` rritet një herë
- [ ] Harta tregon zonën, jo derën e saktë

**Commit:** `feat: property detail page with gallery, map, contact and similar properties`

---

## FAZA 6B — Kalkulatori i financimit

### Prompti

```
Add the financing calculator to the property detail page (sale listings only — hide it
for rent).

Inputs with +/- steppers, exactly like the reference design:
- Down Payment (€), default 20% of the price
- Loan Duration (years), default 10, range 1-30
- Monthly Income (€), default 1000
- Annual Interest Rate (%), default 5, step 0.5

Outputs, live, no submit button:
- Loan Amount = price - down payment
- Down Payment as a % of price
- Monthly Instalment (standard annuity formula)
- Debt burden = instalment / monthly income, shown as a badge:
    <30%  → green  "Low Risk"
    30-45% → amber "Moderate Risk"
    >45%  → red    "High Risk — most banks may not approve"

Pure frontend, zero backend, zero API. Vue computed properties. Debounce the recalculation
at 150ms so the steppers feel smooth.

Include the disclaimer text: informational estimate only.
Labels must come from the translation files — this appears in SQ/EN/DE.
```

### E kryer kur
- [ ] Ndryshoj parapagimin → kësti rillogaritet menjëherë
- [ ] Kontrolloj formulën me një kalkulator kredie online → e njëjta shifër
- [ ] Ngarkesa > 45% → badge i kuq
- [ ] Prona me qira → kalkulatori nuk shfaqet fare
- [ ] Parapagim = çmimi i plotë → nuk pjesëton me zero, tregon €0

**Commit:** `feat: financing calculator`

---

## FAZA 7A — Offer + Request (format publike)

### Prompti

```
Build the two public lead forms, matching the reference designs.

/offer-property — "Offer Your Property":
  First Name, Last Name, Phone (+383 prefix with a flag), Type toggle (For Sale / For
  Rent), Category chips with icons (House, Apartment, Office, Store, Land, Warehouse,
  Object), Location select, Surface (m²), Introductory price (€ → cents).
  Below the form: "What happens after submitting the form?" explainer card.

/create-request — "Create your request":
  Name, Last Name, Phone, Type toggle (Buying / Rent), Category chips, Location, Budget,
  Surface with a unit select (m² / ari / ha) and min/max, More details textarea.
  Below: "What happens after sending the request?" explainer card.

Both:
- FormRequest validation. Kosovo phone regex: /^(\+383|0)4[3-9][0-9]{6}$/
- Rate limit 3/hour per IP. Honeypot + a minimum time-on-form check.
- status = 'new', assigned_to = null (these go into the unassigned queue).
- Success = an inline confirmation state replacing the form, not a redirect.
- All labels from translation files.
- Notify admins by email of a new lead (queued, not synchronous).

Feature tests: valid submission stores correctly, invalid phone rejected, rate limit,
honeypot catches bots, assigned_to is null.
```

### E kryer kur
- [ ] Dërgoj ofertë → rresht në `property_offers`, `status=new`, `assigned_to=null`
- [ ] Numër i pavlefshëm (`044123`) → gabim i qartë
- [ ] `+38344123456` dhe `044123456` → të dyja pranohen
- [ ] Mbushi honeypot-in me JS → refuzohet në heshtje
- [ ] Dërgoj 4 herë → i katërti bllokohet
- [ ] Çmimi ruhet në cent
- [ ] Admini merr email (kontrollo Mailtrap/log)

**Commit:** `feat: public offer and request forms`

---

## FAZA 7B — Inbox (zemra e dashboard-it)

**Qëllimi:** ku mbyllet zinxhiri. Faza që e bën sistemin mjet pune.

### Prompti

```
Build the dashboard Inbox — one shared component, three tabs (Mesazhe / Oferta /
Kërkesa). This is where the whole system comes together.

Shared behaviour across all three:
- List: name, phone, snippet, status badge, assigned agent avatar, relative time.
  Unread/new rows visually distinct.
- Filters: status, assigned agent (admin only), date range, search by name or phone.
- Scoping: admin sees all; agent sees ONLY assigned_to = me. Enforced in the query AND
  by LeadPolicy — not just hidden in the UI.
- Detail panel: full data, status dropdown, assign-to-agent dropdown (ADMIN ONLY),
  notes thread (lead_notes polymorphic, newest first, with author and time), Call and
  WhatsApp buttons with the phone prefilled.
- Sidebar badges update after any status change.

Tab-specific:

MESAZHE (contact_messages):
- If property_id exists, render the property card next to the message — image, ref code,
  price, link. The agent must see WHICH property they are being asked about.
- These arrive pre-assigned (property.agent_id). Admin may still reassign.

OFERTA (property_offers):
- Unassigned queue at the top for admins: "3 oferta pa agjent" with a quick assign.
- The key feature: a "Krijo pronë nga kjo ofertë" button. It opens the property create
  form prefilled with listing_type, category, location_id, surface_m2, price (from
  asking_price), and a generated title like "{Category} në {Location}". On save it sets
  offer.converted_property_id = the new property, offer.status = 'converted', and
  property.agent_id = the agent who converted it. Then it redirects to the new property's
  edit page so they can add photos.
- Converted offers show a link to the resulting property.

KËRKESA (property_requests):
- Below the request, automatically show matching published properties:
  same category AND same location (or same municipality if the request names a
  neighbourhood) AND price <= budget_max AND surface between surface_min and surface_max.
  Order by best price fit. Limit 6.
- Each match has a "Send via WhatsApp" button that opens WhatsApp to the requester's
  number with a prefilled message containing the property link and ref code.
- If zero matches, say so clearly and suggest relaxing the criteria.

Feature tests: agent sees only their own leads, agent cannot assign, the full conversion
flow (offer → property → converted status → correct agent_id), request matching returns
correct properties, notes save with the right author.
```

### E kryer kur
- [ ] Agjenti hap Inbox → sheh vetëm të vetat; me URL të drejtpërdrejtë te lead-i i tjetrit → 403
- [ ] Agjenti nuk e ka dropdown-in e caktimit
- [ ] Admini cakton ofertën te Arbeni → i shfaqet Arbenit menjëherë
- [ ] "Krijo pronë nga oferta" → forma vjen e mbushur saktë
- [ ] Pas ruajtjes: `converted_property_id` i mbushur, `status=converted`, `agent_id` = ai që e konvertoi
- [ ] Mesazhi për `PRO-1042` → karta e banesës duket pranë tekstit
- [ ] Kërkesa "apartament, Dardani, deri €90k, 50-70m²" → nxjerr përputhje të sakta
- [ ] WhatsApp → hapet me linkun e pronës brenda
- [ ] Shënimi ruhet me autorin e saktë
- [ ] Ndryshoj statusin → badge në sidebar ulet

**Commit:** `feat: unified inbox with lead assignment, offer conversion and request matching`

---

## FAZA 8 — Shumëgjuhësia

### Prompti

```
Implement full SQ / EN / DE localisation.

- Locale in the URL: /properties (sq, default, no prefix), /en/properties, /de/properties.
- SetLocale middleware reading the route prefix, falling back to a cookie, then to sq.
- Language switcher in the header (flags + names), switching to the SAME page in the new
  locale — never dumping the user on the homepage.
- Translate EVERY UI string into lang/sq.json, lang/en.json, lang/de.json. No hardcoded
  text anywhere in any Vue file. Grep for it and fix what you find.
- Translatable model content already uses spatie/laravel-translatable — fall back to sq
  when a locale is empty.
- Localise formats: prices (€114,000 / 114.000 €), dates, numbers.
- hreflang alternate tags on every public page, plus x-default.
- The sitemap includes all three locales.
- The dashboard stays in Albanian only — do not translate it. That is deliberate.

Then audit: search all .vue files for hardcoded user-facing Albanian or English strings and
report what you find before fixing.
```

### E kryer kur
- [ ] Ndërroj në DE në faqen e detajeve → mbetem në atë pronë, në gjermanisht
- [ ] Prona pa përshkrim në DE → tregon shqipen, jo bosh
- [ ] Grep në `.vue` për tekst të fiksuar → zero
- [ ] `/en/properties` → filtrat punojnë njësoj
- [ ] Kodi burimor tregon `hreflang` për sq/en/de
- [ ] Formatimi i çmimit i saktë për secilën gjuhë

**Commit:** `feat: SQ/EN/DE localisation with URL prefixes and hreflang`

---

## FAZA 9 — Dizajni

**Qëllimi:** kalim i veçantë, vetëm estetikë. Zero funksionalitet i ri.

> Mos e bëj më herët. Dizajni gjatë ndërtimit del generic, gjithmonë.

Përpara promptit, vendos vetë drejtimin. Shembull: *warm minimal, tipografi editoriale,
hapësirë e bollshme, foto në plan të parë, çmimi si element hero*.

### Prompti

```
Design pass only. Do NOT change any functionality, routes, or queries. Visual layer only.

Direction: [SHKRUAJ DREJTIMIN TËND KËTU — jini specifik]

1. Establish a design system first: type scale, spacing scale, colour tokens driven by
   settings.primary_color, radii, shadows, transitions. Put it in tailwind.config and CSS
   variables. Show it to me before touching any page.
2. Then apply it page by page, in this order: property card → listing → detail → homepage
   → forms → dashboard.
3. Mobile-first. Test at 375px before anything else.
4. Micro-interactions: hover lifts on cards, smooth filter transitions, skeleton loaders.
   Subtle. If it draws attention to itself, it is wrong.
5. Accessibility: focus rings, 4.5:1 contrast, alt text, aria labels on icon buttons.

Stop after step 1 and show me the design system. Do not proceed until I approve.
```

### E kryer kur
- [ ] Sistemi i dizajnit i miratuar prej teje para se të prekej ndonjë faqe
- [ ] Në 375px gjithçka përdoret me një dorë
- [ ] Ndërrimi i `primary_color` në cilësime → e gjithë faqja ndryshon
- [ ] Lighthouse Accessibility ≥ 95
- [ ] Tab nëpër faqe → focus gjithmonë i dukshëm
- [ ] Nuk duket si template

**Commit:** `style: design system and visual pass`

---

## FAZA 10 — Hardening + deploy

### Prompti

```
Security, performance and deployment hardening. Report your findings FIRST as a numbered
list with severity. Do not fix anything until I approve each item.

Audit and then fix:
1. Rate limits: contact 3/h, offer 3/h, request 3/h, login 5/min per IP+email.
2. SecurityHeaders middleware: CSP, X-Frame-Options, X-Content-Type-Options,
   Referrer-Policy, Permissions-Policy.
3. Ziggy: expose ONLY public routes to guests — never dashboard routes.
4. N+1 audit across every page. Add Laravel Strict Mode (preventLazyLoading) in non-prod.
5. Cache: settings, filter facets, homepage featured. Correct invalidation on save. Prove
   it with a test.
6. Mass assignment: audit every $fillable. agent_id must never be mass-assignable from
   agent input.
7. File uploads: mime validation, no SVG, size limits, randomised filenames.
8. IDOR sweep: try to access every dashboard resource by ID as the wrong agent.
9. Images: responsive srcset, lazy loading, correct dimensions to stop layout shift.
10. sitemap.xml (all locales), robots.txt, 404 and 500 pages.
11. Ensure APP_DEBUG=false, HTTPS-only cookies, and secure session config in production.
12. Database: verify every index in PLAN.md exists. EXPLAIN the listing query.
13. CI: run the Pest feature suite against a real Postgres database, not just SQLite.
    SQLite silently tolerates bogus double-quoted identifiers in a SELECT list (it falls
    back to treating them as string literals instead of erroring) — a column-vs-relation
    typo like `'property:id,slug,primaryImage'` (primaryImage is a hasOne, not a column)
    passes every SQLite test and only breaks on Postgres in production. Wire a Postgres
    service into CI and point the test run at it so this class of bug is caught pre-merge.

Target: Lighthouse Performance ≥ 90 on the listing page.
```

### E kryer kur
- [ ] Provoj IDOR: `/dashboard/properties/{id_i_tjetrit}/edit` → 403
- [ ] Ziggy si vizitor → zero route të dashboard-it në burim
- [ ] Strict mode aktiv → asnjë lazy loading exception gjatë klikimit të gjithë faqes
- [ ] Lighthouse Performance ≥ 90, SEO ≥ 95
- [ ] `/sitemap.xml` përmban të tri gjuhët
- [ ] Header-at e sigurisë duken në Network tab
- [ ] `APP_DEBUG=false` në prod, faqja 500 e dizajnuar
- [ ] `php artisan test` → gjithçka jeshile
- [ ] CI e ekzekuton test suite-in kundër Postgres (jo vetëm SQLite)
- [ ] Deploy i suksesshëm, migrimet kaluan, cilësimet u mbushën

**Commit:** `chore: security hardening, performance, deploy config`

---

## Si të punosh me Claude Code

**Para çdo faze të madhe (4A, 5, 7B):**
```
Read PLAN.md and FAZAT.md. Before writing any code for [FAZA X], propose your approach:
file structure, class responsibilities, and the tricky parts you foresee. Do not implement
anything yet.
```
Lexoje propozimin. Korrigjoje. Pastaj thuaj `implement`.

**Kur diçka del keq:** mos e rregullo me prompte të reja mbi kod të keq. `git checkout .`,
ripiqe promptin me kufizim më të qartë, dhe fillo nga fillimi. Është më shpejt. Gjithmonë.

**Kurrë mos i jep dy faza njëherësh.** Konteksti hollohet dhe cilësia bie ndjeshëm.

**Pas çdo faze:** `php artisan test` → kalo listën "E kryer kur" me dorë → commit.
