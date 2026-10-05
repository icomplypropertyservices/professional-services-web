#!/usr/bin/env python3
"""Build compact data for the on-demand keyword x place renderer (Netlify Functions).

Reads (all in git):
  website/data/areas/UK-FULL-PLACES-2026-10-05.csv        (34,235 place allowlist, population order)
  website/data/areas/UK-ALL-PLACES-2026-10-05.csv         (coordinates + feature codes)
  website/data/areas/UK-TOP5000-TOWNS-BY-POP-2026-10-05.csv (county / region for TOP5000)
  website/data/areas/xplace-subwaves/W1-SUB001..071-places.txt
  website/data/keywords/PS-KEYWORDS-*.txt                  (allowlist + tiers)
  website/data/xplace/units.json                           (enabled units)
  dist/keywords/<slug>/index.html                          (rendered keyword bodies; run static export first)
Writes (gitignored): netlify/functions/xplace-data/*.json
Standard library only, so it runs on a bare CI runner.
"""
import csv, json, math, os, re, sys, html
from html.parser import HTMLParser

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__)))))
WEB = os.path.join(ROOT, 'website')
DATA = os.path.join(WEB, 'data')
DIST = os.path.join(ROOT, 'dist')
OUT = os.path.join(ROOT, 'netlify', 'functions', 'xplace-data')

# Agency / web-design / SEO / marketing intent never enters the allowlist (Marketing site owns these).
AGENCY_RX = re.compile(r'(^|-)(seo|marketing|website|websites|web-design|web-designer|web-designers|web-development|web-developer|'
                       r'web-developers|ppc|adwords|google-ads|social-media|branding|copywriting|copywriter|advertising|lead-generation)(-|$)')

def lines(p):
    with open(p) as f:
        return [l.strip() for l in f if l.strip()]

def kwset(name):
    return [k for k in lines(os.path.join(DATA, 'keywords', f'PS-KEYWORDS-{name}.txt')) if not AGENCY_RX.search(k)]

