F = {}
for m in ('families_a','families_b','families_c','families_d','families_e','families_f','families_g'):
    F.update(__import__(m).F)
