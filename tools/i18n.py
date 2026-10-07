#!/usr/bin/env python3
"""
Překlady pluginu Marmal – Breakdance Plus.

Zdrojový jazyk je čeština. Skript:
  1. najde v PHP všechny texty  __('…', 'marmal-breakdance-plus'), esc_html__, esc_attr__, _e …
  2. přepíše languages/marmal-breakdance-plus.pot,
  3. doplní nové texty do každého languages/marmal-breakdance-plus-<locale>.po
     (existující překlady zachová, nepoužívané označí komentářem #~),
  4. zkompiluje .mo soubory, které čte WordPress.

Použití:  python3 tools/i18n.py
Pak doplň prázdné msgstr v .po (např. v Poedit nebo ručně) a spusť skript znovu.
"""
import os
import re
import struct

DOMAIN = 'marmal-breakdance-plus'
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
LANG = os.path.join(ROOT, 'languages')

FUNC = r"(?:__|_e|esc_html__|esc_attr__|esc_html_e|esc_attr_e)"
PATTERN = re.compile(FUNC + r"\(\s*'((?:[^'\\]|\\.)*)'\s*,\s*'" + re.escape(DOMAIN) + r"'\s*\)")


def extract():
    found = {}
    for base, dirs, files in os.walk(ROOT):
        dirs[:] = [d for d in dirs if d not in ('.git', 'lib', 'tools', 'node_modules', 'dist')]
        for f in sorted(files):
            if not f.endswith('.php'):
                continue
            path = os.path.join(base, f)
            rel = os.path.relpath(path, ROOT)
            with open(path, encoding='utf-8') as fh:
                for no, line in enumerate(fh, 1):
                    for m in PATTERN.finditer(line):
                        msgid = m.group(1).replace("\\'", "'")
                        found.setdefault(msgid, []).append(f'{rel}:{no}')
    return found


def po_escape(s):
    return s.replace('\\', '\\\\').replace('"', '\\"').replace('\n', '\\n')


def po_unescape(s):
    return s.replace('\\n', '\n').replace('\\"', '"').replace('\\\\', '\\')


def read_po(path):
    """Vrátí (hlavička msgstr, {msgid: msgstr})."""
    entries, header = {}, ''
    if not os.path.exists(path):
        return header, entries
    cur_id = cur_str = None
    mode = None
    with open(path, encoding='utf-8') as fh:
        for line in fh:
            line = line.rstrip('\n')
            if line.startswith('#~'):
                continue
            if line.startswith('msgid '):
                if cur_id is not None:
                    entries[cur_id] = cur_str
                cur_id, cur_str, mode = po_unescape(line[7:-1]), '', 'id'
            elif line.startswith('msgstr '):
                cur_str, mode = po_unescape(line[8:-1]), 'str'
            elif line.startswith('"') and mode:
                val = po_unescape(line[1:-1])
                if mode == 'id':
                    cur_id += val
                else:
                    cur_str += val
        if cur_id is not None:
            entries[cur_id] = cur_str
    header = entries.pop('', '')
    return header, entries


def write_po(path, header, strings, translations, refs):
    out = ['msgid ""', 'msgstr ""']
    out += ['"' + po_escape(h + '\n') + '"' for h in header.strip('\n').split('\n') if h]
    out.append('')
    for msgid in strings:
        out.append('#: ' + ' '.join(refs[msgid]))
        if '%' in msgid:
            out.append('#, php-format')
        out.append('msgid "' + po_escape(msgid) + '"')
        out.append('msgstr "' + po_escape(translations.get(msgid, '')) + '"')
        out.append('')
    obsolete = [k for k in translations if k not in refs and translations[k]]
    for msgid in obsolete:
        out.append('#~ msgid "' + po_escape(msgid) + '"')
        out.append('#~ msgstr "' + po_escape(translations[msgid]) + '"')
        out.append('')
    with open(path, 'w', encoding='utf-8') as fh:
        fh.write('\n'.join(out))


def write_mo(path, header, translations):
    """Kompilace .mo (formát GNU gettext, little endian)."""
    items = {'': header}
    items.update({k: v for k, v in translations.items() if v})
    keys = sorted(items)
    ids = b''
    strs = b''
    offsets = []
    for k in keys:
        kb, vb = k.encode('utf-8'), items[k].encode('utf-8')
        offsets.append((len(ids), len(kb), len(strs), len(vb)))
        ids += kb + b'\0'
        strs += vb + b'\0'
    n = len(keys)
    keystart = 7 * 4 + 16 * n
    valuestart = keystart + len(ids)
    koffsets, voffsets = [], []
    for o1, l1, o2, l2 in offsets:
        koffsets += [l1, o1 + keystart]
        voffsets += [l2, o2 + valuestart]
    data = struct.pack('Iiiiiii', 0x950412de, 0, n, 7 * 4, 7 * 4 + n * 8, 0, 0)
    data += struct.pack('%di' % len(koffsets), *koffsets)
    data += struct.pack('%di' % len(voffsets), *voffsets)
    data += ids + strs
    with open(path, 'wb') as fh:
        fh.write(data)


def main():
    os.makedirs(LANG, exist_ok=True)
    refs = extract()
    strings = list(refs)
    pot_header = (
        'Project-Id-Version: Marmal – Breakdance Plus\n'
        'MIME-Version: 1.0\n'
        'Content-Type: text/plain; charset=UTF-8\n'
        'Content-Transfer-Encoding: 8bit\n'
        'X-Domain: ' + DOMAIN + '\n'
    )
    write_po(os.path.join(LANG, DOMAIN + '.pot'), pot_header, strings, {}, refs)
    print(f'{len(strings)} textů → {DOMAIN}.pot')

    for f in sorted(os.listdir(LANG)):
        if not (f.startswith(DOMAIN + '-') and f.endswith('.po')):
            continue
        locale = f[len(DOMAIN) + 1:-3]
        path = os.path.join(LANG, f)
        header, tr = read_po(path)
        if not header:
            header = pot_header + 'Language: ' + locale + '\n'
        write_po(path, header, strings, tr, refs)
        write_mo(path[:-3] + '.mo', header, {k: v for k, v in tr.items() if k in refs})
        missing = [s for s in strings if not tr.get(s)]
        print(f'{locale}: přeloženo {len(strings) - len(missing)}/{len(strings)}' + (f' – chybí: {missing}' if missing else ''))


if __name__ == '__main__':
    main()
