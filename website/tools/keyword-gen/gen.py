#!/usr/bin/env python3
"""Generate client-facing P0 keyword pages (markdown) for iComply Professional Services.
Run: python3 website/tools/keyword-gen/gen.py  (writes website/pages/keywords/<slug>.md)"""
import os, random, re, sys, hashlib
HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
from norm import parse
from topics import T
from families import F
from mods import MOD, FORM, ROLE

WEB = os.path.dirname(os.path.dirname(HERE))
OUT = os.path.join(WEB, 'pages', 'keywords')
SLUGS = [l.strip() for l in open(os.path.join(WEB, 'data', 'keywords', 'PS-KEYWORDS-P0.txt')) if l.strip()]
AGENCY = ('website', 'seo', 'digital-marketing', 'web-design')

CAPS = {'ifa':'IFA','gp':'GP','gps':'GPs','acl':'ACL','adhd':'ADHD','cbt':'CBT','emdr':'EMDR','eis':'EIS','seis':'SEIS','csop':'CSOP',
 'ir35':'IR35','cis':'CIS','cetv':'CETV','vat':'VAT','hmrc':'HMRC','llp':'LLP','acca':'ACCA','cima':'CIMA','amh':'AMH','icsi':'ICSI',
 'iui':'IUI','ivf':'IVF','ent':'ENT','ct':'CT','hr':'HR','it':'IT','oisc':'OISC','fue':'FUE','uk':'UK','xero':'Xero','quickbooks':'QuickBooks',
 'sage':'Sage','amazon':'Amazon','invisalign':'Invisalign','botox':'Botox','passivhaus':'Passivhaus','british':'British','24':'24'}
def phrase(slug):
    return ' '.join(CAPS.get(w, w) for w in slug.split('-'))
def title_phrase(slug):
    small = {'a','of','and','for','to','me'}
    out = []
    for i, w in enumerate(slug.split('-')):
        if w in CAPS: out.append(CAPS[w])
        elif w in small and i > 0 and w != 'me': out.append(w)
        else: out.append(w[:1].upper() + w[1:])
    return ' '.join(out).replace('24 Hour', '24-Hour')
def strip_article(s):
    return re.sub(r'^(a|an)\s+', '', s)
def cap(s):
    return s[:1].upper() + s[1:]

IMAGES = {
 'healthcare': ['healthcare-consult.jpg','hero-workshop.jpg','insurance-advisory.jpg','finance-desk.jpg'],
 'finance': ['insurance-advisory.jpg','finance-desk.jpg','hero-workshop.jpg','healthcare-consult.jpg'],
 'insurance': ['insurance-advisory.jpg','finance-desk.jpg','hero-workshop.jpg','healthcare-consult.jpg'],
 'accountancy': ['finance-desk.jpg','hero-workshop.jpg','insurance-advisory.jpg','healthcare-consult.jpg'],
 'legal': ['hero-workshop.jpg','finance-desk.jpg','insurance-advisory.jpg','healthcare-consult.jpg'],
 'property-prof': ['hero-workshop.jpg','finance-desk.jpg','insurance-advisory.jpg','healthcare-consult.jpg'],
 'consulting': ['hero-workshop.jpg','finance-desk.jpg','insurance-advisory.jpg','healthcare-consult.jpg'],
 'general': ['hero-workshop.jpg','insurance-advisory.jpg','finance-desk.jpg','healthcare-consult.jpg'],
}

def fmt(s, ctx):
    return s.format(**ctx)

