# Changelog

## 0.1.3 (2026-11-19)

- Neue Quellenart *ProvenExpert* (API-ID + API-Key): Gesamtnote und Anzahl in jedem Paket, alle einzelnen
  Bewertungen ab Paket PREMIUM; Abruf per Cronjob, „Jetzt abrufen“ und `gratreviews:sync` wie bei Google. Auf
  ProvenExpert gelöschte/unveröffentlichte Bewertungen werden entfernt, keine E-Mail-Adressen gespeichert,
  Nachnamen gekürzt
- Übersicht: „Alle Bewertungen bei ProvenExpert“ (Profil-Link der Quelle), Anzahl „… bei ProvenExpert“;
  Liste: Herkunft „ProvenExpert“, „Anonym“ bei Bewertungen ohne Namen; Hinweis zur Echtheit für ProvenExpert und
  weitere Quellen-Kombinationen
- Datenbank: neue Felder `tl_gr_source.peApiId`, `peApiKey` – `contao:migrate` ausführen

## 0.1.2 (2026-11-12)

- Fix: „Jetzt abrufen“ scheiterte an der Rechteprüfung („Could not find the subject …“) und leitete danach auf die
  Startseite statt zurück zur Liste

## 0.1.1 (2026-11-12)

- Logo, Stichwörter und Support-Links für Packagist und den Contao Manager

## 0.1.0 (2026-11-12)

- Erste Version: Quellen *Google* (Places API New, Profilsuche, Cronjob, „Jetzt abrufen“, `gratreviews:sync`) und
  *Eigene Bewertungen*; Bewertungen veröffentlichen/ausblenden/hervorheben; Inhaltselemente *Bewertungen:
  Übersicht* und *Bewertungen: Liste* (Raster/Slider) mit Hinweis zur Echtheit nach § 5b Abs. 3 UWG; Profilbilder
  lokal gespeichert
- Backend: „Bewertungen“ steht in der Gruppe *Inhalte* statt als eigene Gruppe
