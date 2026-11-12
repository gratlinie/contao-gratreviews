<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_gr_source']['title_legend'] = 'Source';
$GLOBALS['TL_LANG']['tl_gr_source']['google_legend'] = 'Google Business Profile';
$GLOBALS['TL_LANG']['tl_gr_source']['sync_legend'] = 'Synchronisation';
$GLOBALS['TL_LANG']['tl_gr_source']['status_legend'] = 'Last synchronisation';
$GLOBALS['TL_LANG']['tl_gr_source']['link_legend'] = 'Link';
$GLOBALS['TL_LANG']['tl_gr_source']['publish_legend'] = 'Publishing';

$GLOBALS['TL_LANG']['tl_gr_source']['title'] = ['Title', 'Name of the source in the back end, e.g. “Google”.'];
$GLOBALS['TL_LANG']['tl_gr_source']['type'] = ['Type', '<em>Google</em>: reviews are fetched regularly from the business profile. <em>Own reviews</em>: maintained by hand.'];
$GLOBALS['TL_LANG']['tl_gr_source']['types'] = ['google' => 'Google', 'manual' => 'Own reviews'];
$GLOBALS['TL_LANG']['tl_gr_source']['googleApiKey'] = ['API key', 'Key from the Google Cloud project with the <em>Places API (New)</em> enabled. Restrict it to the server IP, not to a website (requests run on the server).'];
$GLOBALS['TL_LANG']['tl_gr_source']['googleQuery'] = ['Search profile', 'Enter business name and city – the results appear below for selection.'];
$GLOBALS['TL_LANG']['tl_gr_source']['googlePlaceId'] = ['Business profile', 'Search results. The reviews are fetched right after saving.'];
$GLOBALS['TL_LANG']['tl_gr_source']['language'] = ['Language', 'Language for name and address (e.g. “de”). Reviews always appear in their original language.'];
$GLOBALS['TL_LANG']['tl_gr_source']['syncInterval'] = ['Fetch every', 'Google returns at most the 5 most relevant reviews per request; new ones are collected, older ones are kept. Every request is billed to the Cloud project.'];
$GLOBALS['TL_LANG']['tl_gr_source']['intervals'] = [3600 => 'hour', 21600 => '6 hours', 86400 => 'day', 604800 => 'week'];
$GLOBALS['TL_LANG']['tl_gr_source']['avatarFolder'] = ['Folder for profile pictures', 'Reviewers’ profile pictures are stored here so the website serves them itself. Empty: “files/bewertungen”.'];
$GLOBALS['TL_LANG']['tl_gr_source']['placeName'] = ['Name on Google', ''];
$GLOBALS['TL_LANG']['tl_gr_source']['placeAddress'] = ['Address on Google', ''];
$GLOBALS['TL_LANG']['tl_gr_source']['ratingAverage'] = ['Rating', 'Average across all reviews of the profile.'];
$GLOBALS['TL_LANG']['tl_gr_source']['ratingCount'] = ['Count', 'Number of all reviews of the profile.'];
$GLOBALS['TL_LANG']['tl_gr_source']['lastSync'] = ['Last fetched', ''];
$GLOBALS['TL_LANG']['tl_gr_source']['lastStatus'] = ['Result', ''];
$GLOBALS['TL_LANG']['tl_gr_source']['profileUrl'] = ['Profile link', 'Optional: page showing the reviews.'];
$GLOBALS['TL_LANG']['tl_gr_source']['published'] = ['Publish', 'Show reviews of this source on the website and (for Google) fetch them regularly.'];
$GLOBALS['TL_LANG']['tl_gr_source']['sync'] = ['Fetch now', 'Fetch reviews of source ID %s now'];
$GLOBALS['TL_LANG']['tl_gr_source']['children'] = ['Reviews', 'Reviews of source ID %s'];
$GLOBALS['TL_LANG']['tl_gr_source']['noResults'] = 'Google found no business profile for this search term.';

$GLOBALS['TL_LANG']['tl_gr_source']['new'] = ['New source', 'Create a new source'];
$GLOBALS['TL_LANG']['tl_gr_source']['show'] = ['Details', 'Show the details of source ID %s'];
$GLOBALS['TL_LANG']['tl_gr_source']['edit'] = ['Edit', 'Edit source ID %s'];
$GLOBALS['TL_LANG']['tl_gr_source']['copy'] = ['Duplicate', 'Duplicate source ID %s'];
$GLOBALS['TL_LANG']['tl_gr_source']['delete'] = ['Delete', 'Delete source ID %s'];
$GLOBALS['TL_LANG']['tl_gr_source']['toggle'] = ['Publish', 'Publish/unpublish source ID %s'];