def build(slug, siblings_by_family):
    p = parse(slug)
    topic = T[p['key']]
    fam = F[topic['family']]
    rng = random.Random(int(hashlib.sha256(slug.encode()).hexdigest(), 16))
    kw = phrase(slug)
    kwt = title_phrase(slug)
    lab = topic['label']; bare = strip_article(lab)
    ctx = dict(lab=lab, bare=bare, Bare=cap(bare), noun=fam['noun'], nouns=fam['nouns'], Nouns=cap(fam['nouns']),
               practice=fam['practice'], kw=kw)
    group = fam['group']
    hub = topic.get('hub') or fam['hub']
    imgs = IMAGES.get(group, IMAGES['general'])[:]
    rot = rng.randrange(len(imgs)); imgs = imgs[rot:] + imgs[:rot]
    alts = [
        f"Client explaining what they need before being matched with {lab}",
        f"{cap(fam['practice'])} ready to take on a new enquiry for {kw}",
        f"Next steps after an iComply introduction for {lab}",
        f"Professional reviewing a client's brief before an introduction for {kw}",
        f"Planning the first appointment after an enquiry for {kw}",
    ]
    rng.shuffle(alts)
    img = lambda i: f"![{alts[i]}](/assets/images/{imgs[i]})"
    form = 'near' if p['near'] else ('plural' if p['plural'] else 'bare')

    # ---- intro
    openers = {
     'near': [f"Searching for **{kw}**? You probably want someone suitable, available and within reach. {topic['what']}",
              f"If you have typed **{kw}** into a search bar, you are likely looking for help soon and not too far away. {topic['what']}",
              f"Looking for **{kw}** usually starts with a practical problem that needs a qualified person. {topic['what']}"],
     'plural': [f"There are many **{kw}** to choose from, and they are not all the same. {topic['what']}",
                f"Comparing **{kw}** can feel overwhelming when every website says something similar. {topic['what']}"],
     'bare': [f"Thinking about **{kw}**? {topic['what']}",
              f"Before you contact anyone about **{kw}**, it helps to know what to expect. {topic['what']}"],
    }
    middle = [
     f"iComply Professional Services is a free matching service. You tell us what you need and where you are; we connect you with a suitable UK {fam['noun']} who can quote for the work. The practice provides the service, and every quote is POA.",
     f"We act as the middleman between you and the professional. Share a short brief and we match you with {lab} who has the right experience and capacity. Enquiring is free, and you are under no obligation to accept a quote.",
     f"Rather than calling round, you can send one enquiry to iComply. We look at your needs, location and timing, then introduce you to a suitable {fam['practice']}. They quote you directly, POA, and you decide whether to go ahead.",
    ]
    md = []
    md.append(rng.choice(openers[form]))
    md.append('')
    md.append(rng.choice(middle))
    md.append('')
    md.append(f"**[Get a free quote](/contact/)** — tell us your town or postcode and a line or two about what you need.")
    md.append('')
    md.append(img(0)); md.append('')

    sections = []
    def sec(h, body):
        sections.append((h, body))

    # what / scenarios
    sc = rng.sample(fam['scenarios'], 5)
    sec(rng.choice(["What {nouns} commonly help with", "Common reasons people contact {nouns}", "Where {nouns} can help"]).format(**ctx),
        [rng.choice([f"{cap(fam['nouns'])} help clients in many situations. Some of the most common are:",
                     f"Every enquiry is different, but these are typical starting points for clients who contact {fam['nouns']}:",
                     f"You do not need to have everything worked out. Clients often come to us with situations like these:"])]
        + ['- ' + cap(x) + '.' for x in sc]
        + [f"If your situation is not listed, that is fine. Describe it in your own words and we will work out which kind of {fam['noun']} fits best."])
    # key points (topic-specific)
    sec(rng.choice(["Key things to know about {lab}", "Practical points about {lab}", "Before you choose {lab}"]).format(**ctx),
        [rng.choice([f"A few points come up again and again with {lab}:", f"These practical points are worth knowing before you speak to anyone about {lab}:"])]
        + ['- ' + x for x in topic['points']]
        + [rng.choice([f"A good {fam['noun']} will talk you through each of these in plain English.",
                       f"Raise any of these with the {fam['noun']} you are introduced to; they should be happy to explain."])])
    # modifiers
    for m in p['mods']:
        if m in MOD:
            hs, paras, _ = MOD[m]
            sec(rng.choice(hs).format(**ctx), [x.format(**ctx) for x in paras])
    # form
    hs, paras = FORM[form]
    sec(rng.choice(hs).format(**ctx), [x.format(**ctx) for x in paras])
    # role angle
    last = p['rest'][-1] if p['rest'] else ''
    rk = {'advisor':'advisor','advisors':'advisor','adviser':'advisor','advisers':'advisor','consultant':'consultant','consultants':'consultant',
          'specialist':'specialist','help':'help','broker':'broker','brokers':'broker','clinic':'clinic','lawyers':'lawyers','lawyer':'lawyers',
          'therapist':'therapist'}.get(last)
    role_txt = ROLE.get(rk) if rk else None
    # regulation
    reg = list(fam['regulator'])
    if role_txt: reg.append(role_txt)
    reg.append(rng.choice([
        f"iComply is not a regulator and does not provide the professional service itself. We introduce you to professionals who hold their own registrations, and we encourage you to check them on the public register.",
        f"Please note that iComply is a matching service only. We are not regulated as a {fam['noun']}, and we do not give professional advice. The practice you are introduced to is responsible for its own registration and for the work it does.",
    ]))
    sec(rng.choice(["Checking {nouns} are qualified and regulated", "Qualifications and registration to look for", "How to check you are in safe hands"]).format(**ctx), reg)
    # prepare
    pr = rng.sample(fam['prepare'], 6)
    sec(rng.choice(["What to prepare before you enquire", "Information that helps us match you", "Getting ready for your first conversation"]),
        [rng.choice(["You do not need all of this to enquire, but the more you can share, the better the match:",
                     "A short, clear brief saves time on both sides. Useful details include:"])]
        + ['- ' + cap(x) for x in pr]
        + ["Please do not send confidential records or full files in your first message. The practice will ask for what it needs securely once you are introduced."])
    # questions
    qs = rng.sample(fam['questions'], 6)
    sec(rng.choice(["Questions to ask {lab}", "Good questions for your first call", "What to ask before you instruct anyone"]).format(**ctx),
        [rng.choice([f"Once you are introduced, these questions help you decide whether the {fam['noun']} is right for you:",
                     "Asking a few direct questions early tells you a lot about how a practice works:"])]
        + ['- ' + x for x in qs])
    # fees
    sec(rng.choice(["How fees usually work (POA)", "Costs and quotes for {lab}", "What {lab} is likely to cost (POA)"]).format(**ctx), list(fam['fees']))
    # optional sections
    opt = []
    opt.append((rng.choice(["Warning signs to watch for", "Red flags when choosing {lab}", "When to think twice"]).format(**ctx),
        [rng.choice(["Most professionals are honest and competent, but it is worth knowing the warning signs:", "Be cautious if you notice any of the following:"])]
        + ['- ' + cap(x) for x in rng.sample(fam['red_flags'], 4)]))
    opt.append((rng.choice(["Typical timescales", "How long things usually take", "Timing and urgency"]), [fam['timeline']]))
    opt.append((rng.choice(["What happens after you are introduced", "After the introduction", "Your relationship with the practice"]), [fam['after']]))
    keep = rng.sample(opt, rng.choice([2, 3, 3]))
    for o in keep: sec(*o)
    # how it works
    steps_v = [
        ["**Tell us what you need.** Use the contact form, WhatsApp or phone. A few lines is enough.",
         f"**We review your brief.** We check the type of {fam['noun']} you need, your location and your timing.",
         "**We match you.** We introduce a suitable practice with capacity, usually within a few working days.",
         "**You get a quote.** The practice quotes you directly, POA. You decide whether to go ahead."],
        ["**Enquire for free** with your town or postcode and a short description.",
         "**We clarify** anything unclear, so the introduction is useful from the start.",
         f"**We connect you** with {lab} who suits your situation.",
         "**The practice takes over**, confirms scope and fees in writing and does the work."],
    ]
    sec(rng.choice(["How iComply matches you", "How our free matching service works", "From enquiry to quote in four steps"]),
        ['1. ' + s for s in rng.choice(steps_v)]
        + [rng.choice(["We are the middleman, not the provider: the professional you choose carries out the work and is responsible for it.",
                       "There is no charge to you for the introduction, and no obligation to accept any quote."])])

    # order middle sections
    first = sections[0]
    rest = sections[1:]
    rng.shuffle(rest)
    order = [first] + rest
    mid_img = len(order) // 2
    for i, (h, body) in enumerate(order):
        md.append('## ' + h); md.append('')
        for j, line in enumerate(body):
            md.append(line)
            nxt = body[j + 1] if j + 1 < len(body) else ''
            is_li = line.startswith(('- ', '1. '))
            if not is_li or not nxt.startswith(('- ', '1. ')):
                md.append('')
        if i == mid_img:
            md.append(img(1)); md.append('')
        if i == 2:
            md.append(f"**Need {lab}?** [Get a free quote](/contact/) or message us on [WhatsApp](https://wa.me/447517806082) — it takes two minutes.")
            md.append('')

    # FAQs
    faqs = list(fam['faqs'])
    for m in p['mods']:
        if m in MOD:
            q, a = MOD[m][2]; faqs.append((q.format(**ctx), a.format(**ctx)))
    near_q = rng.choice([f"How do I find {lab} near me?", f"Can you find {lab} in my area?"])
    faqs.append((near_q, f"Send us your town or postcode with a short description of what you need. We look for a suitable {fam['practice']} with capacity in your area, or one that can help remotely, and introduce you. It is free to enquire."))
    generic = [
        ("Is it free to use iComply?", "Yes. Enquiring and being matched is free for you. If a practice quotes for its professional work, that quote is between you and the practice, and it is POA."),
        ("Do I have to accept a quote?", "No. An introduction is not a contract. You can ask questions, decline, or ask us for another match where capacity allows."),
        ("Is iComply regulated?", f"iComply is a matching service. We are not a {fam['noun']} and we do not give professional advice. The professionals we introduce are registered with their own bodies, and you can check them."),
        ("How quickly will I hear back?", "Straightforward enquiries are usually reviewed within a few working days. Mark urgent needs clearly. Speed also depends on practice capacity near you."),
        ("Can I contact you on WhatsApp?", "Yes. You can message us on WhatsApp or call us, as well as using the enquiry form. Please keep sensitive details for the practice once you are introduced."),
    ]
    faqs += rng.sample(generic, 3)
    seenq = set(); fq = []
    for q, a in faqs:
        if q not in seenq: seenq.add(q); fq.append((q, a))
    md.append(img(2)); md.append('')
    md.append('## ' + rng.choice([f"FAQs about {kw}", f"{kwt}: FAQs", f"FAQs: {lab}"])); md.append('')
    for q, a in fq:
        md.append('### ' + q); md.append(''); md.append(a); md.append('')

    # related
    sibs = [s for s in siblings_by_family[topic['family']] if s != slug]
    rng.shuffle(sibs)
    rel = sibs[:5]
    md.append('## ' + rng.choice(["Related searches", "People also look for", "Other services you may need"])); md.append('')
    for s in rel:
        md.append(f"- [{cap(phrase(s))}](/keywords/{s}/)")
    md.append(f"- [Browse all {fam['nouns']}](/hubs/{hub}/)")
    md.append('')
    md.append('## ' + rng.choice([f"Get a free quote for {lab}", "Ready to be matched?", "Request your free quote"])); md.append('')
    md.append(rng.choice([
        f"Tell us what you need and where you are. We will match you with a suitable {fam['noun']}, and they will quote you directly. It is free to enquire, there is no obligation, and quotes are POA.",
        f"One short enquiry is all it takes. We connect you with {lab} who fits your brief, and you stay in control of whether to go ahead. Free to enquire; quotes are POA.",
    ]))
    md.append('')
    md.append("[Get a free quote](/contact/) · [Message us on WhatsApp](https://wa.me/447517806082) · Call [07517 806082](tel:+447517806082)")
    md.append('')

    # meta
    title = f"{kwt} | iComply Professional Services"
    canonical_form = not p['mods'] and not p['plural'] and '-'.join(p['rest']) == p['key']
    q = (lab + (' near you' if p['near'] else '')) if canonical_form else '"' + kw + '"'
    descs = [
        f"Looking for {q}? Tell iComply what you need and we match you with a suitable UK {fam['noun']}. Free to enquire. Request a quote — POA.",
        f"Need {q}? iComply connects you with a suitable UK {fam['practice']}. Free, no-obligation matching. Request a quote — POA.",
        f"Find {q} with iComply: one free enquiry, matched to a suitable UK {fam['noun']}. No obligation. Request a quote — POA.",
        f"{cap(q)}: get matched free with a suitable UK {fam['noun']} via iComply Professional Services. Request a quote — POA.",
    ]
    if q.startswith('"'):
        descs = descs[:3]  # front matter parser trims leading quotes
    rng.shuffle(descs)
    desc = min(descs, key=lambda d: 0 if 140 <= len(d) <= 160 else min(abs(len(d) - 140), abs(len(d) - 160)))
    if len(desc) < 115:
        desc = desc.replace('Request a quote — POA.', 'Free quotes from the practice. Request a quote — POA.')
    if len(desc) < 140:
        desc = desc.replace(' Request a quote — POA.', ' UK-wide. Request a quote — POA.')
    if len(desc) > 160:
        desc = desc.replace(' Professional Services', '')
    for a, b in ((' Free, no-obligation matching.', ' Free matching.'), (' No obligation.', ''), (' Free to enquire.', ' Free.'), ('wills and probate specialist', 'wills and probate firm')):
        if len(desc) > 160:
            desc = desc.replace(a, b)
    for filler in (' Fast replies.', ' No obligation.', ' Free.'):
        if len(desc) < 140 and len(desc) + len(filler) <= 160:
            desc = desc.replace(' Request a quote — POA.', filler + ' Request a quote — POA.')
    front = [
        '---', f'slug: {slug}', f'title: {title}', f'description: {desc}', f'family: {topic["family"]}',
        f'group: {group}', f'hub: {hub}', f'profession: {fam["noun"]}', f'service_label: {cap(bare)}', f'og_image: /assets/images/{imgs[0]}', '---', '',
        f'# {kwt}', '',
    ]
    return '\n'.join(front + md).rstrip() + '\n', desc

def main():
    os.makedirs(OUT, exist_ok=True)
    for f in os.listdir(OUT):
        if f.endswith('.md'): os.remove(os.path.join(OUT, f))
    good = [s for s in SLUGS if not any(a in s for a in AGENCY) and 'near-me-near-me' not in s]
    by_fam = {}
    for s in good:
        by_fam.setdefault(T[parse(s)['key']]['family'], []).append(s)
    lens = []
    for s in good:
        text, d = build(s, by_fam)
        lens.append(len(d))
        with open(os.path.join(OUT, s + '.md'), 'w') as fh:
            fh.write(text)
    print('wrote', len(good), 'pages; desc len', min(lens), max(lens))
if __name__ == '__main__':
    main()
