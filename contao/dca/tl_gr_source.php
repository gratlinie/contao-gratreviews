<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;

/*
 * Quelle von Bewertungen: ein Google-Unternehmensprofil (Abruf über die Places API) oder eigene,
 * von Hand gepflegte Bewertungen. Die Bewertungen hängen als Kinder an der Quelle (tl_gr_review).
 */
$GLOBALS['TL_DCA']['tl_gr_source'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_gr_review'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'markAsCopy' => 'title',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'type' => 'index',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['title'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['title', 'type', 'ratingAverage', 'ratingCount', 'lastSync'],
            'showColumns' => true,
        ],
        'operations' => [
            'edit',
            'children',
            'sync' => [
                'route' => 'gratreviews_sync',
                'icon' => 'sync.svg',
                'primary' => true,
                'prefetch' => false,
            ],
            'copy',
            'delete',
            'toggle',
            'show',
        ],
    ],
    'palettes' => [
        '__selector__' => ['type'],
        'default' => '{title_legend},title,type',
        'google' => '{title_legend},title,type;{google_legend},googleApiKey,googleQuery,googlePlaceId,language;{sync_legend},syncInterval,avatarFolder;{status_legend},placeName,placeAddress,ratingAverage,ratingCount,lastSync,lastStatus;{publish_legend},published',
        'manual' => '{title_legend},title,type;{link_legend},profileUrl;{publish_legend},published',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'title' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'type' => [
            'filter' => true,
            'inputType' => 'select',
            'options' => ['google', 'manual'],
            'reference' => &$GLOBALS['TL_LANG']['tl_gr_source']['types'],
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default 'google'",
        ],
        'googleApiKey' => [
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 128, 'decodeEntities' => true, 'doNotCopy' => true, 'doNotShow' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'googleQuery' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'submitOnChange' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'googlePlaceId' => [
            'inputType' => 'select',
            'eval' => ['includeBlankOption' => true, 'submitOnChange' => true, 'doNotCopy' => true, 'tl_class' => 'clr long'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'language' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 8, 'rgxp' => 'language', 'tl_class' => 'w50'],
            'sql' => "varchar(8) NOT NULL default 'de'",
        ],
        'syncInterval' => [
            'inputType' => 'select',
            'options' => [3600, 21600, 86400, 604800],
            'reference' => &$GLOBALS['TL_LANG']['tl_gr_source']['intervals'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => 'int(10) unsigned NOT NULL default 86400',
        ],
        'avatarFolder' => [
            'inputType' => 'fileTree',
            'eval' => ['fieldType' => 'radio', 'files' => false, 'tl_class' => 'w50 clr'],
            'sql' => 'binary(16) NULL',
        ],
        'placeName' => [
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'placeAddress' => [
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'ratingAverage' => [
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'decimal', 'precision' => 3, 'scale' => 2, 'default' => '0.00'],
        ],
        'ratingCount' => [
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'profileUrl' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'maxlength' => 2048, 'decodeEntities' => true, 'doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => 'text NULL',
        ],
        'reviewsUrl' => [
            'eval' => ['doNotCopy' => true],
            'sql' => 'text NULL',
        ],
        'writeReviewUrl' => [
            'eval' => ['doNotCopy' => true],
            'sql' => 'text NULL',
        ],
        'lastSync' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'readonly' => true, 'doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'lastStatus' => [
            'inputType' => 'textarea',
            'eval' => ['readonly' => true, 'doNotCopy' => true, 'tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],
        'published' => [
            'filter' => true,
            'toggle' => true,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
    ],
];
