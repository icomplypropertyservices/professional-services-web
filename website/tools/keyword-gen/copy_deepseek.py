#!/usr/bin/env python3
"""Write keyword-specific copy for each P0 keyword page with DeepSeek (G20) and cache it as JSON in
website/data/keywords/p0-copy/<slug>.json. gen.py reads the cache, so page builds stay deterministic and
need no API key. Run: DEEPSEEK_API_KEY=... python3 copy_deepseek.py [slug ...]  (skips slugs already cached)."""
import os, sys, json, re, time, urllib.request, concurrent.futures as cf
HERE = os.path.dirname(os.path.abspath(__file__)); sys.path.insert(0, HERE)
from norm import parse
from topics import T
from families import F
import gen
WEB = os.path.dirname(os.path.dirname(HERE))
OUT = os.path.join(WEB, 'data', 'keywords', 'p0-copy')
KEY = os.environ.get('DEEPSEEK_API_KEY', '')

SYSTEM = """You write web copy for iComply Professional Services, a free UK introduction service: members of the public tell iComply what they need and iComply introduces them to a suitable UK professional, who quotes them directly. Quotes are POA (price on application).
Write in British English, plain and warm, speaking to the person who typed the search (\"you\"); \"we\" means iComply.
Hard rules (breaking any makes the copy unusable):
- No digits or numbers of any kind, except digits that appear in the search phrase itself and the UK numbers 111 and 999. No prices, fees, percentages, timeframes in numbers, statistics or counts.
- Nothing invented: no reviews, ratings, testimonials, case studies, awards, accreditations, rankings, partner names, firm names or claims about how many professionals iComply works with. Never call professionals vetted, verified, approved, hand-picked, trusted, leading or top-rated, and never say iComply guarantees anything.
- iComply is not regulated and does not give professional advice or provide the service itself. Never say otherwise.
- Only accurate, general, widely known UK facts. Name a regulator or official body only if it appears in the FACTS given, or is NHS 111/999, Citizens Advice, GOV.UK or HMRC where clearly relevant. If you are unsure of a fact, leave it out.
- If the search includes words like best, top, rated, cheap or affordable, do not claim anyone is the best or cheapest; explain how the reader can judge for themselves.
- Never mention websites, web design, SEO, marketing agencies, AI, templates or this page itself.
- Every sentence must be specific to THIS search phrase and the situation behind it; avoid generic sentences that would fit any profession. Mention the exact search phrase naturally at most twice.
Return only JSON."""

SCHEMA = """Return JSON with exactly these keys:
{"intro": "2-3 sentences about the specific need behind this search",
 "need_heading": "short H2 about what people searching this usually need",
 "need_lead": "one sentence",
 "needs": ["5 bullet points, each one full sentence describing a concrete situation"],
 "context_heading": "short H2 about one key thing to understand for this specific search",
 "context": ["2 short paragraphs, 2-3 sentences each, with useful accurate general information for this search"],
 "tell_heading": "short H2 about what to tell iComply when enquiring",
 "tell": ["6 bullet points: specific details worth including in the enquiry for this search, each a full sentence"],
 "questions_heading": "short H2 about questions to ask the professional",
 "questions": ["6 questions the reader could ask the professional, specific to this search, each ending with ?"],
 "faqs": [{"q": "question", "a": "2-3 sentence answer"}, ... 4 items specific to this search; do not ask whether iComply is free, regulated or on WhatsApp]
}"""

BAN = re.compile(r'£|%|(?<!not )(?<!no one can )(?<!cannot )(?<!can.t )(?<!can )\bguarantee[sd]?\b|\b(?:online|customer|client|google|star|five-star|glowing) reviews?\b|\breviews? (?:from|by) (?:clients|customers|patients)\b|\b(?:highly|top|well|best)[- ]rated\b|\btestimonial|\baward|\bvetted\b|\bverified\b|\bhand-?picked\b|\btrusted\b|\bleading\b|\btop-rated\b|\bthousands?\b|\bhundreds?\b|\bwebsite|\bseo\b|\bweb design|\bagency\b|\bmarketing agenc|\bAI\b|\btemplate|\bthis page\b|\bdozens?\b|\bnetwork of\b', re.I)

