# Changelog

## 0.4.0 – 2026-10-07
- Galerie Plus – efekty po najetí (Design → Dlaždice):
  - pohyb fotky: Přiblížení / Pomalý posun / Žádný,
  - kombinovatelné doplňky: Světelný přejezd, Barvy na dotek, Zaostření, Popisek zespodu (+ písmo popisku),
  - efekty reagují i na fokus z klávesnice, pohyb respektuje „omezit pohyb“, na dotykových zařízeních jsou popisky vidět trvale.
- Změna volby „Na mobilu“ se v builderu projeví hned.

## 0.3.0 – 2026-10-07
- Galerie Plus – Mozaika:
  - fotka vždy vyplní celou dlaždici bez ohledu na poměr stran (odolné vůči obecnému `img { height: auto }`),
  - 20 vzorů pro 2, 3 a 4 sloupce (např. velká vlevo + 3 pod sebou, široká nahoře + 3 dole, mozaika 3 × 3),
  - „Výška galerie“ místo výšky řádku – všechny vzory mají stejnou celkovou výšku,
  - mřížka s volitelným poměrem stran dlaždic, volba výřezu fotky (střed, nahoře, dole…),
  - vzory se generují z jednoho zdroje: `tools/vzory.py`.

## 0.2.0 – 2026-10-07
- Nový element **Galerie Plus** – varianta Mozaika:
  - vzory se zarovnanými okraji (Vysoký–4–vysoký, Velký vlevo/vpravo + 4, Vysoký vlevo + 4, Velký + 2, rovnoměrná mřížka), nastavitelné po breakpointech,
  - opakování vzoru, dlaždice „+N“ s počtem dalších fotek, neúplné řady se skrývají,
  - na mobilu automaticky mřížka 2 sloupce (lze vypnout),
  - zdroj: výběr z knihovny médií nebo ACF pole galerie,
  - video dlaždice (YouTube bez cookies, Vimeo, MP4),
  - vlastní lightbox bez závislostí (klávesnice, swipe, popisky).

## 0.1.1 – 2026-10-07
- Test automatických aktualizací z GitHubu.
- Přidán CHANGELOG.

## 0.1.0 – 2026-10-07
- Výchozí verze: kostra pluginu, složky pro Element Studio (`MarmalElements`), automatické aktualizace z GitHubu.
