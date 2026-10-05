import re
MODS = ['best','top','cheap','affordable','local','online','accredited','comparison','mobile','nationwide','packages','consultation','rated','fast']
SING = {'advisers':'advisor','adviser':'advisor','advisors':'advisor','lawyers':'solicitor','lawyer':'solicitor','solicitors':'solicitor',
 'conveyancers':'conveyancer','conveyancing':'conveyancer','accountants':'accountant','bookkeepers':'bookkeeper','architects':'architect',
 'consultants':'consultant','physiotherapists':'physiotherapist','dentists':'dentist','doctors':'doctor','gps':'gp','wills':'will','vets':'vet',
 'barristers':'barrister','surveyors':'surveyor','planners':'planner','brokers':'broker','osteopaths':'osteopath','chiropractors':'chiropractor',
 'opticians':'optician','counsellor':'counsellor','forensic':'forensic'}
def parse(slug):
    near = slug.endswith('-near-me')
    core = slug[:-8] if near else slug
    toks = core.split('-')
    mods = []
    rest = []
    i = 0
    while i < len(toks):
        t = toks[i]
        if t == 'fixed' and i+1 < len(toks) and toks[i+1] == 'fee':
            mods.append('fixed-fee'); i += 2; continue
        if t in MODS:
            mods.append(t)
        else:
            rest.append(t)
        i += 1
    plural = bool(rest) and rest[-1] in SING and rest[-1].endswith('s') and rest[-1] not in ('wills',)
    norm = [SING.get(t, t) for t in rest]
    key = '-'.join(norm)
    return dict(slug=slug, near=near, mods=mods, key=key, plural=plural, rest=rest)
