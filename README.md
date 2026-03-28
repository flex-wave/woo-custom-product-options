# 🌊 FlexWave Product Options v1.0.0

Centrale optiebibliotheek voor WooCommerce. Maak optiegroepen aan, wijs ze per product toe, en laat klanten live hun prijs opbouwen.

---

## Installatie

1. Upload map `flexwave-product-options/` naar `/wp-content/plugins/`
2. Activeer de plugin via WordPress → Plugins
3. Ga naar **FW Optiegroepen** en maak je eerste groepen aan
4. Open een product en vink de gewenste groepen aan in de **FlexWave** meta box

---

## Werking

### 1. Optiegroepen (centrale bibliotheek)
Ga naar **FW Optiegroepen → Groep toevoegen**. Kies een type:

| Type | Gebruik |
|---|---|
| 🔘 Keuze (radio) | Onderstel, poot, afmeting, materiaal |
| 🎨 Kleurstaal | Kleur blad, RAL-kleur, houtsoort |
| ✏️ Vrije tekst | Gravure, naam, opmerking |

Voeg variaties toe met naam en meerprijs. Afbeeldingen zijn optioneel.

### 2. Per product aanvinken
Op het product bewerkscherm → meta box **FlexWave – Actieve optiegroepen**:
- Vink aan welke groepen actief zijn
- Sleep rijen om de volgorde op de productpagina te bepalen

### 3. Prijsopbouw
- WooCommerce productprijs = **basisprijs**
- Gekozen opties tellen er **bovenop**
- Totaalprijs wordt live getoond op de productpagina
- De echte cartprijs wordt automatisch aangepast

---

## Mapstructuur

```
flexwave-product-options/
├── flexwave-product-options.php
├── includes/
│   ├── class-fw-library.php        ← CPT fw_option_group + variaties
│   ├── class-fw-product-meta.php   ← Groepen per product aanvinken
│   ├── class-fw-frontend.php       ← Frontend render (radio/kleur/tekst)
│   └── class-fw-pricing.php        ← WooCommerce prijsintegratie
└── assets/
    ├── frontend.js / .css          ← Live prijsberekening
    ├── library.js / .css           ← Bibliotheek admin
    └── product-meta.js / .css      ← Product admin
```

---

## Meta keys

| Key | Post type | Inhoud |
|---|---|---|
| `_fw_group_type` | `fw_option_group` | `radio` / `color` / `text` |
| `_fw_group_required` | `fw_option_group` | `1` of leeg |
| `_fw_variations` | `fw_option_group` | JSON array variaties |
| `_fw_active_groups` | `product` | JSON array group IDs (op volgorde) |

---

## Template tag

Standaard auto-inject via `woocommerce_before_add_to_cart_button`.  
Handmatig in je thema:

```php
<?php flexwave_render_options(); ?>
```

---

## Contact

Voor vragen of support:  
📧 jorian@flex-wave.nl
🌐 [flex-wave.nl](https://flex-wave.nl)
