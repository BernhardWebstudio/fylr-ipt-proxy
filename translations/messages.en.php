<?php

return array (
  // Labels
  'label.unknown' => 'Unknown',
  'label.none' => 'None',
  'label.type' => 'Type',
  'label.tag' => 'Tag',
  'label.actions' => 'Actions',
  'label.language' => 'Language',

  // Languages
  'language.english' => 'English',
  'language.german' => 'German',

  // Chart
  'chart.by_object_type' => 'By Object Type',
  'chart.by_tag_id' => 'By Tag ID',
  'chart.specimen_overview' => 'Specimen Overview',

  // Actions
  'action.toggle_dropdown' => 'Toggle Dropdown',
  'action.preview' => 'Preview',
  'action.import' => 'Import',
  'action.export' => 'Export',
  'action.view_details' => 'View Details',
  'action.import_this_item' => 'Import This Item',
  'action.sign_in' => 'Sign in',
  'action.logout' => 'Logout',
  'action.log_in' => 'Log in',

  // Login page
  'login.title' => 'Log in!',
  'login.heading' => 'Please sign in',
  'login.nahima_info' => 'Use your Nahima account credentials to login. They will not be stored on this server, but simply used for loading data from Nahima.',
  'login.you_are_logged_in_as' => 'You are logged in as',
  'login.username' => 'Username',
  'login.password' => 'Password',
  'login.remember_me' => 'Remember me',

  // Home page
  'home.title' => 'fylr IPT Proxy Home',
  'home.hello' => 'Hello',
  'home.navigation' => 'Navigation',
  'home.home' => 'Home',
  'home.data_management' => 'Data Management',
  'home.import_data' => 'Import Data',
  'home.export_data' => 'Export Data',
  'home.description' => 'This is a small web-app with a darwin-core database, storing data from easydb5 and/or fylr to export via IPT to GBIF.',
  'home.logged_in_message' => 'You are logged in. Use the menu to manage your data imports and exports.',
  'home.current_data_overview' => 'Current Data Overview',
  'home.total_specimen' => 'Total Specimen',
  'home.by_object_type' => 'By Object Type',
  'home.by_tag_id' => 'By Tag ID',
  'home.no_data_yet' => 'No data yet.',
  'home.stats_description' => 'These numbers reflect imports stored, ready for export and/or already exported.',
  'home.import_data_from_nahima' => 'Import Data from Nahima',
  'home.export_data_from_application' => 'Export Data from this Application',
  'home.job_history' => 'Job History',
  'home.please_log_in' => 'Please log in to access the application.',

  // Import page
  'import.title' => 'Import Data from EasyDB',
  'import.heading' => 'Import Data from EasyDB',
  'import.description' => 'Select criteria to preview and import specimen data from EasyDB. Use filters to narrow down your selection before importing.',
  'import.filter_options' => 'Filter & Import Options',
  'import.preview_results' => 'Preview Results',
  'import.items' => 'items',
  'import.page' => 'Page',
  'import.more_available' => '• More available',
  'import.confirm_import' => 'Import this specimen?',

  // Export page
  'export.title' => 'Export Management',
  'export.heading' => 'Export Management',
  'export.description' => 'Export entities from this proxy\'s database to various formats. Use the filters below to select data for export.',
  'export.filter_options' => 'Filter & Export Options',
  'export.preview_results' => 'Preview Results',
  'export.select_tag_help' => 'Select a tag to export all entities with that tag.',
  'export.limit_type_help' => 'Limit export to specific object type.',

  // Actions (continued)
  'action.importing' => 'Importing...',
  'action.sync_roles' => 'Sync Roles',
  'action.change_language' => 'Change Language',

  // Flash messages
  'flash.no_easydb_session' => 'No valid EasyDB session available. Please log in again.',
  'flash.import_job_started' => 'Import job started. Job ID: %jobId%',
  'flash.no_data_found' => 'No data found matching your search criteria. Please adjust your filters and try again.',
  'flash.import_successful' => 'Import of Specimen with ID %globalObjectId% was successful',
  'flash.import_failed' => 'Import of Specimen with ID %globalObjectId% failed: %message%',
  'flash.could_not_load_tags' => 'Could not load tags: %message%',
  'flash.no_entities_found_export' => 'No entities found matching your criteria. Please adjust your filters and try again.',
  'flash.unsupported_export_format' => 'Unsupported export format: %format%',
  'flash.export_failed' => 'Export failed: %message%',
  'flash.no_data_for_type' => 'No data found for type: %type%',

  // Table columns
  'table.accession_number' => 'Accession Number',
  'table.taxon_name' => 'Taxon Name',
  'table.genus' => 'Genus',
  'table.species' => 'Species',
  'table.author' => 'Author',
  'table.collector' => 'Collector',
  'table.collection_location' => 'Collection Location',
  'table.habitat' => 'Habitat',
  'table.global_object_id' => 'Global Object ID',
  'table.catalog_number' => 'Catalog Number',
  'table.scientific_name' => 'Scientific Name',
  'table.recorded_by' => 'Recorded By',
);
