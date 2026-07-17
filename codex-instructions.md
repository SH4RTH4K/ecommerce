# Instructions for Codex — Ecommerce Homepage Redesign

## Context
This is a general Laravel e-commerce application. Its catalog, branding, contact information, domain, categories, and products are configurable for different stores.

A reference mockup (`index.html`) and design plan (`redesign-plan.md`) have already been produced, styled after startech.com.bd but using this site's real navy (#0B3D62) / orange (#F5821F) palette, categories, and brands. Your job is to implement that design as real, working Blade views wired to the existing database — not to reproduce the static mockup as-is.

Attach both `index.html` and `redesign-plan.md` to your context before starting; treat `index.html` as the visual/markup source of truth and `redesign-plan.md` as the implementation notes.

## Objective
Rebuild the homepage (and the shared header/footer used site-wide) as Blade partials backed by real Eloquent queries, matching the mockup's structure: top bar, main header with search, mega-menu navigation, hero carousel, featured category icons, tabbed featured-products grid, USP strip, new-arrivals grid, brand grid, and footer.

## Before writing code
1. Inspect the existing schema: `products`, `categories`, `manufacturers` (or `brands`) migrations and models. Report back what columns already exist before assuming anything from the plan doc.
2. Check whether `old_price`/`discount` and any "featured" or "new arrival" flag already exist in any form (even under different names). Do not create duplicate columns.
3. Identify the existing routes/controller that renders the homepage (likely `HomeController@index` or similar) and the current `home.blade.php` (or equivalent) so you extend it rather than starting from scratch.
4. Identify how the current cart/compare session or service works, so header badge counts use real data, not placeholders.

## Schema changes (only if missing — confirm first)
- `products.old_price` — nullable decimal, same precision as `price`. Used to render strikethrough price + computed discount badge.
- `products.is_featured` — boolean, default false.
- `products.is_new_arrival` — boolean, default false.
- Write a migration for whatever is actually missing. Do not touch columns that already exist under a different name — grep the codebase first.

## Blade structure to produce
```
resources/views/partials/topbar.blade.php
resources/views/partials/header.blade.php        // logo, search bar, account/compare/cart (real counts)
resources/views/partials/mega-menu.blade.php      // renders category tree, cached
resources/views/partials/product-card.blade.php   // reusable: accepts $product, used on homepage + category + search pages
resources/views/partials/footer.blade.php
resources/views/home.blade.php                    // hero carousel, category icon strip, featured tabs, USP strip, new arrivals, brand grid
```

Reuse `product-card.blade.php` anywhere a product grid currently exists (category listing, search results) instead of only on the homepage — check for duplicated card markup elsewhere in the codebase and consolidate it into this partial.

## Specific implementation requirements
- **Mega-menu**: build the category tree from the real `categories` table. If categories are flat (no `parent_id`), do NOT invent a fake hierarchy in the database — group them for display only inside the Blade view, and flag this clearly in a code comment as a temporary display grouping pending a real parent/child schema decision.
- **Mega-menu caching**: wrap the category tree query in `Cache::remember('mega-menu-tree', now()->addHours(6), fn() => ...)`. Invalidate the cache in the category model's `saved`/`deleted` observers if one exists, or note where cache-busting should be added if it doesn't.
- **Featured Products / New Arrivals**: two independent queries (`where('is_featured', true)`, `where('is_new_arrival', true)`), each `->limit(10)`. Do not reuse one collection for both sections.
- **Discount badge**: compute in an accessor on the `Product` model, e.g. `getDiscountPercentAttribute()`, returning null when `old_price` is null or not greater than `price`. Blade should just call `$product->discount_percent`.
- **Cart/compare/account badges**: pull from the real session/cart service already used elsewhere in the app. If none exists, do not build one from scratch — flag it and stub with `0` clearly marked as TODO.
- **Carousel images**: pull from whatever mechanism currently manages homepage banner images (likely a `banners` or `sliders` table/admin panel) rather than hardcoding image paths. If no such mechanism exists, keep the current hardcoded images from the existing homepage and flag this as a follow-up.
- **Product images**: use the real `product.image` (or equivalent) field/storage path. Add a fallback placeholder only for products with a null/broken image path.
- **Bangla notice strip**: keep it, but pull the text from a settings/config table if one exists (the current site updates this message seasonally); otherwise leave it as an editable Blade string and flag it as a follow-up for a settings-driven version.
- **Responsiveness**: preserve the mockup's mobile breakpoints (collapse mega-menu into a toggle/drawer under ~720px, product grid to 2 columns, etc.) — don't ship a desktop-only version.
- **Accessibility**: keep visible focus states on nav links, buttons, and carousel controls; carousel must be pausable/keyboard-navigable, not autoplay-only.

## Explicitly out of scope for this task
- Do not redesign product detail pages, cart, checkout, or account pages — homepage and shared header/footer only.
- Do not change the color palette, typography, or layout structure from what's in `index.html` without flagging the change and reason.
- Do not invent product data — pull everything from the real database. If a section (e.g. "New Arrivals") would be empty because no products are flagged yet, note that seeding/admin work is needed rather than hardcoding fake products.

## Definition of done
- Homepage renders from real DB data with no hardcoded product arrays.
- Header cart/compare counts reflect actual session state.
- Mega-menu reflects actual categories, cached, with a clear comment on any display-only grouping.
- `product-card.blade.php` is the single reusable card component, adopted on at least the homepage (and ideally category/search pages if time allows).
- Site is responsive at the same breakpoints as `index.html`.
- A short summary is provided of: any schema migrations added, any TODOs/flags left for missing infrastructure (banner management, settings table, cart service), and any deviation from the mockup with reasoning.