def facts(slug):
    p = parse(slug); t = T[p['key']]; f = F[t['family']]
    return p, t, f, {
        'search_phrase': gen.phrase(slug), 'profession': f['noun'], 'profession_plural': f['nouns'], 'practice_type': f['practice'],
        'what_the_professional_does': t['what'], 'topic_points': t['points'], 'regulation_facts': f['regulator'],
        'search_modifiers': p['mods'] + (['near me'] if p['near'] else []),
        'form': 'plural search (comparing several)' if p['plural'] else 'singular search',
    }

def check(d, slug):
    allowed = set(re.findall(r'\d+', slug)) | {'111', '999'}
    errs = []
    need = {'intro': str, 'need_heading': str, 'need_lead': str, 'needs': list, 'context_heading': str, 'context': list,
            'tell_heading': str, 'tell': list, 'questions_heading': str, 'questions': list, 'faqs': list}
    for k, ty in need.items():
        if not isinstance(d.get(k), ty): errs.append(f'missing {k}')
    if errs: return errs
    if len(d['needs']) < 5 or len(d['tell']) < 6 or len(d['questions']) < 6 or len(d['faqs']) < 4 or len(d['context']) < 2:
        errs.append('too few items')
    txt = json.dumps(d, ensure_ascii=False)
    txt = re.sub(r'\bIR35\b', 'IRxx', txt)
    txt = re.sub(r'limited by guarantee', 'limited by g', txt, flags=re.I)
    if any(w in slug for w in ('recruit', 'employment', 'agency', 'staffing')):
        txt = re.sub(r'\b(recruitment|employment|staffing) agenc(y|ies)\b|\bagenc(y|ies)\b', 'firm', txt, flags=re.I)
    for n in re.findall(r'\d+', txt):
        if n not in allowed: errs.append(f'digit {n}')
    for m in BAN.finditer(txt):
        if m.group(0).lower() in slug.replace('-', ' '): continue
        errs.append(f'banned "{m.group(0)}"')
    for fq in d['faqs']:
        if not isinstance(fq, dict) or not fq.get('q') or not fq.get('a'): errs.append('bad faq')
    words = len(re.findall(r'\w+', ' '.join([d['intro'], d['need_lead']] + d['needs'] + d['context'] + d['tell'] + d['questions'] + [x.get('q','') + ' ' + x.get('a','') for x in d['faqs'] if isinstance(x, dict)])))
    if words < 520: errs.append(f'too short {words}')
    return sorted(set(errs))

def call(msgs):
    body = json.dumps({'model': 'deepseek-chat', 'messages': msgs, 'temperature': 0.7, 'response_format': {'type': 'json_object'}, 'max_tokens': 3000}).encode()
    req = urllib.request.Request('https://api.deepseek.com/chat/completions', data=body,
                                 headers={'Content-Type': 'application/json', 'Authorization': 'Bearer ' + KEY})
    with urllib.request.urlopen(req, timeout=180) as r:
        return json.loads(r.read())['choices'][0]['message']['content']

def one(slug):
    path = os.path.join(OUT, slug + '.json')
    if os.path.exists(path): return slug, 'cached'
    p, t, f, fx = facts(slug)
    user = 'Search phrase: "' + fx['search_phrase'] + '"\nFACTS (the only regulator facts you may use):\n' + json.dumps(fx, ensure_ascii=False, indent=1) + '\n\n' + SCHEMA
    msgs = [{'role': 'system', 'content': SYSTEM}, {'role': 'user', 'content': user}]
    last = None
    for attempt in range(4):
        try:
            raw = call(msgs); d = json.loads(raw)
        except Exception as e:
            last = str(e); time.sleep(3); continue
        errs = check(d, slug)
        if not errs:
            with open(path, 'w') as fh: json.dump(d, fh, ensure_ascii=False, indent=1)
            return slug, f'ok (attempt {attempt+1})'
        last = errs
        msgs = msgs[:2] + [{'role': 'assistant', 'content': raw}, {'role': 'user', 'content': 'Fix these problems and return the full JSON again: ' + '; '.join(errs)}]
    return slug, f'FAILED {last}'

