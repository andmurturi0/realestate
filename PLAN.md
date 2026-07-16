# Plani i Projektit — Portal Patundshmërie (Single-Agency)

**Stack:** Laravel 12 · Vue 3 · Inertia.js · Tailwind · PostgreSQL (Supabase) · Supabase S3 (foto)
**Model:** Single-tenant. Një instalim = një agjenci. I shitshëm përmes `settings`, jo përmes forkut.
**Role:** `admin`, `agent`. Asgjë tjetër.

---

## 1. Vendimet arkitekturore (të fiksuara)

| Vendim | Zgjedhja | Arsyeja |
|---|---|---|
| Tenancy | Single-tenant | Një agjenci për instalim. Pa `agency_id`, pa scope global. |
| Role | `admin` + `agent` | Kushton një kolonë + një policy. Shtimi më vonë kushton rishkrim. |
| Exclusive | `is_exclusive` boolean, jo `listing_type` | Prona është *sale* OSE *rent*, dhe veçmas mund të jetë ekskluzive. |
| Çmimet | Integer, cent (€114,000 → `11400000`) | Pa float. Kurrë. |
| Branding | Tabela `settings` | Zero vlera të fiksuara në kod. Ky rregull e mban të shitshëm. |
| Favorites | localStorage (guest) | Publiku nuk logohet. Pa tabelë, pa auth. |
| Gjuhët | SQ (default), EN, DE | JSON columns për përmbajtje, PHP lang files për UI. |
| Harta | Leaflet + OpenStreetMap | Pa API key, pa faturë. |
| Geocoding | Pin manual nga agjenti | Kosova s'ka adresa të standardizuara. Automatiku dështon. |

---

## 2. Skema e bazës

### `users`
```
id, name, email, password, role (enum: admin|agent),
phone, whatsapp, avatar_path, bio (json: sq/en/de),
is_active (bool), timestamps
```

### `settings` — tabelë key/value, një rresht për çelës
```
id, key (unique), value (json), timestamps
```
Çelësat: `agency_name`, `logo_path`, `logo_dark_path`, `favicon_path`, `primary_color`,
`phone`, `email`, `address`, `office_lat`, `office_lng`,
`facebook`, `instagram`, `tiktok`, `linkedin`, `youtube`,
`about_content` (json sq/en/de), `terms_content`, `privacy_content`, `faq_content`,
`app_store_url`, `play_store_url`, `watermark_enabled`.

Cache-o të gjithë tabelën si një array. Invalido në `Setting::saved()`.

### `locations` — hierarki e mbjellë
```
id, parent_id (nullable, self-referencing), name (json: sq/en/de),
slug, lat, lng, type (enum: municipality|neighborhood), timestamps
```
Seed: komunat e Kosovës + lagjet e Prishtinës (Dardania, Ulpiana, Mati 1, Bregu i Diellit,
Kodra e Diellit, Sunny Hill, Arbëria, Lakrishte, Qafa, Veternik, Fushë Kosovë...).

### `properties`
```
id, reference_code (unique, p.sh. PRO-1042), agent_id (FK users),
title (json sq/en/de), slug (unique), description (json sq/en/de),

listing_type (enum: sale|rent),
is_exclusive (bool, default false),
category (enum: house|apartment|office|store|land|warehouse|object),

price (bigint, cent), price_negotiable (bool),
surface_m2 (decimal), land_surface_m2 (decimal, nullable),
bedrooms (smallint, nullable), bathrooms (smallint, nullable),
floor (smallint, nullable), total_floors (smallint, nullable),
year_built (smallint, nullable), parking_spaces (smallint, nullable),

location_id (FK locations), address_line (nullable), lat, lng,

has_possession_sheet (bool, nullable),   -- null = e panjohur
document_type (enum: notary|lawyer, nullable),

status (enum: draft|published|reserved|sold|rented|archived),
published_at (nullable), views_count (default 0),
is_featured (bool),
meta_title, meta_description (json, nullable),
timestamps, softDeletes
```

