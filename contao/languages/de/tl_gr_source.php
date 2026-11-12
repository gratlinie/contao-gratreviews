<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_gr_source']['title_legend'] = 'Quelle';
$GLOBALS['TL_LANG']['tl_gr_source']['google_legend'] = 'Google-Unternehmensprofil';
$GLOBALS['TL_LANG']['tl_gr_source']['sync_legend'] = 'Abruf';
$GLOBALS['TL_LANG']['tl_gr_source']['status_legend'] = 'Stand des letzten Abrufs';
$GLOBALS['TL_LANG']['tl_gr_source']['link_legend'] = 'Link';
$GLOBALS['TL_LANG']['tl_gr_source']['publish_legend'] = 'Veröffentlichung';

$GLOBALS['TL_LANG']['tl_gr_source']['title'] = ['Titel', 'Name der Quelle im Backend, z. B. „Google“.'];
$GLOBALS['TL_LANG']['tl_gr_source']['type'] = ['Art', '<em>Google</em>: Bewertungen werden regelmäßig aus dem Unternehmensprofil abgerufen. <em>Eigene Bewertungen</em>: von Hand gepflegt.'];
$GLOBALS['TL_LANG']['tl_gr_source']['types'] = ['google' => 'Google', 'manual' => 'Eigene Bewertungen'];
$GLOBALS['TL_LANG']['tl_gr_source']['googleApiKey'] = ['API-Schlüssel', 'Schlüssel aus dem Google-Cloud-Projekt mit aktivierter <em>Places API (New)</em>. Auf die IP-Adresse des Servers beschränken, nicht auf eine Website (der Abruf läuft serverseitig).'];
$GLOBALS['TL_LANG']['tl_gr_source']['googleQuery'] = ['Profil suchen', 'Firmenname und Ort eingeben, z. B. „basecamp COACHING Stuttgart“ – die Treffer erscheinen darunter zur Auswahl.'];
$GLOBALS['TL_LANG']['tl_gr_source']['googlePlaceId'] = ['Unternehmensprofil', 'Treffer der Suche. Nach dem Speichern werden die Bewertungen sofort abgerufen.'];
$GLOBALS['TL_LANG']['tl_gr_source']['language'] = ['Sprache', 'Sprache für Name und Adresse (z. B. „de“). Bewertungen erscheinen immer im Original.'];
$GLOBALS['TL_LANG']['tl_gr_source']['syncInterval'] = ['Abruf alle', 'Google liefert je Abruf höchstens die 5 relevantesten Bewertungen; neue werden gesammelt, ältere bleiben erhalten. Jeder Abruf wird über das Cloud-Projekt abgerechnet.'];
$GLOBALS['TL_LANG']['tl_gr_source']['intervals'] = [3600 => 'Stunde', 21600 => '6 Stunden', 86400 => 'Tag', 604800 => 'Woche'];
$GLOBALS['TL_LANG']['tl_gr_source']['avatarFolder'] = ['Ordner für Profilbilder', 'Hier werden die Profilbilder der Bewertenden gespeichert, damit die Website sie selbst ausliefert. Leer: „files/bewertungen“.'];
$GLOBALS['TL_LANG']['tl_gr_source']['placeName'] = ['Name bei Google', ''];
$GLOBALS['TL_LANG']['tl_gr_source']['placeAddress'] = ['Adresse bei Google', ''];
$GLOBALS['TL_LANG']['tl_gr_source']['ratingAverage'] = ['Note', 'Durchschnitt über alle Bewertungen des Profils.'];
$GLOBALS['TL_LANG']['tl_gr_source']['ratingCount'] = ['Anzahl', 'Anzahl aller Bewertungen des Profils.'];
$GLOBALS['TL_LANG']['tl_gr_source']['lastSync'] = ['Letzter Abruf', ''];
$GLOBALS['TL_LANG']['tl_gr_source']['lastStatus'] = ['Ergebnis', ''];
$GLOBALS['TL_LANG']['tl_gr_source']['profileUrl'] = ['Profil-Link', 'Optional: Seite, auf der die Bewertungen stehen.'];
$GLOBALS['TL_LANG']['tl_gr_source']['published'] = ['Veröffentlichen', 'Bewertungen dieser Quelle auf der Website anzeigen und (bei Google) regelmäßig abrufen.'];
$GLOBALS['TL_LANG']['tl_gr_source']['sync'] = ['Jetzt abrufen', 'Bewertungen der Quelle ID %s jetzt abrufen'];
$GLOBALS['TL_LANG']['tl_gr_source']['children'] = ['Bewertungen', 'Bewertungen der Quelle ID %s'];
$GLOBALS['TL_LANG']['tl_gr_source']['noResults'] = 'Google hat zu diesem Suchbegriff kein Unternehmensprofil gefunden.';

$GLOBALS['TL_LANG']['tl_gr_source']['new'] = ['Neue Quelle', 'Neue Quelle anlegen'];
$GLOBALS['TL_LANG']['tl_gr_source']['show'] = ['Details', 'Details der Quelle ID %s anzeigen'];
$GLOBALS['TL_LANG']['tl_gr_source']['edit'] = ['Bearbeiten', 'Quelle ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_gr_source']['copy'] = ['Duplizieren', 'Quelle ID %s duplizieren'];
$GLOBALS['TL_LANG']['tl_gr_source']['delete'] = ['Löschen', 'Quelle ID %s löschen'];
$GLOBALS['TL_LANG']['tl_gr_source']['toggle'] = ['Veröffentlichen', 'Quelle ID %s veröffentlichen/unveröffentlichen'];
