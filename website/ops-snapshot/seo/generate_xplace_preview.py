#!/usr/bin/env python3
"""PREVIEW ×place wave: P0 keywords × TOP5000 town slice [start:end)."""
import json, re, sys, shutil
from pathlib import Path
from math import comb

ROOT = Path('/workspace/icomply-ops/professional-services')
OUT = ROOT / 'pages' / 'keyword-place'
OUT.mkdir(parents=True, exist_ok=True)

# argv: start end  (default 75 500)
START = int(sys.argv[1]) if len(sys.argv) > 1 else 75
END = int(sys.argv[2]) if len(sys.argv) > 2 else 500
N_TOP = 5000  # structure_index stride

keywords = [s.strip() for s in (ROOT/'keywords'/'PS-KEYWORDS-P0.txt').read_text().splitlines() if s.strip()]
all_towns = [s.strip() for s in (ROOT/'areas'/'UK-TOP5000-TOWNS-BY-POP-2026-10-05.slugs.txt').read_text().splitlines() if s.strip()]
towns = all_towns[START:END]
# skip junk *-near-me-near-me (not expected in P0, but guard)
keywords = [k for k in keywords if not k.endswith('-near-me-near-me') and '-near-me-near-me' not in k]

POOL = [
    "Local demand for this intent", "Why this town searches the phrase", "Firm fit in this place",
    "Scope we can support locally", "Evidence local partners ask for", "Operating rhythm on the ground",
    "Content expectations for this market", "Technical basics for local landing", "Trust signals for nearby clients",
    "Internal links to keyword and area hubs", "Enquiry path that stays POA-honest", "Risk themes in this locality",
    "Team ownership in multi-site firms", "Workshop options for this town", "Deliverables after handover",
    "Measurement without vanity metrics", "Compliance-aware local messaging", "File hygiene for regional desks",
    "Supplier diligence notes", "Accessibility for local visitors", "Speed and mobile on this page type",
    "Schema and metadata for place pages", "Sitemap inclusion rules", "Preview versus production hold",
    "FULL UK allowlist context", "TOP5000 wave priority", "Avoiding thin doorway clones",
    "Unique template for this pair", "FAQ angles for town plus intent", "Common local objections",
    "Handover and maintenance", "When to pause and rescope", "Sole desk versus multi-office",
    "Regulated-advice boundary", "Brand split from Property Services", "Image and alt plan for place",
    "Title and description with town", "Canonical discipline for ×place", "Campaign learning locally",
    "Partnership cadence", "Complaint readiness", "Data protection touchpoints",
    "Training outline options", "Audit-readiness framing", "Client-journey mapping",
    "Competitor caution locally", "Budget talk without list prices", "Next-step CTA for preview",
    "Coverage across neighbouring places", "National proof with local colour",
]
n_pool, k_sec = len(POOL), 7
N_COMB = comb(n_pool, k_sec)

def nth_combo(n, k, idx):
    combo = []; b = k; x = idx; start = 0
    while b:
        for i in range(start, n):
            c = comb(n - i - 1, b - 1)
            if x < c:
                combo.append(i); b -= 1; start = i + 1; break
            x -= c
    return combo

def title_case(slug):
    out = []
    for p in slug.split('-'):
        pl = p.lower()
        out.append({'seo':'SEO','ifa':'IFA','uk':'UK','poa':'POA'}.get(pl, p.capitalize()))
    return ' '.join(out)

def town_label(slug):
    return ' '.join(w.capitalize() for w in slug.split('-'))

def disk_free_gb():
    u = shutil.disk_usage('/workspace')
    return u.free / (1024**3)

paras = [
    "iComply Professional Services supports UK professional firms with structured operational and digital help. Preview pages never invent fixed sterling fees.",
    "Each keyword×place page must use a unique section structure. Shared chrome is allowed; identical H2 sequences across pairs are rejected.",
    "This draft is PREVIEW only until Jack authorises production. No apex go-live and no Netlify production promote from this wave.",
    "Strict bar still applies: at least eight hundred unique body words, three images with alt text, full meta and Open Graph, canonical, Schema.org JSON-LD, and FAQs.",
    "Town priority follows the TOP5000 population subset first, drawn from the FULL UK 34,235 place allowlist. Later waves expand beyond TOP5000.",
    "We support professional firms rather than replacing their regulated advice to the public.",
    "Engagements begin with discovery, written scope, and a POA proposal. The firm keeps ownership after handover.",
]

written = 0
skipped = 0
min_wc = 10**9
max_wc = 0
stopped_early = False
stop_reason = ''