**Indekset (obligative):** `(status, listing_type, category)`, `(location_id)`, `(price)`,
`(surface_m2)`, `(bedrooms)`, `(is_exclusive)`, `(agent_id)`, `(published_at)`, `(reference_code)`.

### `property_images`
```
id, property_id, path, thumbnail_path, is_primary (bool),
sort_order, alt_text (json, nullable), timestamps
```

### `property_price_histories`
```
id, property_id, old_price, new_price, changed_by (FK users), created_at
```
Shkruhet automatikisht nga `Property::updating()` kur `price` ndryshon.

### `features` + `feature_property` (pivot)
```
features: id, key (unique), name (json), icon, group (enum: infrastructure|furnishing), sort_order
```
Seed — grupi `infrastructure`: `street`, `water`, `wastewater`, `keds`, `elevator`,
`central_heating`, `balcony`, `garage`, `basement`.
Seed — grupi `furnishing`: `salon`, `bedroom`, `kitchen`, `bathroom`, `unfurnished`.

### `contact_messages`
```
id, property_id (nullable FK), full_name, phone, email (nullable), message,
status (enum: new|contacted|in_progress|closed),
assigned_to (nullable FK users), ip_address, timestamps
```
Kur ka `property_id` → `assigned_to` mbushet automatikisht me `property.agent_id`.

### `property_offers` — "Offer Your Property"
```
id, first_name, last_name, phone,
listing_type (sale|rent), category, location_id,
surface_m2, asking_price (bigint cent),
status (enum: new|contacted|in_progress|converted|rejected),
assigned_to (nullable FK users),
converted_property_id (nullable FK properties),
ip_address, timestamps
```

### `property_requests` — "Create Request"
```
id, first_name, last_name, phone,
request_type (enum: buying|renting), category, location_id,
budget_max (bigint cent), surface_min, surface_max, surface_unit (m2|are|ha),
details (text, nullable),
status (enum: new|contacted|in_progress|closed),
assigned_to (nullable FK users), ip_address, timestamps
```

### `lead_notes` — polimorfike, e përbashkët për të tria
```
id, notable_type, notable_id, user_id, body, created_at
```

---

## 3. Matrica e lejeve

| | Admin | Agent |
|---|---|---|
| Prona — shiko | të gjitha | të gjitha |
| Prona — krijo | ✅ | ✅ (bëhet `agent_id`) |
| Prona — edito/fshi | të gjitha | **vetëm të mijat** |
| Prona — publiko | ✅ | ✅ (të mijat) |
| Inbox — shiko | të gjitha | vetëm `assigned_to = me` |
| Inbox — cakto te dikush | ✅ | ❌ |
| Inbox — status/shënim | ✅ | të mijat |
| Agjentët | ✅ CRUD | ❌ |
| Cilësimet | ✅ | ❌ |

Zbatohet me tri policy: `PropertyPolicy`, `LeadPolicy` (e përbashkët për të tria), `UserPolicy`.

---

## 4. Struktura e faqeve

**Publike:** `/` · `/properties` (filtra) · `/properties/{slug}` · `/about` · `/contact` ·
`/offer-property` · `/create-request` · `/favorites` · `/terms` · `/privacy` · `/faq`
Prefiks gjuhe: `/{locale}/...` me `sq` si default pa prefiks.

**Dashboard:** `/dashboard` (përmbledhje) · `/dashboard/properties` · `/dashboard/properties/create`
· `/dashboard/inbox/messages` · `/dashboard/inbox/offers` · `/dashboard/inbox/requests`
· `/dashboard/agents` · `/dashboard/settings`

---

## 5. Filtrat publikë (specifikimi i saktë)

