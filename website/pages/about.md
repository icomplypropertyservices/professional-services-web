---
status: preview-draft
note: PREVIEW DRAFT — not production. Do not publish or promote to live apex until Jack explicitly says go.
brand: iComply Professional Services
domain: https://icomplyprofessionalservices.co.uk
pricing: POA only — never invent fixed £ prices
quality_bar: ">=800 words body, >=3 images with alt, full meta+OG+canonical+JSON-LD, FAQs on hubs"
---
# About iComply Professional Services

## Meta block (required)

| Field | Value |
|-------|-------|
| title | About Us | iComply Professional Services |
| description | Learn how iComply Professional Services supports UK law, healthcare, accountancy, finance and insurance firms. Preview draft — request a quote POA. |
| og:title | About Us | iComply Professional Services |
| og:description | Learn how iComply Professional Services supports UK law, healthcare, accountancy, finance and insurance firms. Preview draft — request a quote POA. |
| og:url | https://icomplyprofessionalservices.co.uk/about/ |
| og:type | website |
| og:image | https://icomplyprofessionalservices.co.uk/assets/images/placeholders/about-team.jpg |
| canonical | https://icomplyprofessionalservices.co.uk/about/ |

### JSON-LD schema

```json
{
  "@context": "https://schema.org",
  "@type": "AboutPage",
  "name": "About iComply Professional Services",
  "url": "https://icomplyprofessionalservices.co.uk/about/",
  "mainEntity": {
    "@type": "ProfessionalService",
    "name": "iComply Professional Services",
    "url": "https://icomplyprofessionalservices.co.uk/",
    "areaServed": "GB"
  }
}
```


## Preview notice

**PREVIEW DRAFT — not production.** This About page scaffolds brand narrative for review. No production DNS or Netlify promote without Jack's go-live.

![iComply Professional Services brand workshop notes on a conference table](/assets/images/placeholders/about-1.jpg)
![Practice managers discussing compliance evidence packs in a UK office](/assets/images/placeholders/about-2.jpg)
![Documented operating procedures folder prepared for a professional services firm](/assets/images/placeholders/about-3.jpg)

## Our purpose

iComply Professional Services was created so that UK professional practices — from solicitors' firms and conveyancing desks to private clinics, accountancy practices, advice firms and insurance brokers — can access structured help with the operational side of staying organised, auditable and client-ready. The brand is separate from iComply Property Services. Different domain, different verticals, different copy, different quality locks.

We believe good compliance support is practical. Policies that sit unread do not protect clients or partners. Training that never reaches the people opening files does not reduce risk. Our bias is toward clear ownership, realistic cadences, and artefacts that survive busy weeks.

## What we are (and are not)

We are a **professional services support** brand focused on compliance and operating discipline for firms. We are **not** a substitute solicitor, barrister, clinician, accountant, financial adviser or insurance broker. We do not quote fixed consumer prices. We do not sell Property Services gas, electrical or fire products on this domain. We do not invent local branch networks.

When a firm engages us, they remain accountable to their own regulators and professional bodies. Our role is to help them design and maintain the scaffolding around that accountability.

## How we think about quality on this site

Jack set a non-negotiable page bar for Professional Services: at least eight hundred words of unique body content, three or more images with meaningful alt text, complete title and meta description, Open Graph tags, absolute canonical URLs on https://icomplyprofessionalservices.co.uk, Schema.org JSON-LD, and FAQ sections on hubs. Thin templates and shared shells that only swap a town name are rejected. Keyword pages must each have their own template structure.

That bar exists because professional audiences — and search quality systems — notice hollow pages. Preview drafts already aim at the bar so later export to the PHP/Netlify site does not start from thin stubs.

## Vertical breadth

Scope covers all justifiable UK professional services. The P0 hub set includes solicitors and lawyers, barristers, conveyancers, private healthcare GPs, private dentists, physiotherapists, accountants, bookkeeping, tax advisors, financial advisors, financial planners, mortgage advisors, wealth management, pensions advisors, insurance brokers and life insurance brokers. PS SEO may lock further verticals. Area coverage targets every UK city, town and village once the full allowlist is confirmed; TOP5000 is an interim prioritisation aid only.

## Working style

Engagements usually mix conversation and artefacts. We listen for where workarounds have replaced process, where supervision is assumed rather than evidenced, and where growth has left induction, complaints or supplier checks behind. Then we propose a scoped deliverable at **POA** — never a fake on-page price list.

Teams get the most value when partners sponsor the work and a named internal owner keeps the rhythm after handover. We design for that ownership model rather than creating dependency on perpetual rewrites of the same binder.

Workshops are often more useful than remote-only document dumps. Sitting with the people who open files reveals the real sequence of tasks, the informal escalations, and the places where software and paper disagree. We capture that reality, then simplify — not decorate — the written process.

Document control deserves attention. Version history, approval dates, and retirement of obsolete templates stop teams arguing about which PDF is current. We help firms set a light control habit that still satisfies auditors who ask when a policy was last reviewed.

## Brand and domain

- Brand name: **iComply Professional Services**
- Apex: **https://icomplyprofessionalservices.co.uk**
- Pricing language: **POA only**
- Status of this content: **preview draft**

Property Services remains a separate brand and codebase. Do not mix catalogues or SEO ops folders.

## Culture notes for page authors

When expanding these scaffolds into production HTML:

1. Keep voice calm, specific and UK-professional — no hype, no invented case studies with fake metrics.
2. Prefer concrete process language (file review cadence, induction checklist, complaints log) over vague excellence claims.
3. Link hubs to parent home and to areas; avoid orphan keyword pages.
4. Preserve distinct hub section structures — each vertical has different regulatory and operational textures.
5. Never paste Property Services disclaimers about gas safety or electrical installation onto this domain.

Authors should also resist stuffing every synonym into one paragraph. Primary intent belongs in the title and opening; supporting themes can appear naturally in later sections. Keyword lists in meta descriptions are rejected by our own rules.

## Preview FAQs

### Is iComply Professional Services the same as Property Services?
No. Separate brand, domain, verticals and ops folder. Architecture may mirror Property's PHP static-export pattern; content must not.

### Can we go live from these markdown files?
No. Preview only. PHP repo build and Jack's go-live approval are separate tracks.

### Do you list office addresses in every town?
No. Nationwide support intent does not equal inventing branch offices. Area pages explain coverage honestly.

### How should prices appear?
Always **POA** / request a quote. Never invent pound figures in titles, meta or body.

### Who owns keyword uniqueness?
PS SEO locks intents; Website emits unique templates per keyword. Shared thin shells are a hard reject.

## Closing

About pages often become boilerplate. This one should stay useful: explain the brand boundary, the quality bar, the vertical map, and the preview status so every later contributor knows the locks. For enquiries, use the Contact scaffold. For geographic planning, use Areas. For vertical detail, use the hub set under `/hubs/`.
