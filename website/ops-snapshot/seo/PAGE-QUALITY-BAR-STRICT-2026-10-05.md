# PAGE QUALITY BAR — STRICT SCORE / REJECT — Professional Services 2026-10-05

**Jack (via Grok Bot):** Score and **reject** any PS page missing **800 words**, **3 images**, or **full meta/tags**. Strict template. No parking. No soft pass.

**Domain:** https://icomplyprofessionalservices.co.uk  
**Owner:** PS SEO (locks + CONFIRM). PS Website ships only pages that pass this bar.

## Hard reject (any one fails → REJECT)
| Gate | Rule |
|------|------|
| Body copy | **≥ 800 words** unique prose (body only — not chrome/nav/footer) |
| Images | **≥ 3** content images on the page |
| Metadata | **Complete** title, meta description, OG tags (`og:title`, `og:description`, `og:url`, `og:type`, `og:image`), **canonical**, **Schema.org JSON-LD** |
| Sitemap | Indexable routes present in regenerated sitemap on same ship |

Missing any hard gate = **REJECT**. Do not CONFIRM. Do not ship. Fix and resubmit.

## Also required (scorecard)
| Requirement | Rule |
|-------------|------|
| FAQs | Dedicated FAQ section (≥3 Qs preferred) |
| Uniqueness | Unique intro; no boilerplate-only ×area / ×town clones |
| POA | Never fixed £ prices in title/meta/body |
| Brand | iComply Professional Services (not Property) |
| Canonical | Absolute `https://icomplyprofessionalservices.co.uk{path}` |
| Internal links | Healthy parents (no soft-404 hubs) |

## Scorecard checklist (per page / sample)
- [ ] Word count ≥ 800 (body)
- [ ] FAQ section present
- [ ] ≥3 content images
- [ ] Title present and unique
- [ ] Meta description present and unique
- [ ] OG title / description / url / type / image present
- [ ] Schema.org JSON-LD present (ProfessionalService / LocalBusiness / FAQPage / Service as fits)
- [ ] Canonical absolute HTTPS to preferred URL
- [ ] POA / compliance wording OK
- [ ] Internal links healthy
- [ ] In sitemap if indexable; out if 301/noindex

**Pass** = all hard reject gates + checklist OK.  
**Reject** = any hard gate miss. Return to PS Website with the failed fields named.

## Meta pattern (brief)
See `META-TITLE-DESCRIPTION-RULES-2026-10-05.md` in this folder.

Title: `{Primary intent} in {Town} | iComply Professional Services` (hubs omit town).  
Meta description: 140–160 chars — what + where + who for + CTA (“Request a quote — POA”).  
OG mirrors title/description/canonical; `og:image` = one of the 3 page images.

## Ship gates
| Gate | When | Pass |
|------|------|------|
| Pre-CONFIRM | Before SEO CONFIRM on any PR/pack | Sampled pages meet bar; sitemap includes new locs |
| Post-ship | After merge/alias/prod | Live sample meets bar; live sitemap matches live 200s |

## Related
- Keyword locks: `keywords/` + `seo/PS-KEYWORDS-LOCK-*.md`
- Area locks: `areas/` (TOP5000 subset; full UK places primary when locked)
- Property reference only (do not write there): Property `PAGE-QUALITY-BAR-KEYWORD-JOB-2026-10-05.md`

## Audit log
Every reject: append page URL + failed gates to `seo/REJECT-LOG.md`.

## Canonical template
Manager lock (source of truth): `/workspace/icomply-ops/professional-services/PAGE-TEMPLATE-STRICT-2026-10-05.md` — includes schema. Do not mark URLs shippable unless they meet it.

## Preview only (Jack 2026-10-05)
- **PREVIEW ONLY** until Jack explicitly says go live.
- Do **not** mark any URL as production-shippable / go-live.
- Preview / draft / Netlify deploy-preview only. No production DNS cutover or promote without Jack’s “go”.
- Source: `/workspace/icomply-ops/professional-services/PREVIEW-AND-TEMPLATE-RULES-2026-10-05.md`

## Per-keyword unique templates (Jack 2026-10-05) — HARD REJECT
| Gate | Rule |
|------|------|
| Unique template | Each keyword (and keyword×place) must have its **own** template structure/copy pattern — not one generic shared shell with find/replace only |
| Shared thin shell | One generic template for all keywords = **REJECT** |

Still requires STRICT bar (≥800w, ≥3 images, full meta + OG + canonical + Schema.org JSON-LD, FAQs).

Reject-log columns should name: `preview_violation` | `shared_thin_shell` | `wordcount` | `images` | `meta` | `schema` as applicable.