```
Rreshti 1:  [ Për shitje ] [ Me qira ] [ Ekskluzive ]
Rreshti 2:  [House] [Apartment] [Office] [Store] [Land] [Warehouse] [Object]
Rreshti 3:  Çmimi (min–max) · Sipërfaqja (min–max) · Lokacioni (komunë → lagje) · [ Më shumë ▾ ]

Më shumë:
  Fleta poseduese:  Të gjitha | Me | Pa
  Dokumentet:       Të gjitha | Noter | Avokat
  Dhomat:           Të gjitha | 1 | 2 | 3 | 4 | 5+
  Mobilimi:         Salon · Dhomë gjumi · Kuqinë · Banjo · Pa mobilje   (multi)
```

**Zbatimi:** `PropertyFilter` class → query scopes. Gjendja në URL query string.
Inertia partial reload (`only: ['properties']`) — pa reload të plotë.
Inputet me debounce 300ms. Numri i rezultateve gjithmonë i dukshëm. Empty state i dizajnuar.

---

## 6. Fazat

### Faza 0 — Themeli
`CLAUDE.md`, Laravel 12 + Inertia + Vue 3 + Tailwind, lidhja me Supabase, ky `PLAN.md` në repo.

### Faza 1 — Skema + të dhëna
Migrimet, modelet, relacionet, casts, factories, seeders.
**Rezultat:** ~200 prona fiktive realiste (çmime dhe lagje reale të Prishtinës). Pa UI.
*Pa të dhëna të mira nuk e teston dot kërkimin. Kjo fazë nuk anashkalohet.*

### Faza 2 — Auth + role + policies
Login (pa regjistrim publik!), `admin`/`agent`, tri policy, teste për çdo rregull.

### Faza 3 — Settings + shell i dashboard-it
Tabela `settings`, faqja e cilësimeve, cache, sidebar me badge, faqja e përmbledhjes.

### Faza 4 — CRUD i pronave
Forma, FormRequest, service layer, upload në Supabase S3 me drag-reorder + primary,
watermark me logo, thumbnails, reference code auto, price history auto, slug auto.

### Faza 5 — Listimi publik + filtrat  ⬅ **faza më e rëndësishme**
Kartat, filtrat, paginimi me ruajtje gjendjeje, sortimi, favorites (localStorage).
*Këtu vendoset nëse projekti duket profesional apo jo. Jepi kohë.*

### Faza 6 — Faqja e detajeve
Galeri + lightbox, specifikimet, veçoritë, harta Leaflet, karta e agjentit,
butoni WhatsApp me ref code të parambushur, forma e kontaktit, price history chart,
kalkulatori i financimit, 3 prona të ngjashme, Open Graph tags.

### Faza 7 — Offer + Request + Inbox
Të tria format publike. Inbox me tre tabs, status, shënime, assignment,
**"Krijo pronë nga oferta"** (parambush + lidh), përputhje automatike për kërkesat.

### Faza 8 — Shumëgjuhësia
Spatie Translatable për JSON columns, lang files për UI, ndërruesi i gjuhës,
`hreflang`, ruajtja e locale në sesion + URL.

### Faza 9 — Dizajni
Kalim i veçantë, vetëm estetikë. Drejtim i qartë i dhënë paraprakisht.
*Mos ia lër Claude Code-it dizajnin gjatë ndërtimit — del generic.*

### Faza 10 — Hardening + deploy
Rate limits, security headers, Ziggy filtering, audit i N+1, cache i faceteve,
SVG blocking, Lighthouse, sitemap.xml, robots.txt, deploy.

---

## 7. Jashtë fushëveprimit (v1)

Chat · krahasim pronash · njoftime email për kërkime të ruajtura · tur virtual ·
llogari për blerësit · shiriti "Very Good Price" *(kërkon ≥15 prona krahasuese për të mos
qenë numër i sajuar — shtoje kur baza mbushet)* · aplikacion mobil.

---

## 8. Promptet për Claude Code

> Rregulli: **një fazë, verifiko, commit, vazhdo.** Kurrë të gjitha njëherësh.