# ---- Supplement for pages that still share too much with near-identical searches (twins, same topic).
EXTRA_SCHEMA = """Write ONE extra section for this search that adds new, useful information not already covered by the existing copy below (and not covered by similar searches listed). Return JSON:
{"extra_heading": "short H2", "extra": ["3 paragraphs, 2-3 sentences each, specific to this exact search phrase"],
 "extra_faqs": [{"q": "...", "a": "2-3 sentences"}, {"q": "...", "a": "..."}]}
Ideas: what this particular wording of the search suggests about the person's situation, how to compare options for exactly this need, common misunderstandings, what happens at the first appointment for this need, or how this need differs from closely related services."""

def check_extra(d, slug):
    errs = []
    if not isinstance(d.get('extra_heading'), str) or not isinstance(d.get('extra'), list) or len(d['extra']) < 3 \
       or not isinstance(d.get('extra_faqs'), list) or len(d['extra_faqs']) < 2:
        return ['wrong shape']
    full = {'intro': 'x', 'need_heading': 'x', 'need_lead': 'x', 'needs': ['x'] * 5, 'context_heading': d['extra_heading'],
            'context': d['extra'], 'tell_heading': 'x', 'tell': ['x'] * 6, 'questions_heading': 'x', 'questions': ['x?'] * 6,
            'faqs': d['extra_faqs'] + d['extra_faqs']}
    return [e for e in check(full, slug) if not e.startswith('too short')]

def extra(slug, similar):
    path = os.path.join(OUT, slug + '.json')
    cur = json.load(open(path))
    if 'extra' in cur: return slug, 'cached'
    p, t, f, fx = facts(slug)
    others = []
    for s in similar[:4]:
        try:
            o = json.load(open(os.path.join(OUT, s + '.json'))); others.append({'search': gen.phrase(s), 'headings': [o['need_heading'], o['context_heading']], 'intro': o['intro']})
        except Exception: pass
    user = ('Search phrase: "' + fx['search_phrase'] + '"\nFACTS:\n' + json.dumps(fx, ensure_ascii=False, indent=1)
            + '\n\nEXISTING COPY FOR THIS SEARCH:\n' + json.dumps(cur, ensure_ascii=False)
            + '\n\nSIMILAR SEARCHES ALREADY COVERED ELSEWHERE:\n' + json.dumps(others, ensure_ascii=False) + '\n\n' + EXTRA_SCHEMA)
    msgs = [{'role': 'system', 'content': SYSTEM}, {'role': 'user', 'content': user}]
    last = None
    for attempt in range(4):
        try:
            raw = call(msgs); d = json.loads(raw)
        except Exception as e:
            last = str(e); time.sleep(3); continue
        errs = check_extra(d, slug)
        if not errs:
            cur.update({k: d[k] for k in ('extra_heading', 'extra', 'extra_faqs')})
            with open(path, 'w') as fh: json.dump(cur, fh, ensure_ascii=False, indent=1)
            return slug, f'extra ok ({attempt+1})'
        last = errs
        msgs = msgs[:2] + [{'role': 'assistant', 'content': raw}, {'role': 'user', 'content': 'Fix these problems and return the full JSON again: ' + '; '.join(errs)}]
    return slug, f'extra FAILED {last}'


if __name__ == '__main__' and sys.argv[1:2] == ['--extra']:
    # usage: --extra slugs.txt  (one slug per line)
    want = [l.strip() for l in open(sys.argv[2]) if l.strip()]
    by_topic = {}
    allslugs = [f[:-5] for f in os.listdir(OUT)]
    for s in allslugs: by_topic.setdefault(T[parse(s)['key']]['family'], []).append(s)
    def run(s):
        sim = sorted((x for x in by_topic[T[parse(s)['key']]['family']] if x != s), key=lambda x: (parse(x)['key'] != parse(s)['key'], x))
        return extra(s, sim)
    with cf.ThreadPoolExecutor(16) as ex:
        for s, r in ex.map(run, want): print(s, r, flush=True)
    sys.exit(0)

if __name__ == '__main__':
    os.makedirs(OUT, exist_ok=True)
    slugs = sys.argv[1:] or [l.strip() for l in open(os.path.join(WEB, 'pages', '..', 'data', 'keywords', 'PS-KEYWORDS-P0.txt')) if l.strip()]
    slugs = [s for s in slugs if os.path.exists(os.path.join(WEB, 'pages', 'keywords', s + '.md')) or len(sys.argv) > 1]
    with cf.ThreadPoolExecutor(16) as ex:
        for s, r in ex.map(one, slugs):
            print(s, r, flush=True)