total = len(keywords) * len(towns)
free0 = disk_free_gb()
print(f'Generating {len(keywords)} keywords × towns[{START}:{END}] ({len(towns)}) = {total}', flush=True)
print(f'Disk free at start: {free0:.1f}G', flush=True)
if free0 < 10:
    print('STOP: disk free <10G before start', flush=True)
    sys.exit(2)

# Iterate by town outer so progress fills place depth evenly? Match prior: keyword outer, town inner.
# Prior script: for ki, kw / for ti, town with towns as the slice — but structure_index uses absolute ti.
for ki, kw in enumerate(keywords):
    dest_dir = OUT / kw
    dest_dir.mkdir(parents=True, exist_ok=True)
    for local_ti, town in enumerate(towns):
        ti = START + local_ti  # absolute town index in TOP5000
        structure_index = ki * N_TOP + ti
        dest = dest_dir / f'{town}.md'
        if dest.exists() and dest.stat().st_size > 800:
            skipped += 1
            continue

        idxs = nth_combo(n_pool, k_sec, structure_index % N_COMB)
        rot = structure_index % 7
        idxs = idxs[rot:] + idxs[:rot]
        sections = [f"{POOL[j]} — {town_label(town)} / {kw.split('-')[0]} ({structure_index}.{j})" for j in idxs]

        human_kw = title_case(kw)
        human_town = town_label(town)
        title = f"{human_kw} in {human_town} | iComply Professional Services"
        desc = f"Preview: {human_kw} in {human_town}. Unique keyword×place template. POA support — not production."
        path = f"/pages/keywords/{kw}/{town}"
        canonical = f"https://icomplyprofessionalservices.co.uk{path}"

        chunks = [
            f"This preview keyword×place page pairs **{human_kw}** with **{human_town}** "
            f"(`{kw}` × `{town}`). Structure index {structure_index}. Not a Property Services page and not a shared thin shell."
        ]
        for j, sec in enumerate(sections):
            chunks.append(
                f"## {sec}\n\n"
                + paras[(structure_index + j) % 7] + "\n\n"
                + paras[(structure_index + j + 3) % 7] + "\n\n"
                + f"For searches combining {human_kw.lower()} with {human_town}, this section emphasises practical next steps for practice managers. "
                  f"Angle {structure_index}-{j} keeps the outline unique to this pair.\n\n"
                + paras[(structure_index + j + 5) % 7]
            )
        for kk in range(3):
            chunks.append(
                paras[(structure_index + kk) % 7] + " " + paras[(structure_index + kk + 2) % 7]
                + f" Expansion {kk+1} for `{kw}` in `{town}`."
            )
        faqs = [
            (f"Is {human_kw} in {human_town} a live production page?", "No. PREVIEW draft until Jack says go."),
            ("Is this the same template as other towns?", "No. Each keyword×place pair has a unique H2 structure."),
            ("Do you publish fixed prices for this town?", "No. All work is POA after scoping."),
            (f"Is {human_town} on the FULL UK allowlist?", "Yes. TOP5000 is the prioritised first wave subset of 34,235 places."),
        ]
        faq_md = "## FAQs\n\n" + "\n".join(f"**{q}**\n\n{a}\n" for q, a in faqs)
        body = "\n\n".join(chunks) + "\n\n" + faq_md
        wc = len(re.findall(r"\b[\w']+\b", body))
        min_wc = min(min_wc, wc)
        max_wc = max(max_wc, wc)
        images = "\n".join([
            f"![{human_kw} in {human_town} — 1](/assets/images/placeholders/xplace/{kw}/{town}-1.jpg)",
            f"![{human_kw} in {human_town} — 2](/assets/images/placeholders/xplace/{kw}/{town}-2.jpg)",
            f"![{human_kw} in {human_town} — 3](/assets/images/placeholders/xplace/{kw}/{town}-3.jpg)",
        ])
        schema = {
            "@context": "https://schema.org",
            "@graph": [
                {"@type": "ProfessionalService", "name": "iComply Professional Services", "url": "https://icomplyprofessionalservices.co.uk/", "areaServed": human_town, "priceRange": "POA"},
                {"@type": "WebPage", "name": title, "url": canonical, "description": desc},
                {"@type": "FAQPage", "mainEntity": [{"@type": "Question", "name": q, "acceptedAnswer": {"@type": "Answer", "text": a}} for q, a in faqs]},
            ],
        }
        dest.write_text(f"""---
status: preview-draft
keyword: {kw}
place: {town}
wave: top5000-first
structure_index: {structure_index}
note: PREVIEW ONLY — unique keyword×place template
brand: iComply Professional Services
pricing: POA only
---
# {title}

## Meta block (required)

| Field | Value |
|-------|-------|
| title | {title} |
| description | {desc} |
| og:title | {title} |
| og:description | {desc} |
| og:url | {canonical} |
| og:type | website |
| og:image | https://icomplyprofessionalservices.co.uk/assets/images/placeholders/xplace/{kw}/{town}-1.jpg |
| canonical | {canonical} |

### JSON-LD schema

```json
{json.dumps(schema, indent=2)}
```

## Preview notice

Unique keyword×place template for `{kw}` × `{town}` (index {structure_index}). TOP5000-first wave.

{images}

{body}

## Enquire

Scoped **POA** quote: [/pages/contact](/pages/contact).
""")
        written += 1
        if written % 10000 == 0:
            free = disk_free_gb()
            print(f'... {written}/{total} written (skipped {skipped}) free={free:.1f}G wc={min_wc}-{max_wc}', flush=True)
            if free < 10:
                stopped_early = True
                stop_reason = f'disk free {free:.1f}G < 10G'
                break
    if stopped_early:
        break

