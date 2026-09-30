<!--- BEGIN HEADER -->
# Changelog

All notable changes to this project will be documented in this file.
<!--- END HEADER -->

## [2.2.0](https://github.com/liquiddesign/translator/compare/v2.1.2...v2.2.0) (2026-09-30)

### Features

* Cache invalidation — every write through `TranslationRepository` (admin edits, CSV import, `createMode`) cleans the translation cache through StORM `onCreate` / `onUpdate` / `onDelete`, so `cache: true` no longer serves stale texts after an edit. Writes outside the repository (raw SQL in a migration) call the new public `invalidateCache()` or rely on the cache clear of a deploy
* The cache key is per scope, mutation and shop (it used to include the id of the first message translated in the scope, so every page kept its own copy of the scope), and entries carry the `translator` tag


---

## [2.1.2](https://github.com/liquiddesign/translator/compare/v2.1.1...v2.1.2) (2026-08-28)

### Bug Fixes

* `translationShop` no longer hides shared (`fk_shop IS NULL`) rows — it now selects *which shop's* rows to read instead of the selected one, keeping the shared fallback that the rest of the stack relies on
* Deterministic precedence in `getScopeTranslations()` — when the same `code` exists both shared and per-shop, the shop row now always wins (previously decided by DB row order)


---

## [2.0.7](https://github.com/liquiddesign/translator/compare/v2.0.6...v2.0.7) (2024-07-24)

### Features

* You can generate uuid based on code ([de4d44](https://github.com/liquiddesign/translator/commit/de4d441cf2233f54533a1ce6c6b4459aa2d8960b))


---

## [2.0.6](https://github.com/liquiddesign/translator/compare/v2.0.5...v2.0.6) (2024-07-24)


---

## [2.0.5](https://github.com/liquiddesign/translator/compare/v2.0.4...v2.0.5) (2024-05-30)

### Bug Fixes

* Save correctly when no shop ([df44d2](https://github.com/liquiddesign/translator/commit/df44d2d9c4555d36defd4f259775eeff61b6a784))


---

## [2.0.4](https://github.com/liquiddesign/translator/compare/v2.0.3...v2.0.4) (2024-03-08)


---

## [2.0.3](https://github.com/liquiddesign/translator/compare/v2.0.2...v2.0.3) (2024-03-08)


---

## [2.0.2](https://github.com/liquiddesign/translator/compare/v2.0.1...v2.0.2) (2024-03-07)


---

