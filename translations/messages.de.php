<?php

return array (
  // Labels
  'label.unknown' => 'Unbekannt',
  'label.none' => 'Keine',
  'label.type' => 'Typ',
  'label.tag' => 'Tag',
  'label.actions' => 'Aktionen',
  'label.language' => 'Sprache',

  // Languages
  'language.english' => 'Englisch',
  'language.german' => 'Deutsch',

  // Chart
  'chart.by_object_type' => 'Nach Objekttyp',
  'chart.by_tag_id' => 'Nach Tag-ID',
  'chart.specimen_overview' => 'Übersicht der Belege',

  // Actions
  'action.toggle_dropdown' => 'Dropdown umschalten',
  'action.preview' => 'Vorschau',
  'action.import' => 'Importieren',
  'action.export' => 'Exportieren',
  'action.view_details' => 'Details anzeigen',
  'action.import_this_item' => 'Dieses Element importieren',
  'action.sign_in' => 'Anmelden',
  'action.logout' => 'Abmelden',
  'action.log_in' => 'Anmelden',

  // Login page
  'login.title' => 'Anmelden!',
  'login.heading' => 'Bitte melden Sie sich an',
  'login.nahima_info' => 'Verwenden Sie Ihre Nahima-Anmeldedaten. Diese werden nicht auf diesem Server gespeichert, sondern nur zum Laden von Daten aus Nahima verwendet.',
  'login.you_are_logged_in_as' => 'Sie sind angemeldet als',
  'login.username' => 'Benutzername',
  'login.password' => 'Passwort',
  'login.remember_me' => 'Angemeldet bleiben',

  // Home page
  'home.title' => 'fylr IPT Proxy Startseite',
  'home.hello' => 'Hallo',
  'home.navigation' => 'Navigation',
  'home.home' => 'Startseite',
  'home.data_management' => 'Datenverwaltung',
  'home.import_data' => 'Daten importieren',
  'home.export_data' => 'Daten exportieren',
  'home.description' => 'Dies ist eine kleine Webanwendung mit einer Darwin-Core-Datenbank, die Daten von easydb5 und/oder fylr speichert, um sie über IPT an GBIF zu exportieren.',
  'home.logged_in_message' => 'Sie sind angemeldet. Verwenden Sie das Menü, um Ihre Datenimporte und -exporte zu verwalten.',
  'home.current_data_overview' => 'Aktuelle Datenübersicht',
  'home.total_specimen' => 'Belege gesamt',
  'home.by_object_type' => 'Nach Objekttyp',
  'home.by_tag_id' => 'Nach Tag-ID',
  'home.no_data_yet' => 'Noch keine Daten.',
  'home.stats_description' => 'Diese Zahlen spiegeln gespeicherte Importe wider, die exportbereit sind und/oder bereits exportiert wurden.',
  'home.import_data_from_nahima' => 'Daten aus Nahima importieren',
  'home.export_data_from_application' => 'Daten aus dieser Anwendung exportieren',
  'home.job_history' => 'Job-Verlauf',
  'home.please_log_in' => 'Bitte melden Sie sich an, um auf die Anwendung zuzugreifen.',

  // Import page
  'import.title' => 'Daten aus EasyDB importieren',
  'import.heading' => 'Daten aus EasyDB importieren',
  'import.description' => 'Wählen Sie Kriterien aus, um eine Vorschau der Belegdaten aus EasyDB anzuzeigen und zu importieren. Verwenden Sie Filter, um Ihre Auswahl vor dem Import einzugrenzen.',
  'import.filter_options' => 'Filter- und Importoptionen',
  'import.preview_results' => 'Vorschau der Ergebnisse',
  'import.items' => 'Elemente',
  'import.page' => 'Seite',
  'import.more_available' => '• Weitere verfügbar',
  'import.confirm_import' => 'Diesen Beleg importieren?',

  // Export page
  'export.title' => 'Export-Verwaltung',
  'export.heading' => 'Export-Verwaltung',
  'export.description' => 'Exportieren Sie Entitäten aus der Datenbank dieses Proxys in verschiedene Formate. Verwenden Sie die Filter unten, um Daten für den Export auszuwählen.',
  'export.filter_options' => 'Filter- und Exportoptionen',
  'export.preview_results' => 'Vorschau der Ergebnisse',
  'export.select_tag_help' => 'Wählen Sie einen Tag aus, um alle Entitäten mit diesem Tag zu exportieren.',
  'export.limit_type_help' => 'Export auf bestimmten Objekttyp beschränken.',

  // Actions (continued)
  'action.importing' => 'Importiere...',
  'action.sync_roles' => 'Rollen synchronisieren',
  'action.change_language' => 'Sprache ändern',

  // Flash messages
  'flash.no_easydb_session' => 'Keine gültige EasyDB-Sitzung verfügbar. Bitte melden Sie sich erneut an.',
  'flash.import_job_started' => 'Import-Job gestartet. Job-ID: %jobId%',
  'flash.no_data_found' => 'Keine Daten gefunden, die Ihren Suchkriterien entsprechen. Bitte passen Sie Ihre Filter an und versuchen Sie es erneut.',
  'flash.import_successful' => 'Import des Belegs mit ID %globalObjectId% war erfolgreich',
  'flash.import_failed' => 'Import des Belegs mit ID %globalObjectId% fehlgeschlagen: %message%',
  'flash.could_not_load_tags' => 'Tags konnten nicht geladen werden: %message%',
  'flash.no_entities_found_export' => 'Keine Entitäten gefunden, die Ihren Kriterien entsprechen. Bitte passen Sie Ihre Filter an und versuchen Sie es erneut.',
  'flash.unsupported_export_format' => 'Nicht unterstütztes Exportformat: %format%',
  'flash.export_failed' => 'Export fehlgeschlagen: %message%',
  'flash.no_data_for_type' => 'Keine Daten für Typ gefunden: %type%',

  // Table columns
  'table.accession_number' => 'Zugangsnummer',
  'table.taxon_name' => 'Taxonname',
  'table.genus' => 'Gattung',
  'table.species' => 'Art',
  'table.author' => 'Autor',
  'table.collector' => 'Sammler',
  'table.collection_location' => 'Sammelort',
  'table.habitat' => 'Habitat',
  'table.global_object_id' => 'Globale Objekt-ID',
  'table.catalog_number' => 'Katalognummer',
  'table.scientific_name' => 'Wissenschaftlicher Name',
  'table.recorded_by' => 'Erfasst von',
);
