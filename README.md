# GratReviews

Google-Bewertungen automatisch abrufen und im Stil von [GratFrame](https://www.gratframe.de) anzeigen –
Contao 5.7, PHP ≥ 8.3. Läuft auch ohne GratFrame (eigene Styles mit `--gr-*`-Variablen, Fallback auf `--gf-*`).

## Funktionen

- **Quellen** (Backend → Bewertungen):
  - *Google*: API-Schlüssel des Kunden, Profil per Suche (Firmenname, Ort) auswählen, Abruf per Cronjob im
    gewählten Intervall, „Jetzt abrufen“ in der Liste, Befehl `gratreviews:sync [id]`.
  - *Eigene Bewertungen*: von Hand gepflegt, z. B. ältere Bewertungen oder per Mail erhaltene.
- **Bewertungen** je Quelle: veröffentlichen/ausblenden und hervorheben – bleibt beim nächsten Abruf erhalten.
- **Inhaltselemente**:
  - *Bewertungen: Übersicht* – Gesamtnote mit Sternen, Anzahl, je Quelle eine Zeile, Links „Alle Bewertungen
    bei Google“ und „Bewertung schreiben“.
  - *Bewertungen: Liste* – Raster oder Slider, Anzahl, Reihenfolge, Mindeststerne, „nur mit Text“; lange Texte
    mit „Weiterlesen“.
  - Beide mit **Hinweis zur Echtheit** (§ 5b Abs. 3 UWG), Standardtext je nach Quelle oder eigener Text.
- **Datenschutz**: Profilbilder der Bewertenden werden lokal gespeichert (Standard `files/bewertungen`) und
  verkleinert ausgeliefert – Besucher laden nichts von Google. Links zu Google erst beim Klick.

## Google einrichten (je Kunde)

1. In der [Google Cloud Console](https://console.cloud.google.com/) mit dem Google-Konto des Kunden ein Projekt
   anlegen, Abrechnung aktivieren.
2. **Places API (New)** aktivieren.
3. Unter *APIs & Dienste → Anmeldedaten* einen API-Schlüssel erstellen und einschränken: Anwendung
   *IP-Adressen* (die des Webservers), API *Places API (New)*.
4. In Contao eine Quelle *Google* anlegen, Schlüssel eintragen, Firmenname suchen, Profil wählen, speichern –
   die ersten Bewertungen werden sofort abgerufen.

**Grenzen der Places API:** je Abruf höchstens die **5 relevantesten** Bewertungen (Auswahl durch Google, keine
Sortierung nach Datum). GratReviews sammelt sie, der Bestand wächst mit der Zeit; ältere Bewertungen lassen sich
als *Eigene Bewertungen* nachtragen. Gesamtnote und Anzahl gelten immer für alle Bewertungen des Profils.
Jeder Abruf wird über das Cloud-Projekt abgerechnet (Place Details mit Bewertungen).

## Cronjob

Der Abruf läuft über den Contao-Cron (stündlich prüfen, welche Quelle fällig ist). Ohne echten Cronjob löst Contao
ihn über Seitenaufrufe aus; zuverlässiger ist ein Cronjob beim Hoster, der `vendor/bin/contao-console contao:cron`
ausführt oder `https://<domain>/_contao/cron` aufruft.

## Styling

Variablen u. a. `--gr-star-color`, `--gr-star-size`, `--gr-card-bg`, `--gr-card-border`, `--gr-card-radius`,
`--gr-card-min`, `--gr-gap`, `--gr-slide-width`, `--gr-text-lines`, `--gr-summary-size`, `--gr-muted`.
Alle Selektoren in `:where()` – jedes Projekt-CSS überschreibt sie ohne Spezifitätskampf.

## Test ohne Google

`GRATREVIEWS_PLACES_URL` ersetzt die Basis-URL der Places API (z. B. ein lokaler Mock-Server).
