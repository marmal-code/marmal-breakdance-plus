<?php
/**
 * Modul Ochrana osobních údajů – text stránky podle čl. 13 GDPR a zákona č. 110/2019 Sb.
 *
 * Každý účel má stejnou strukturu (čl. 13 odst. 1 písm. c, odst. 2 písm. a, e): jaké údaje · proč ·
 * právní základ · jak dlouho · zda je poskytnutí povinné. Části, které se mění, jsou shortcody.
 * Chybějící údaje (hosting, e-mailová služba…) jsou zvýrazněné „DOPLŇTE“.
 */

namespace MarmalElements\Gdpr;

if (!defined('ABSPATH')) {
    exit;
}

function b_p(string $html): string
{
    return "<!-- wp:paragraph -->\n<p>" . $html . "</p>\n<!-- /wp:paragraph -->\n\n";
}

function b_h(string $text, int $uroven = 2): string
{
    $u = $uroven === 3 ? 3 : 2;
    return '<!-- wp:heading' . ($u === 3 ? ' {"level":3}' : '') . " -->\n<h{$u} class=\"wp-block-heading\">" . esc_html($text) . "</h{$u}>\n<!-- /wp:heading -->\n\n";
}

function b_ul(array $polozky): string
{
    $o = "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
    foreach ($polozky as $p) {
        $o .= "<!-- wp:list-item -->\n<li>" . $p . "</li>\n<!-- /wp:list-item -->";
    }
    return $o . "</ul>\n<!-- /wp:list -->\n\n";
}

function b_sc(string $sc): string
{
    return "<!-- wp:shortcode -->\n" . $sc . "\n<!-- /wp:shortcode -->\n\n";
}

/** Řádek „Popisek: text“ s tučným popiskem (text je hotové HTML). */
function radek(string $popisek, string $html): string
{
    $dvojtecka = substr($popisek, -1) === '?' ? '' : ':';
    return '<strong>' . esc_html($popisek . $dvojtecka) . '</strong> ' . $html;
}

/** Jeden účel zpracování: nadpis + pět řádků podle čl. 13. */
function ucel(string $nadpis, string $udaje, string $proc, string $zaklad, string $doba, string $povinnost, array $navic = array()): string
{
    $r = array(
        radek(__('Jaké údaje', 'marmal-breakdance-plus'), esc_html($udaje)),
        radek(__('Proč', 'marmal-breakdance-plus'), esc_html($proc)),
        radek(__('Právní základ', 'marmal-breakdance-plus'), esc_html($zaklad)),
        radek(__('Jak dlouho', 'marmal-breakdance-plus'), esc_html($doba)),
        radek(__('Musíte údaje poskytnout?', 'marmal-breakdance-plus'), esc_html($povinnost)),
    );
    return b_h($nadpis, 3) . b_ul(array_merge($r, $navic));
}

