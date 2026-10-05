---
status: preview-draft
note: PREVIEW DRAFT — not production. Do not publish or promote to live apex until Jack explicitly says go.
brand: iComply Professional Services
domain: https://icomplyprofessionalservices.co.uk
pricing: POA only — never invent fixed £ prices
quality_bar: ">=800 words body, >=3 images with alt, full meta+OG+canonical+JSON-LD, FAQs on hubs"
---
# Areas we cover — UK towns and places | iComply Professional Services

## Meta block (required)

| Field | Value |
|-------|-------|
| title | UK Areas & Towns Coverage | iComply Professional Services |
| description | iComply Professional Services plans coverage across UK cities, towns and villages. Preview area model — request a quote POA. Not a branch list. |
| og:title | UK Areas & Towns Coverage | iComply Professional Services |
| og:description | iComply Professional Services plans coverage across UK cities, towns and villages. Preview area model — request a quote POA. Not a branch list. |
| og:url | https://icomplyprofessionalservices.co.uk/areas/ |
| og:type | website |
| og:image | https://icomplyprofessionalservices.co.uk/assets/images/placeholders/areas-map.jpg |
| canonical | https://icomplyprofessionalservices.co.uk/areas/ |

### JSON-LD schema

```json
{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "UK Areas Coverage — iComply Professional Services",
  "url": "https://icomplyprofessionalservices.co.uk/areas/",
  "isPartOf": {
    "@type": "WebSite",
    "name": "iComply Professional Services",
    "url": "https://icomplyprofessionalservices.co.uk/"
  }
}
```


## Preview notice

**PREVIEW DRAFT — not production.** Full UK places allowlist is now **locked** (34,235 places — see `areas/AREA-LOCK-FULL-UK-2026-10-05.md`). TOP5000 remains a wave-priority subset only. Do not generate keyword×place matrix files in this scaffold; when matrices are built later, use FULL UK slugs — not TOP5000-only.

![Abstract UK outline highlighting cities towns and villages for professional services coverage](/assets/images/placeholders/areas-1.jpg)
![Planner reviewing a ranked list of UK place slugs for SEO area waves](/assets/images/placeholders/areas-2.jpg)
![Regional workshop with practice managers from multiple English towns](/assets/images/placeholders/areas-3.jpg)

## Coverage philosophy

iComply Professional Services is built for **nationwide** professional audiences. Clients and firms exist in every city, town and village — not only in the largest conurbations. Our area information architecture therefore plans for:

- Parent **area hub** pages that explain regional context without inventing fake offices
- Later **keyword times place** pages once PS SEO locks keywords and place slugs
- Honest language: remote or national support and on-site workshops by arrangement — not a fabricated high-street branch in every settlement

## Authoritative area lock (FULL UK)

**Locked 2026-10-05:** full UK places allowlist — **34,235** places.

- Lock note: `areas/AREA-LOCK-FULL-UK-2026-10-05.md`
- Data: `areas/UK-FULL-PLACES-2026-10-05.csv`
- Slugs: `areas/UK-FULL-PLACES-2026-10-05.slugs.txt` (34,235 lines)

This FULL UK allowlist is the primary place set for future keyword×place matrices. Do **not** wire production matrices to TOP5000-only.

### Interim / subset files (wave planning only)

- `AREA-LOCK-TOP5000-2026-10-05.md` + companion CSV/slug files — ranked subset for prioritised waves
- `AREA-LOCK-ALL-PLACES-2026-10-05.md` + earlier places files — superseded for matrix scope by FULL UK lock

SCOPE-EXPAND remains: every UK city, town and village is in scope. Keyword×place matrix files are **not** generated in this scaffold wave — await PS SEO keyword lock, then build against FULL UK slugs.

## How area pages should read (quality bar)

When individual town pages are generated later, each must still meet the strict bar: at least 800 words unique body (not a short stub with the town name swapped), three or more images, full meta, Open Graph, canonical and schema, and useful localised context that does not invent unverifiable local claims. Shared thin shells are a hard reject.

Local colour that is allowed: travel-to-client patterns, typical firm sizes in that market, regional referral habits, and links back to the relevant vertical hubs. Local colour that is forbidden: fake testimonials, fake street addresses, invented prices, or copied Property Services estate content.

## Relationship to vertical hubs

Area pages sit beside vertical hubs, not instead of them. A conveyancer in Leeds and a private dentist in Leeds share geography but not operating reality. Internal links should send readers to the right hub first, then to keyword times place pages once those exist.

Suggested parent links from this Areas overview:

- [Solicitors and lawyers hub](/hubs/solicitors-lawyers/)
- [Private healthcare and GPs hub](/hubs/private-healthcare-gps/)
- [Accountants hub](/hubs/accountants/)
- [Financial advisors hub](/hubs/financial-advisors/)
- [Insurance brokers hub](/hubs/insurance-brokers/)

(Full hub list remains on the home page.)

## Wave planning (ops, not public hype)

Ops waves may prioritise denser places first for quality assurance, then expand. Public messaging should not promise that every village page exists on day one of preview. This Areas page explains the model; sitemaps will list what is actually exported when Website ships HTML.

Place slug discipline matters. Generators must use locked slugs only, preserve trailing-slash policy consistently with the rest of the site, and regenerate sitemaps on the same ship as new indexable routes. Routes that 301 or noindex must leave the sitemap on the same ship.

## POA and contact from any place

Wherever your firm is based, quotes remain **POA**. Use the [Contact](/contact/) pattern with your locations listed in free text. We will not refuse an enquiry because a town page has not been generated yet.

Rural practices and coastal towns often have different recruitment and locum patterns than city centres. When we tailor workshops, those constraints matter more than decorative local adjectives. Tell us how your geography shapes staffing and client access.

## Preview FAQs

### Do you have an office in my town?
Not implied. Nationwide support does not equal a physical branch network. Ask via Contact if you need on-site workshops.

### Why mention TOP5000 if all places are in scope?
TOP5000 helps sequence build waves. Final allowlist aims at all UK places per SCOPE-EXPAND.

### When will town pages appear?
After PS SEO locks and Website export waves. This preview does not publish the full matrix.

### Will each town page be unique?
Yes — uniqueness is mandatory under PAGE-TEMPLATE-STRICT and PAGE-QUALITY-BAR. Thin clones are rejected.

### Can villages be included?
Yes. Scope includes cities, towns and villages. Await the full allowlist before claiming a specific village URL is live.

## Author notes for generators

When scripts create area slug markdown or keyword times place files later:

1. Pull place name and slug from the locked allowlist only.
2. Inject unique sectional modules (not only a town-name replace).
3. Keep canonical as an absolute `https://icomplyprofessionalservices.co.uk` URL.
4. Include at least three images with alt text; only mention a place in alt text when the claim is truthful.
5. Log rejects to `seo/REJECT-LOG.md` if word count or meta fails.

## Closing

Areas are a first-class part of the Professional Services information architecture. This overview page states the nationwide intent, the interim versus final data distinction, the anti-thin-page rule, and the POA contact path. It stays preview-only until Jack's go-live.
