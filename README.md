<div align="center">

# ⚡ STM Smart Checkout for WooCommerce

### Fokussierte Kasse · DACH-Rechtsangaben · arbeitet mit Germanized statt gegen es

[![License](https://img.shields.io/badge/License-GPLv2%2B-blue?style=flat-square)](#lizenz)
[![WordPress](https://img.shields.io/badge/WordPress-6.5%2B-21759B?style=flat-square&logo=wordpress)](#kompatibilität)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.3%2B-96588A?style=flat-square&logo=woocommerce)](#kompatibilität)
[![PHP](https://img.shields.io/badge/PHP-7.4%E2%80%938.4-777BB4?style=flat-square&logo=php)](#kompatibilität)
[![Preis](https://img.shields.io/badge/Preis-kostenlos-brightgreen?style=flat-square)](#pricing)

[🖥️ **Live-Demo**](https://www.storetown-media.de/kasse/?add-to-cart=56628) · [📦 **Download & Hub**](https://www.storetown-media.de/produkt-kategorie/downloads/wordpress-plugins/) · [⭐ **Pro-Version**](#pro-version)

</div>

> ✅ **Freie Software, GPLv2 oder später.** Dieses Repository enthält den vollständigen Quellcode. Das Plugin ist beim WordPress-Plugin-Verzeichnis eingereicht; bis zur Freigabe erhalten Sie es als ZIP über den [Download-Hub](https://www.storetown-media.de/produkt-kategorie/downloads/wordpress-plugins/).

---

## Das Problem

> „Die Kasse ist die Seite, auf der ein Shop sein Geld verdient — und die einzige, die kaum jemand gestaltet."

Die Standard-Kasse von WooCommerce ist funktional, aber sie führt nicht. Theme-Kopf, Menü und Footer lenken vom Abschluss ab, die Bestellübersicht liegt als graue Tabelle da, und der Kaufknopf sitzt selten dort, wo die Entscheidung fällt.

Dazu kommt im DACH-Raum eine zweite Frage: **Was passiert, wenn kein Rechts-Plugin installiert ist?** Dann fehlen Einwilligung, § 312j-Knopftext, MwSt.-Ausweis und Lieferzeit — und die Kasse ist nicht nur unschön, sondern angreifbar.

## Die Lösung

**STM Smart Checkout** ordnet die WooCommerce-Kasse: Kopfband mit Fortschritt und Trust-Zeile, nummerierte Schritte, Bestellübersicht als ruhige Karte, ein Kaufknopf an der richtigen Stelle. Ein bis drei Spalten, im Backend umschaltbar, Farben und Schriftgröße ohne eine Zeile CSS.

Und es folgt **einer Regel**: *Liefert ein Rechts-Plugin die Pflichtangabe, treten wir zurück. Liefert keines, bleibt die Kasse trotzdem nicht nackt.* Germanized und German Market behalten ihre Hoheit — erkannt wird pro Angabe, teils sogar pro Produkt.

## Features

- 🎨 **Drei Spalten-Layouts** — eine, zwei oder drei Spalten, im Backend umschaltbar
- 🎯 **Ablenkungsfreier Modus** — eigene Vollseiten-Vorlage für Themes ohne Adapter; Theme-Kopf, Menü und dekorativer Footer weg, Rechtslinks bleiben
- 🖌️ **Design über Tokens** — Akzent-, Überschriften-, Beschriftungs-, Text- und Flächenfarben, Schriftgröße in Pixeln, Eckenradius. Ohne `!important`-Kämpfe mit dem Theme
- 📍 **PLZ-Autovervollständigung** — Deutschland, Österreich, Schweiz aus mitgelieferten Daten (GeoNames, CC BY 4.0). **Kein externer Dienst, keine Datenweitergabe**
- ⚖️ **Pflichtangaben ohne Rechts-Plugin** — eigene Einwilligung zu AGB und Widerruf, § 312j-Knopfbeschriftung, MwSt.-Ausweisung bei Bruttopreisen, Lieferzeit je Artikel
- 📄 **Rechtstexte im Overlay** — AGB und Widerrufsbelehrung lesen, ohne die Kasse zu verlassen; Rechtsklick öffnet die Seite weiterhin normal
- 🛡️ **Serverseitige Absicherung** — Pflichthäkchen werden nach dem Absenden erneut geprüft, und die Einwilligung wird mit exaktem Wortlaut auf die Bestellung geschrieben
- 📱 **Mobil gedacht** — richtige Tastaturen, treffsichere Felder, Zahlarten immer einzeilig
- 🧾 **Bestellübersicht mit Mengen** — Plus/Minus direkt in der Kasse, Bestand und Staffelpreise serverseitig geprüft
- 📦 **Versand-Integrationen** — Shiptastic und DHL fügen sich ein, statt den Feldfluss zu unterbrechen
- 👀 **Vorschau-Modus** — die neue Kasse im Live-Shop ansehen, bevor Kunden sie sehen. Und wieder verlassen
- 🌐 **Deutsch und Englisch** — vollständig übersetzt, Sie-Form, HPOS-kompatibel

## Klassische Kasse oder Block?

Dieses Plugin gestaltet den **klassischen** Warenkorb und die klassische Kasse. Baut Ihr Shop beide Seiten aus den WooCommerce-Blöcken, laufen die Einstellungen ins Leere — deshalb **sagt das Plugin es Ihnen** und bietet die Umstellung als einen Klick an. Das Block-Markup wird gesichert, der Rückweg ist ebenfalls ein Klick. Ohne diesen Klick wird nichts verändert.

## Zielgruppe

- WooCommerce-Shops im DACH-Raum mit Abbrüchen in der Kasse
- Shops **ohne** Rechts-Plugin, denen Pflichtangaben fehlen
- Shops **mit** Germanized oder German Market, die Optik wollen ohne Doppelungen
- Agenturen, die eine Kasse gestalten wollen, ohne Templates zu forken
- Shops mit hohem Mobilanteil

## Pricing

| | Für | Preis |
|---|---|---|
| **Smart Checkout** | alle oben genannten Funktionen | **kostenlos** (GPLv2+) |
| **Smart Checkout Pro** | zusätzlich Widerrufsformular, Sticky-Bestellleiste, Ultra-kompakt | **99,00 €** |

Alles, was auf dieser Seite beschrieben ist, steckt in der kostenlosen Version und bleibt dort.

## Kompatibilität

| Anforderung | Details |
|---|---|
| **WordPress** | 6.5 oder neuer (getestet bis 7.1) |
| **WooCommerce** | 8.3 oder neuer (getestet bis 11.0) |
| **PHP** | 7.4 · 8.0 · 8.1 · 8.2 · 8.3 · 8.4 |
| **Kasse** | klassische Shortcode-Kasse (Block-Kasse: Ein-Klick-Umstellung im Plugin) |
| **Rechts-Plugins** | Germanized, German Market — Koexistenz statt Konkurrenz |
| **Weiteres** | HPOS · Shiptastic + DHL · Themes mit und ohne Adapter |

## Installation

**Per ZIP (heute):**

1. ZIP über den [Download-Hub](https://www.storetown-media.de/produkt-kategorie/downloads/wordpress-plugins/) laden
2. **Plugins → Installieren → Plugin hochladen**
3. Aktivieren — die Einstellungen liegen unter **WooCommerce → Smart Checkout**
4. Erst ansehen: Vorschau-Modus benutzen. Erst dann **Smart Checkout aktivieren** einschalten

**Aus dem Plugin-Verzeichnis:** nach der Freigabe direkt über **Plugins → Installieren**.

## Live ausprobieren

| | |
|---|---|
| **Frontend-Demo** | [Kasse mit gefülltem Warenkorb öffnen](https://www.storetown-media.de/kasse/?add-to-cart=56628) |
| **Download-Hub** | [storetown-media.de → WordPress-Plugins](https://www.storetown-media.de/produkt-kategorie/downloads/wordpress-plugins/) |

## Pro-Version

Die kostenpflichtige Erweiterung ergänzt **drei** Bausteine — mehr nicht, und die Liste ist vollständig:

- **Online-Widerrufsformular** samt Verwaltung unter WooCommerce → Widerrufe
- **Mobile Sticky-Bestellleiste** mit Summe und Kaufknopf, solange der echte Knopf außer Sicht ist
- **Ultra-kompaktes Layout** — der Dreispalter, dichter gesetzt

Pro ist ein Add-on, kein zweiter Checkout: es setzt dieses Plugin voraus und ersetzt es nie. **Die Lizenz sperrt keine Funktion** — sie steuert Updates und Support. Eine abgelaufene Lizenz nimmt Ihnen keine Rechtsfunktion weg.

## Support

- **Fragen vor der Installation:** Issue im [Issues-Tab](../../issues)
- **E-Mail:** support@storetown-media.de
- **Übersetzungsfehler oder Rechtsthemen:** gern als Issue mit Screenshot

## Über Storetown Media

E-Commerce-Agentur aus Tornesch bei Hamburg, seit 2012. Mehr unter [storetown-media.de](https://www.storetown-media.de/) oder im [Org-Profil](https://github.com/storetown-media).

## Lizenz

**GPLv2 oder später** — siehe [Plugin-Header](stm-smart-checkout.php) und [gnu.org](https://www.gnu.org/licenses/gpl-2.0.html).

Die mitgelieferten Postleitzahl-Daten stammen von **GeoNames** und stehen unter **CC BY 4.0**; die Herkunft ist in [`data/postcode/CREDITS.txt`](data/postcode/CREDITS.txt) und in der `readme.txt` ausgewiesen.
