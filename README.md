# Woo Custom Product Options – FlexWave

## Overzicht
Deze plugin breidt WooCommerce uit met krachtige, centraal beheerde productopties, waaronder een geavanceerde lengtemodule voor producten met vaste en maatwerk lengtes. Volledig OOP, schaalbaar en future-proof.

---

## Belangrijkste features

- **Centrale optiegroepen**: beheer opties (zoals lengte, kleur, tekst, etc.) centraal in de bibliotheek.
- **Lengtemodule**:
  - Vaste lengtes (mm) met prijs per maat.
  - Maatwerk lengte (mm, met stapgrootte, min/max, validatie).
  - Prijsbepaling: maatwerk krijgt prijs van eerstvolgende vaste maat die ≥ gekozen maat.
  - Per product bepaal je welke vaste lengtes getoond worden.
- **Per product**: activeer optiegroepen en selecteer per optie welke variaties/maten zichtbaar zijn.
- **Volledige WooCommerce integratie**: opties in winkelwagen, checkout, order, prijsaanpassing, cart meta.
- **Toegankelijk, snel en schaalbaar**: OOP, hooks, sanitization, nonce, validatie, nette admin UI.

---

## Installatie
1. Upload de plugin naar `wp-content/plugins/woo-custom-product-options`.
2. Activeer via het WordPress admin menu.
3. Ga naar **FW Optiegroepen** om centrale opties aan te maken.
4. Ga naar een product en activeer gewenste optiegroepen en variaties.

---

## Lengtemodule – Werking

### 1. Optiegroep aanmaken
- Kies type **Lengte**.
- Voeg vaste lengtes toe (in mm) met prijs per maat.
- Stel stapgrootte (mm), min/max maatwerk (optioneel) en maatwerk-optie in.

### 2. Per product
- Activeer de lengte-optiegroep.
- Vink per vaste maat aan welke zichtbaar zijn op dit product.

### 3. Frontend
- Klant kiest uit actieve vaste lengtes (dropdown).
- Optioneel: maatwerk lengte invoer (mm, stapgrootte, validatie).
- Prijs wordt automatisch bepaald volgens de logica:
  - Vaste maat: prijs van die maat.
  - Maatwerk: prijs van eerstvolgende vaste maat die ≥ gekozen maat.

### 4. Winkelwagen & order
- Gekozen lengte, type (vast/maatwerk), prijs-lengte worden als cart meta opgeslagen.
- Alles zichtbaar in winkelwagen, checkout en order.

---

## Hooks & Techniek
- WooCommerce hooks: `woocommerce_before_add_to_cart_button`, `woocommerce_add_cart_item_data`, `woocommerce_get_item_data`, `woocommerce_before_calculate_totals`, etc.
- OOP structuur, alles met FW_ prefix.
- Volledige validatie, sanitization, nonce, edge case handling.

---

## Edge cases
- Geen vaste lengtes actief → module uitgeschakeld.
- Maatwerk exact gelijk aan vaste maat → juiste prijs.
- Maatwerk buiten bereik → foutmelding.
- Lengtes altijd gesorteerd oplopend.

---

## Credits
- FlexWave – Jorian Beukens
- WooCommerce

---

## Changelog

### 2026-04-27
- Volledige centrale lengtemodule met vaste en maatwerk lengtes, per product instelbaar.
- Prijslogica op mm-niveau, edge cases afgevangen.
- Admin UI opgeschoond voor lengte-opties.
- Documentatie volledig bijgewerkt.

