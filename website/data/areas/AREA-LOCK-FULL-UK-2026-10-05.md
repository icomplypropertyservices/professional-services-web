# Area lock — FULL UK places (Professional Services)

**Locked:** 2026-10-05  
**Domain:** icomplyprofessionalservices.co.uk  
**Count:** 34,235 unique place slugs  
**With population > 0:** 6,326  
**Population blank / unknown:** 27,909  
**in_top5000 = yes:** 5,000 (exact TOP5000 overlap)

## Role

This is the **primary allowlist** for Professional Services keyword×place matrices when Jack requires every UK city, town **and** village — not limited to TOP 5000.

`AREA-LOCK-TOP5000-2026-10-05.md` + `UK-TOP5000-TOWNS-BY-POP-2026-10-05.*` remain the **ranked population subset** for prioritised shipping waves. TOP5000 is a strict subset of this FULL set (slug overlap **5,000 / 5,000**).

## Nations

- England: 25,045
- Scotland: 5,473
- Wales: 3,064
- Northern Ireland: 653

## Sources mined (on-box, read-only outside `professional-services/`)

1. **GeoNames GB country dump** (`/tmp/towns/GB.zip` → `GB.txt`) — densest offline gazetteer present; feature codes kept: `PPL`, `PPLA`–`PPLA4`, `PPLC`, `PPLS`, `PPLL`, `PPLF`, `PPLCH`. Dropped: `PPLX` (neighbourhoods), abandoned/historical (`PPLQ`, `PPLH`, `PPLW`). Greater London bare `PPL` neighbourhoods dropped; ONS London boroughs overlaid via mainland-10k / TOP5000. Primary bulk ≈33.4k rows (via intermediate `UK-ALL-PLACES-2026-10-05.csv` built from the same dump).
2. **TOP5000 ranked subset** — `areas/UK-TOP5000-TOWNS-BY-POP-2026-10-05.*` (also under `icomply-ops/seo/`). Preferred population / county+region when merging; marks `in_top5000=yes`.
3. **Mainland ≥10k** — `/workspace/main-web/website/data/uk-mainland-towns-10k.json` (~995; slug + London borough overlay).
4. **uk-towns-10k.json** — `/workspace/main-web/website/data/uk-towns-10k.json` (~934 ONS/NRS/NI settlement rows; many compound disambiguation slugs).
5. **uk-towns.php** — loader only; points at mainland-10k (no extra places).
6. **Dual-ring 269** — `icomply-ops/seo/AREA-HUB-ALLOWLIST-50MI-DUAL-2026-10-05.csv` (adds GM marketing places missing from GeoNames filter, e.g. cadishead, tameside, trafford).
7. **matrix-extra-places.json** + **barriers-places.json** (ONS Census 2021 BUAs) — small additive residuals.

### Source mix (winning provenance after dedupe)

- `geonames-GB`: 33,395
- `ONS towns 2019`: 420
- `ons-bua-2021`: 354
- `ONS cities and London 2019`: 27
- `Northern Ireland settlement population (published Ireland list, NI jurisdiction)`: 12
- `NRS settlements 2020`: 11
- `ons-london-borough-2022`: 6
- `geonames-cities5000+ons-london-borough-2022`: 4
- `dual-allowlist`: 3
- `matrix-extra`: 3

Dedupe key: **slug**. Prefer higher population, then better provenance (ONS borough / GeoNames / ONS BUA / dual / matrix).

## Gap (honest)

- **No OS Open Names** dataset on the box. GeoNames GB is the fullest offline UK settlement extract available here.
- Full UK named places (every hamlet / farm locality in OS Open Names) is **larger** than GeoNames `PPL*` — typically tens of thousands more named localities. Next import should be **OS Open Names** (Open Government Licence) and/or a fresh **GeoNames GB** refresh, filtered to settlement local types, then re-lock with a new dated file.
- `177` slugs beyond the GeoNames-only ALL-PLACES intermediate come mainly from ONS town compound names (e.g. `bolton-bolton`) and dual/matrix extras — kept to maximise unique on-box slugs; SEO matrices should prefer canonical GeoNames/TOP5000 slugs when both exist.

## Files

- `UK-FULL-PLACES-2026-10-05.csv` — slug,name,population,nation,county_or_region,source,in_top5000
- `UK-FULL-PLACES-2026-10-05.slugs.txt` — one slug per line, unique, **sorted A–Z**
- `AREA-LOCK-FULL-UK-2026-10-05.md` — this note
- Ranked subset (unchanged): `UK-TOP5000-TOWNS-BY-POP-2026-10-05.*` + `AREA-LOCK-TOP5000-2026-10-05.md`
- Intermediate GeoNames extract (also in this folder): `UK-ALL-PLACES-2026-10-05.*` + `AREA-LOCK-ALL-PLACES-2026-10-05.md`

Do not shrink without a new lock file. Expand only with a dated superseding lock.