### Faza 0
```
Fresh Laravel 12 + Inertia + Vue 3 + Tailwind project. Read PLAN.md in the root — it is
the source of truth for this entire build.

Task: set up CLAUDE.md documenting the stack, conventions (service layer for business
logic, FormRequests for validation, policies for authorization, always write feature
tests), and the rule that NOTHING about the agency is hardcoded — all branding comes from
the settings table.

Then configure the Supabase PostgreSQL connection and Supabase S3 disk. Do not write
feature code yet. Ask me questions if anything in PLAN.md is ambiguous.
```

### Faza 1
```
Implement section 2 of PLAN.md: all migrations, models with relationships and casts,
factories, and seeders.

Critical details:
- Prices are bigint in cents. Add a Price cast.
- Translatable fields are JSON columns (sq/en/de) via spatie/laravel-translatable.
- Add every index listed in the schema.
- properties.is_exclusive is a boolean, separate from listing_type.
- Seed locations with real Kosovo municipalities and Prishtina neighborhoods with real
  lat/lng.
- Seed features for both groups.
- Seed 200 realistic properties: real neighborhood names, believable prices
  (apartment €70k-180k, house €150k-400k, rent €250-900/month), varied categories and
  statuses, 3-8 images each (use placeholder URLs).

No controllers, no UI. Run the migrations and show me a sample of the seeded data.
```

### Faza 2
```
Implement section 3 of PLAN.md: authentication and authorization.

- Login only. NO public registration — accounts are created by the admin.
- role enum: admin, agent.
- PropertyPolicy: view=any authed, create=any authed, update/delete=admin OR owning agent.
- LeadPolicy: shared by ContactMessage, PropertyOffer, PropertyRequest.
  view/update = admin OR assigned_to == user. assign = admin only.
- UserPolicy: admin only.
- Middleware for /dashboard.

Write a feature test for every single row of the permission matrix in PLAN.md section 3.
```

### Faza 5 (më e rëndësishmja)
```
Build the public property listing page per section 5 of PLAN.md. This is the most
important page in the project — take your time.

- A PropertyFilter class translating query params into Eloquent scopes. All filters
  compose. Do it server-side.
- Filter state fully in the URL query string, shareable and back-button safe.
- Inertia partial reloads (only: ['properties']) so filtering never does a full reload.
- Debounce text/range inputs at 300ms.
- Pagination preserves all filters.
- Sorting: newest, price asc, price desc, surface desc.
- Always show the result count.
- Design the empty state properly — it will be seen.
- Favorites via localStorage (no auth, no table). Extract into a composable.
- Eager load agent, primaryImage, location. Zero N+1 — prove it with a query count
  assertion in a test.

Feature tests: each filter alone, several combined, URL state survives pagination.
```

### Faza 7
```
Build the Offer, Request, and Inbox per sections 2 and 6 of PLAN.md.

Public: /offer-property and /create-request forms, rate limited, with Kosovo phone
validation (+383).

Dashboard Inbox — one shared component, three tabs (Messages, Offers, Requests):
- Status dropdown, notes (lead_notes polymorphic), assignment (admin only).
- Unread/unassigned badge counts in the sidebar.
- ContactMessage with a property_id auto-assigns to that property's agent_id, and the
  detail view shows the property card next to the message.
- Offers: a "Create property from this offer" button that opens the property create form
  prefilled from the offer, and on save sets converted_property_id and status=converted.
- Requests: automatically show matching published properties (same category, same
  location, price <= budget_max, surface within range) with a WhatsApp share button.

Feature tests for the conversion flow and the auto-assignment.
```

---

## 9. Rregullat gjatë gjithë projektit

1. **Asgjë e fiksuar në kod** për agjencinë. Gjithçka nga `settings`.
2. **Çmimet integer cent.** Kurrë float.
3. **Teste për çdo policy** dhe çdo filtër.
4. **Prompti "propose, don't implement"** para çdo faze të madhe.
5. **Dizajni në fund**, si kalim i veçantë me drejtim të qartë.
6. **Commit pas çdo faze.** Branch për fazat e mëdha.
