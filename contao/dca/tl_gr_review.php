<?php

declare(strict_types=1);

use Contao\DataContainer;
use Contao\DC_Table;

/*
 * Einzelne Bewertung einer Quelle. Abgerufene Bewertungen tragen die ID von Google (externalId) und
 * werden beim nächsten Abruf aktualisiert; „Veröffentlicht“ und „Hervorheben“ bleiben dabei, wie sie im
 * Backend gesetzt sind. Eigene Bewertungen legt man hier von Hand an.
 */
$GLOBALS['TL_DCA']['tl_gr_review'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_gr_source',
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid,externalId' => 'index',
                'pid,published,date' => 'index',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['date DESC'],
            'headerFields' => ['title', 'type', 'ratingAverage', 'ratingCount', 'lastSync'],
            'panelLayout' => 'filter;search,limit',
        ],
        'operations' => [
            'edit',
            'copy',
            'delete',
            'toggle',
            'feature' => [
                'href' => 'act=toggle&amp;field=featured',
                'icon' => 'featured.svg',
            ],
            'show',
        ],
    ],
    'palettes' => [
        'default' => '{review_legend},author,rating,date,text;{author_legend:hide},authorUrl,avatar;{source_legend:hide},reviewUrl,language,externalId;{publish_legend},featured,published',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid' => [
            'foreignKey' => 'tl_gr_source.title',
            'sql' => 'int(10) unsigned NOT NULL default 0',
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'tstamp' => [
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'externalId' => [
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'author' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'rating' => [
            'filter' => true,
            'inputType' => 'select',
            'options' => [5, 4, 3, 2, 1],
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => 'tinyint(1) unsigned NOT NULL default 5',
        ],
        'date' => [
            'sorting' => true,
            'flag' => DataContainer::SORT_MONTH_DESC,
            'inputType' => 'text',
            'default' => time(),
            'eval' => ['rgxp' => 'date', 'mandatory' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'text' => [
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],
        'authorUrl' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'maxlength' => 2048, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => 'text NULL',
        ],
        'avatar' => [
            'inputType' => 'fileTree',
            'eval' => ['fieldType' => 'radio', 'filesOnly' => true, 'extensions' => '%contao.image.valid_extensions%', 'tl_class' => 'w50 clr'],
            'sql' => 'binary(16) NULL',
        ],
        'reviewUrl' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'maxlength' => 2048, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => 'text NULL',
        ],
        'language' => [
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 8, 'tl_class' => 'w50'],
            'sql' => "varchar(8) NOT NULL default ''",
        ],
        'featured' => [
            'filter' => true,
            'toggle' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'published' => [
            'filter' => true,
            'toggle' => true,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => true],
        ],
    ],
];