/** Oddíl „Jaké údaje zpracováváme, proč a jak dlouho“ – jen zvolené účely + provoz webu a cookies vždy. */
function vzor_ucely(): string
{
    $o = '';
    if (je_ucel('dotazy')) {
        $o .= ucel(
            __('Dotazy a poptávky', 'marmal-breakdance-plus'),
            __('jméno a příjmení, e-mail, telefon, obsah zprávy, případně název organizace', 'marmal-breakdance-plus'),
            __('abychom vám odpověděli a případně připravili nabídku', 'marmal-breakdance-plus'),
            __('jednání o smlouvě na vaši žádost (čl. 6 odst. 1 písm. b) GDPR); u obecných dotazů náš oprávněný zájem na tom, abychom vám mohli odpovědět (čl. 6 odst. 1 písm. f) GDPR)', 'marmal-breakdance-plus'),
            __('po dobu vyřizování dotazu a poté nejvýše 1 rok, pokud z něj nevznikne smlouva', 'marmal-breakdance-plus'),
            __('ne, je to dobrovolné – bez kontaktu vám ale nemůžeme odpovědět', 'marmal-breakdance-plus')
        );
    }
    if (je_ucel('smlouvy')) {
        $o .= ucel(
            __('Objednávky a smlouvy', 'marmal-breakdance-plus'),
            __('jméno a příjmení nebo název, adresa, IČO a DIČ (u podnikatelů a organizací), e-mail, telefon, údaje o objednávce a platbě', 'marmal-breakdance-plus'),
            __('uzavření a splnění smlouvy, komunikace k objednávce, vyřízení reklamace', 'marmal-breakdance-plus'),
            __('plnění smlouvy (čl. 6 odst. 1 písm. b) GDPR); uplatnění a obhajoba právních nároků – náš oprávněný zájem (čl. 6 odst. 1 písm. f) GDPR)', 'marmal-breakdance-plus'),
            __('po dobu trvání smlouvy a poté do uplynutí promlčecích lhůt podle občanského zákoníku – zpravidla 3 roky, výjimečně až 10 let', 'marmal-breakdance-plus'),
            __('ano, je to smluvní požadavek – bez údajů smlouvu nemůžeme uzavřít ani splnit', 'marmal-breakdance-plus')
        );
    }
    if (je_ucel('akce')) {
        $o .= ucel(
            __('Přihlášky na akce, kurzy a workshopy', 'marmal-breakdance-plus'),
            __('jméno a kontakt objednatele nebo kontaktní osoby (u školy či školky i název a adresa organizace), termín, počet účastníků a další údaje potřebné k organizaci', 'marmal-breakdance-plus'),
            __('rezervace termínu, organizace akce a komunikace s vámi', 'marmal-breakdance-plus'),
            __('plnění smlouvy (čl. 6 odst. 1 písm. b) GDPR)', 'marmal-breakdance-plus'),
            __('do konání a vyúčtování akce; účetní doklady poté po dobu stanovenou zákonem', 'marmal-breakdance-plus'),
            __('ano, bez nich akci nemůžeme zorganizovat', 'marmal-breakdance-plus')
        );
    }
    if (je_ucel('foto')) {
        $o .= ucel(
            __('Fotografie a videa z akcí', 'marmal-breakdance-plus'),
            __('fotografie a videozáznamy z akcí, na kterých můžete být vy nebo vaše dítě', 'marmal-breakdance-plus'),
            __('prezentace naší činnosti na webu a na sociálních sítích', 'marmal-breakdance-plus'),
            __('váš souhlas (čl. 6 odst. 1 písm. a) GDPR) a svolení s užitím podobizny podle § 84 a násl. občanského zákoníku; za dítě souhlas dává zákonný zástupce. Fotografie, na kterých nikoho nelze rozpoznat, souhlas nevyžadují.', 'marmal-breakdance-plus'),
            __('do odvolání souhlasu – poté fotografie odstraníme z webu i z našich profilů na sociálních sítích', 'marmal-breakdance-plus'),
            __('ne, je to dobrovolné a odmítnutí nemá vliv na účast na akci', 'marmal-breakdance-plus')
        );
    }
    if (je_ucel('newsletter')) {
        $o .= ucel(
            __('Newsletter a obchodní sdělení', 'marmal-breakdance-plus'),
            __('e-mail, případně jméno', 'marmal-breakdance-plus'),
            __('zasílání novinek a nabídek', 'marmal-breakdance-plus'),
            __('váš souhlas (čl. 6 odst. 1 písm. a) GDPR); stávajícím zákazníkům můžeme nabídky podobných služeb posílat i bez souhlasu na základě oprávněného zájmu (čl. 6 odst. 1 písm. f) GDPR a § 7 odst. 3 zákona č. 480/2004 Sb., o některých službách informační společnosti)', 'marmal-breakdance-plus'),
            __('dokud se neodhlásíte', 'marmal-breakdance-plus'),
            __('ne, je to dobrovolné', 'marmal-breakdance-plus'),
            array(radek(__('Odhlášení', 'marmal-breakdance-plus'), esc_html__('odkazem v každém e-mailu nebo zprávou na', 'marmal-breakdance-plus') . ' [marmal_gdpr_kontakt]'))
        );
    }
    if (je_ucel('socialni')) {
        $o .= ucel(
            __('Profily na sociálních sítích', 'marmal-breakdance-plus'),
            __('jméno nebo přezdívka profilu, komentáře, zprávy a reakce; souhrnné statistiky návštěvnosti stránky', 'marmal-breakdance-plus'),
            __('komunikace s vámi a prezentace naší činnosti', 'marmal-breakdance-plus'),
            __('náš oprávněný zájem na komunikaci a prezentaci (čl. 6 odst. 1 písm. f) GDPR)', 'marmal-breakdance-plus'),
            __('po dobu existence profilu, nebo dokud příspěvek či zprávu nesmažete', 'marmal-breakdance-plus'),
            __('ne, je to dobrovolné', 'marmal-breakdance-plus'),
            array(radek(__('Společný správce', 'marmal-breakdance-plus'), esc_html__('u statistik stránky na Facebooku a Instagramu jsme společnými správci se společností Meta Platforms Ireland Limited. Jak údaje zpracovává Meta, popisují její', 'marmal-breakdance-plus')
                . ' <a href="https://www.facebook.com/privacy/policy/" target="_blank" rel="noopener">' . esc_html__('zásady ochrany soukromí', 'marmal-breakdance-plus') . '</a>.'))
        );
    }
    if (je_ucel('uchazeci')) {
        $o .= ucel(
            __('Uchazeči o zaměstnání', 'marmal-breakdance-plus'),
            __('údaje z životopisu a motivačního dopisu, kontakt, údaje z pohovoru', 'marmal-breakdance-plus'),
            __('výběrové řízení', 'marmal-breakdance-plus'),
            __('jednání o smlouvě (čl. 6 odst. 1 písm. b) GDPR); uchování pro budoucí nabídky jen s vaším souhlasem (čl. 6 odst. 1 písm. a) GDPR)', 'marmal-breakdance-plus'),
            __('do skončení výběrového řízení; s vaším souhlasem nejvýše 1 rok', 'marmal-breakdance-plus'),
            __('ne, je to dobrovolné – bez údajů vás ale nemůžeme do řízení zařadit', 'marmal-breakdance-plus')
        );
    }
    if (je_ucel('ucetnictvi')) {
        $o .= ucel(
            __('Fakturace a účetnictví', 'marmal-breakdance-plus'),
            __('údaje uvedené na fakturách a dalších účetních dokladech (jméno nebo název, adresa, IČO, DIČ, předmět a cena plnění)', 'marmal-breakdance-plus'),
            __('vedení účetnictví nebo daňové evidence', 'marmal-breakdance-plus'),
            __('plnění právní povinnosti (čl. 6 odst. 1 písm. c) GDPR) – zejména zákon č. 563/1991 Sb., o účetnictví, zákon č. 586/1992 Sb., o daních z příjmů, a zákon č. 235/2004 Sb., o dani z přidané hodnoty', 'marmal-breakdance-plus'),
            __('po dobu stanovenou těmito zákony – zpravidla 5 až 10 let od konce roku, kterého se doklad týká', 'marmal-breakdance-plus'),
            __('ano, je to zákonný požadavek', 'marmal-breakdance-plus')
        );
    }
    // Vždy: provoz webu.
    $o .= ucel(
        __('Provoz a zabezpečení webu', 'marmal-breakdance-plus'),
        __('IP adresa, typ prohlížeče, čas a adresa navštívené stránky (záznamy serveru)', 'marmal-breakdance-plus'),
        __('zajištění provozu a bezpečnosti webu, ochrana před útoky', 'marmal-breakdance-plus'),
        __('náš oprávněný zájem na bezpečném provozu webu (čl. 6 odst. 1 písm. f) GDPR)', 'marmal-breakdance-plus'),
        __('krátkodobě, po dobu nastavenou poskytovatelem hostingu – obvykle nejvýše několik týdnů', 'marmal-breakdance-plus'),
        __('údaje vznikají automaticky při návštěvě webu', 'marmal-breakdance-plus')
    );
    // Vždy: cookies.
    $o .= b_h(__('Cookies', 'marmal-breakdance-plus'), 3) . b_sc('[marmal_gdpr_cookies]');
    return $o;
}