BEAR = ['north', 'north-east', 'east', 'south-east', 'south', 'south-west', 'west', 'north-west']
def bearing(a, b):
    la1, lo1, la2, lo2 = map(math.radians, (a[0], a[1], b[0], b[1]))
    y = math.sin(lo2 - lo1) * math.cos(la2)
    x = math.cos(la1) * math.sin(la2) - math.sin(la1) * math.cos(la2) * math.cos(lo2 - lo1)
    deg = (math.degrees(math.atan2(y, x)) + 360) % 360
    return int(((deg + 22.5) % 360) // 45)
def miles(a, b):
    la1, lo1, la2, lo2 = map(math.radians, (a[0], a[1], b[0], b[1]))
    h = math.sin((la2 - la1) / 2) ** 2 + math.cos(la1) * math.cos(la2) * math.sin((lo2 - lo1) / 2) ** 2
    return 3958.8 * 2 * math.asin(math.sqrt(h))

REGIONS = {'London', 'South East', 'South West', 'East of England', 'East Midlands', 'West Midlands', 'North West', 'North East',
           'Yorkshire and the Humber', 'Yorkshire and The Humber', 'Wales', 'Scotland', 'Northern Ireland'}

def build_places():
    full = list(csv.DictReader(open(os.path.join(DATA, 'areas', 'UK-FULL-PLACES-2026-10-05.csv'))))
    allp = {r['slug']: r for r in csv.DictReader(open(os.path.join(DATA, 'areas', 'UK-ALL-PLACES-2026-10-05.csv')))}
    top = {r['slug']: r for r in csv.DictReader(open(os.path.join(DATA, 'areas', 'UK-TOP5000-TOWNS-BY-POP-2026-10-05.csv')))}
    sub = {}
    d = os.path.join(DATA, 'areas', 'xplace-subwaves')
    for f in sorted(os.listdir(d)):
        m = re.match(r'W1-SUB(\d+)-places\.txt', f)
        if m:
            for s in lines(os.path.join(d, f)):
                sub[s] = int(m.group(1))
    coords = {}
    for r in full:
        s = r['slug']
        src = allp.get(s) or top.get(s)
        if not src:
            base = re.sub(r'-(\w+)$', '', s)
            src = allp.get(base) or allp.get(s.split('-and-')[0]) or top.get(base)
        if src and src.get('lat'):
            coords[s] = (float(src['lat']), float(src['lng']))
    rows = []
    for i, r in enumerate(full):
        s = r['slug']
        cr = r['county_or_region'] or ''
        if s in top:
            county, region = top[s]['county'], top[s]['region']
        elif ' / ' in cr:
            county, region = cr.split(' / ', 1)
        else:
            county = (allp.get(s, {}).get('county') or cr).split(',')[0].strip()
            region = cr if cr in REGIONS else ''
        if county in REGIONS and allp.get(s, {}).get('county'):
            county = allp[s]['county']
        nation = r['nation']
        if not region:
            region = nation if nation != 'England' else ''
        name = re.sub(r'\s*\(([^)]*)\)$', '', r['name']).strip()
        pop = int(float(r['population'] or 0))
        feat = (allp.get(s, {}).get('feature') or '')
        rows.append(dict(slug=s, name=name, county=county.strip(), region=region.strip(), nation=nation, pop=pop, feat=feat,
                         w1=sub.get(s, 0), c=coords.get(s)))
    # spatial grid for nearest neighbours
    cell = 0.25
    grid = {}
    for i, p in enumerate(rows):
        if p['c']:
            grid.setdefault((int(p['c'][0] / cell), int(p['c'][1] / cell)), []).append(i)
    def near(i, k=6, minpop=0, maxr=6):
        p = rows[i]
        if not p['c']:
            return []
        gx, gy = int(p['c'][0] / cell), int(p['c'][1] / cell)
        found = []
        for r_ in range(1, maxr + 1):
            found = []
            for x in range(gx - r_, gx + r_ + 1):
                for y in range(gy - r_, gy + r_ + 1):
                    for j in grid.get((x, y), []):
                        if j != i and rows[j]['pop'] >= minpop and rows[j]['name'] != p['name']:
                            found.append((miles(p['c'], rows[j]['c']), j))
            if len(found) >= k * 2 or (minpop and found):
                break
        found.sort()
        out, seen = [], set()
        for dd, j in found:
            if rows[j]['name'] in seen:
                continue
            seen.add(rows[j]['name'])
            out.append((j, round(dd, 1), bearing(p['c'], rows[j]['c'])))
            if len(out) >= k:
                break
        return out
    S = [p['slug'] for p in rows]
    D = []
    for i, p in enumerate(rows):
        nb = near(i, 6)
        hub = None
        for minpop in (100000, 40000, 15000):
            if p['pop'] >= minpop:
                break
            h = near(i, 1, minpop=minpop, maxr=10)
            if h and h[0][1] <= 45:
                hub = h[0]
                break
        D.append([p['name'], p['county'], p['region'], p['nation'], p['pop'], p['feat'], p['w1'],
                  [[j, dd, b] for j, dd, b in nb], ([hub[0], hub[1], hub[2]] if hub else None)])
    return S, D

class Art(HTMLParser):
    pass

def strip_tags(s):
    return html.unescape(re.sub(r'<[^>]+>', '', s)).strip()

def build_keywords(p0):
    out = {}
    fm_rx = re.compile(r'^---\n(.*?)\n---', re.S)
    for slug in p0:
        fp = os.path.join(DIST, 'keywords', slug, 'index.html')
        mdp = os.path.join(WEB, 'pages', 'keywords', slug + '.md')
        if not os.path.exists(fp):
            continue
        h = open(fp).read()
        fm = {}
        if os.path.exists(mdp):
            m = fm_rx.match(open(mdp).read())
            if m:
                for l in m.group(1).splitlines():
                    if ':' in l:
                        k, v = l.split(':', 1); fm[k.strip()] = v.strip()
        a = h[h.index('<article'):h.index('</article>')]
        a = a[a.index('>') + 1:]
        h1 = strip_tags(re.search(r'<h1>(.*?)</h1>', a, re.S).group(1))
        a = re.sub(r'<h1>.*?</h1>', '', a, flags=re.S)
        a = re.sub(r'<aside class="(hero-cta|mid-cta|convert-band)".*?</aside>', '', a, flags=re.S)
        parts = re.split(r'(<h2[^>]*>.*?</h2>)', a, flags=re.S)
        intro = parts[0].strip()
        secs, faqs = [], []
        for i in range(1, len(parts), 2):
            ht = strip_tags(parts[i]); body = parts[i + 1].strip()
            if 'faq-accordion' in body or re.search(r'faq', ht, re.I):
                for q, ans in re.findall(r'<summary>(.*?)</summary><div class="faq-answer">(.*?)</div>', body, re.S):
                    faqs.append([strip_tags(q), ans.strip()])
                pre = body.split('<div class="faq-accordion"')[0].strip()
                if pre:
                    secs.append(['', pre])
                continue
            if re.search(r'related searches|people also look for|other services you may need', ht, re.I):
                continue
            if 'wa.me' in body and '/contact/' in body and len(strip_tags(body)) < 600 and re.search(r'quote|matched|request', ht, re.I):
                continue  # closing CTA paragraph; replaced by the shared CTA component
            secs.append([ht, body])
        imgs = re.findall(r'<img src="([^"]+)" alt="([^"]*)"', a)
        # remove images from bodies; renderer places them
        intro = re.sub(r'<p><img[^>]*></p>', '', intro)
        secs = [[t, re.sub(r'<p><img[^>]*></p>', '', b)] for t, b in secs]
        intro = re.sub(r'<p><strong><a href="/contact/">Get a free quote</a></strong>[^<]*</p>', '', intro).strip()
        secs = [[t, re.sub(r'<p><strong>Need [^<]*</strong> <a href="/contact/">.*?</p>', '', b, flags=re.S).strip()] for t, b in secs]
        out[slug] = dict(h1=h1, title=fm.get('title', ''), desc=fm.get('description', ''), family=fm.get('family', ''),
                         group=fm.get('group', ''), hub=fm.get('hub', ''), prof=fm.get('profession', ''),
                         label=fm.get('service_label', ''), intro=intro, secs=[s for s in secs if s[1]], faqs=faqs,
                         imgs=[[s, html.unescape(al)] for s, al in imgs])
    return out

def main():
    os.makedirs(OUT, exist_ok=True)
    allkw = kwset('ALL')
    sets = {n: kwset(n) for n in ['P0', 'P1', 'P2-NEXT', 'AI-P1', 'IT-NETWORKING-P1', 'CYBER-SECURITY-P1', 'AI-SECURITY-FACIAL-P1',
                                   'MIDDLEMAN-SERVICES-EXPAND-P1', 'MIDDLEMAN-SERVICES-EXPAND-2-P1']}
    units = json.load(open(os.path.join(DATA, 'xplace', 'units.json')))
    S, D = build_places()
    kws = build_keywords(sets['P0'])
    missing = [s for s in sets['P0'] if s not in kws]
    if missing:
        print('WARNING: P0 bodies missing from dist:', len(missing), missing[:5], file=sys.stderr)
    json.dump({'s': S, 'd': D}, open(os.path.join(OUT, 'places.json'), 'w'), separators=(',', ':'))
    json.dump(kws, open(os.path.join(OUT, 'keywords-p0.json'), 'w'), separators=(',', ':'))
    json.dump({'all': allkw, 'sets': {k: v for k, v in sets.items()}, 'units': units}, open(os.path.join(OUT, 'sets.json'), 'w'), separators=(',', ':'))
    filtered = len(lines(os.path.join(DATA, 'keywords', 'PS-KEYWORDS-ALL.txt'))) - len(allkw)
    en = units.get('enabled', [])
    print(f'places {len(S)} (coords {sum(1 for d in D if d[7])}); keywords ALL {len(allkw)} (agency/marketing filtered {filtered}); '
          f'P0 bodies {len(kws)}; enabled units {len(en)}: {[u["id"] for u in en]}')

if __name__ == '__main__':
    main()