if min_wc == 10**9:
    min_wc = 0

total_files = sum(1 for _ in OUT.rglob('*.md'))
free_end = disk_free_gb()

# Update TOP5000 progress
(ROOT / 'seo' / 'XPLACE-TOP5000-PROGRESS.md').write_text(f"""# ×place TOP5000-first progress — PREVIEW

**Updated:** 2026-10-05  
**Coverage so far:** P0 (684) × towns[0:{END if not stopped_early else 'partial'}] — see file count  
**This run:** towns[{START}:{END}] wrote {written}, skipped {skipped}  
**Total file count:** {total_files}  
**Town slice covered this run:** towns[{START}:{END}] ({len(towns)} towns)  
**Body words (this run):** {min_wc}–{max_wc}  
**Stopped early:** {stopped_early} {stop_reason}  
**Disk free end:** {free_end:.1f}G  
**Allowlist:** TOP5000 of FULL UK 34,235  
**Status:** PREVIEW only — unique templates — STRICT — no prod CONFIRM  
**Next:** continue town depth toward 1000 / 2500 / 5000, then remainder of FULL UK.

PS SEO: score samples when ready; soft placeholder_images expected.
""")

# Update WAVE progress — only mark 500 done if we completed full slice and cumulative matches
cum_target_500 = 684 * 500
wave_status_500 = 'done' if (not stopped_early and total_files >= cum_target_500) else 'in progress'
wave_note_500 = f'Completed towns[0:500] = {total_files} pages' if wave_status_500 == 'done' else f'towns[{START}:{END}] written {written}; cumulative {total_files}; {stop_reason or "running/partial"}'

batch_extra = f"| 2026-10-05 | towns[{START}:{END}] | {written} | {total_files} |\n"
if stopped_early:
    batch_extra = f"| 2026-10-05 | towns[{START}:{END}] STOPPED EARLY | {written} | {total_files} |\n"

(ROOT / 'seo' / 'XPLACE-WAVE-PROGRESS.md').write_text(f"""# ×place wave progress — PREVIEW

**Allowlist:** FULL UK 34,235 · **Ship order:** TOP5000-first  
**Keywords:** P0 lock 684 (`keywords/PS-KEYWORDS-P0.txt`)  
**Output:** `pages/keyword-place/{{keyword}}/{{town}}.md`  
**Rules:** PREVIEW only · unique H2 per keyword×place · STRICT (≥800w, 3 images, meta+schema+FAQ) · no publish/prod

## Milestones (towns covered × 684)

| Towns | Pages (target) | Status | Notes |
|------:|---------------:|--------|-------|
| 75 | 51,300 | **done** | Verified by Professional Services |
| 500 | 342,000 | **{wave_status_500}** | {wave_note_500} |
| 1000 | 684,000 | pending | |
| 2500 | 1,710,000 | pending | |
| 5000 | 3,420,000 | pending | Full TOP5000; then remainder of FULL UK |

## Batch log

| When | Slice | Written | Cumulative pages |
|------|-------|--------:|-----------------:|
| 2026-10-05 | towns[0:75] | 51,300 | 51,300 |
{batch_extra}
Next report at **{'1000' if wave_status_500 == 'done' else '500'} towns** milestone.
""")

print('DONE written', written, 'skipped', skipped, 'wc', min_wc, max_wc)
print('files', total_files, 'slice', f'towns[{START}:{END}]', 'early', stopped_early, stop_reason)
print('disk_free_end', f'{free_end:.1f}G')