/** Celý obsah stránky Zásady ochrany osobních údajů. */
function vzor_gdpr(): string
{
    $web = esc_html(wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES));

    $o  = b_p(sprintf(
        /* translators: %s: název webu */
        esc_html__('Na této stránce vysvětlujeme, jak na webu %s a při naší činnosti zpracováváme osobní údaje. Informace poskytujeme podle čl. 13 nařízení Evropského parlamentu a Rady (EU) 2016/679 (GDPR) a zákona č. 110/2019 Sb., o zpracování osobních údajů.', 'marmal-breakdance-plus'),
        $web
    ));

    $o .= b_h(__('V kostce', 'marmal-breakdance-plus'));
    $o .= b_ul(array(
        esc_html__('Zpracováváme jen údaje, které potřebujeme pro účely popsané níže – nic navíc.', 'marmal-breakdance-plus'),
        esc_html__('Vaše údaje neprodáváme.', 'marmal-breakdance-plus'),
        esc_html__('Analytické a marketingové cookies používáme jen s vaším souhlasem.', 'marmal-breakdance-plus'),
        esc_html__('Máte právo na přístup k údajům, jejich opravu, výmaz a další práva – stačí napsat na', 'marmal-breakdance-plus') . ' [marmal_gdpr_kontakt].',
    ));

    $o .= b_h(__('Kdo je správce a jak nás kontaktovat', 'marmal-breakdance-plus'));
    $o .= b_sc('[marmal_gdpr_spravce]');

    $o .= b_h(__('Jaké údaje zpracováváme, proč a jak dlouho', 'marmal-breakdance-plus'));
    $o .= b_p(esc_html__('U každého účelu uvádíme, jaké údaje zpracováváme, z jakého důvodu, na jakém právním základě a jak dlouho je uchováváme. Údaje získáváme přímo od vás.', 'marmal-breakdance-plus'));
    $o .= vzor_ucely();

    $o .= b_h(__('Komu údaje předáváme', 'marmal-breakdance-plus'));
    $o .= b_p(esc_html__('Při provozu webu a naší činnosti využíváme služby těchto zpracovatelů a příjemců:', 'marmal-breakdance-plus'));
    $o .= b_sc('[marmal_gdpr_prijemci]');
    $o .= b_p(esc_html__('Zpracovatelé zpracovávají údaje jen podle našich pokynů a na základě smlouvy o zpracování (čl. 28 GDPR). Vaše údaje neprodáváme ani nepředáváme k marketingovým účelům třetím osobám.', 'marmal-breakdance-plus'));

    $o .= b_h(__('Předávání údajů mimo EU', 'marmal-breakdance-plus'));
    $o .= b_p(esc_html__('Údaje zpracováváme především v Evropské unii. Některé služby (například Google, Meta nebo Microsoft) mohou údaje zpracovávat i mimo EU, zejména v USA – vždy jen se zárukami podle kapitoly V GDPR, tedy na základě rozhodnutí Evropské komise o odpovídající ochraně nebo standardních smluvních doložek.', 'marmal-breakdance-plus'));

    $o .= b_h(__('Automatizované rozhodování', 'marmal-breakdance-plus'));
    $o .= b_p(esc_html__('Nedochází k automatizovanému rozhodování ani profilování, které by pro vás mělo právní účinky nebo se vás obdobně významně dotýkalo (čl. 22 GDPR).', 'marmal-breakdance-plus'));

    $o .= b_h(__('Jak údaje chráníme', 'marmal-breakdance-plus'));
    $o .= b_p(esc_html__('Údaje chráníme přiměřenými technickými a organizačními opatřeními: web používá zabezpečené připojení (HTTPS), přístup k údajům mají jen pověřené osoby a naši zpracovatelé jsou vázáni smlouvou a mlčenlivostí.', 'marmal-breakdance-plus'));

    $o .= b_h(__('Vaše práva', 'marmal-breakdance-plus'));
    $o .= b_p(esc_html__('V souvislosti se zpracováním osobních údajů máte tato práva:', 'marmal-breakdance-plus'));
    $o .= b_ul(array(
        esc_html__('právo na přístup k údajům (čl. 15 GDPR) – potvrdíme, zda vaše údaje zpracováváme, a poskytneme vám jejich kopii,', 'marmal-breakdance-plus'),
        esc_html__('právo na opravu nepřesných údajů (čl. 16 GDPR),', 'marmal-breakdance-plus'),
        esc_html__('právo na výmaz (čl. 17 GDPR) – pokud údaje už nepotřebujeme, odvoláte souhlas nebo vznesete oprávněnou námitku a výmazu nebrání zákonná povinnost,', 'marmal-breakdance-plus'),
        esc_html__('právo na omezení zpracování (čl. 18 GDPR),', 'marmal-breakdance-plus'),
        esc_html__('právo na přenositelnost údajů (čl. 20 GDPR) – u údajů, které zpracováváme automatizovaně na základě souhlasu nebo smlouvy,', 'marmal-breakdance-plus'),
        esc_html__('právo vznést námitku (čl. 21 GDPR) proti zpracování na základě oprávněného zájmu a kdykoli proti zasílání obchodních sdělení,', 'marmal-breakdance-plus'),
        esc_html__('právo kdykoli odvolat souhlas (čl. 7 odst. 3 GDPR) – odvolání nemá vliv na zákonnost zpracování před ním.', 'marmal-breakdance-plus'),
    ));
    $o .= b_p(esc_html__('Svá práva můžete uplatnit e-mailem na', 'marmal-breakdance-plus') . ' [marmal_gdpr_kontakt] ' . esc_html__('nebo dopisem na adresu správce. Odpovíme bez zbytečného odkladu, nejpozději do 1 měsíce; ve složitých případech lze lhůtu prodloužit o další 2 měsíce (čl. 12 odst. 3 GDPR). Vyřízení je zdarma. Abychom údaje nevydali nesprávné osobě, můžeme vás požádat o ověření totožnosti.', 'marmal-breakdance-plus'));

    $o .= b_h(__('Stížnost u dozorového úřadu', 'marmal-breakdance-plus'));
    $o .= b_p(sprintf(
        /* translators: %s: odkaz na web ÚOOÚ */
        esc_html__('Pokud se domníváte, že zpracováním vašich osobních údajů porušujeme právní předpisy, můžete podat stížnost u Úřadu pro ochranu osobních údajů, Pplk. Sochora 27, 170 00 Praha 7 (%s). Budeme rádi, když se na nás nejdříve obrátíte – většinu věcí vyřešíme hned.', 'marmal-breakdance-plus'),
        '<a href="https://uoou.gov.cz/" target="_blank" rel="noopener">uoou.gov.cz</a>'
    ));

    $o .= b_h(__('Změny těchto zásad', 'marmal-breakdance-plus'));
    $o .= b_p(esc_html__('Zásady aktualizujeme, když se změní způsob zpracování údajů nebo právní předpisy. Aktuální verze je vždy na této stránce.', 'marmal-breakdance-plus'));
    $o .= b_p(esc_html__('Tyto zásady jsou účinné od', 'marmal-breakdance-plus') . ' [marmal_gdpr_ucinnost].');
    return $o;
}
