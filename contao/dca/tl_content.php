<?php

declare(strict_types=1);

$tail = '{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['palettes']['gr_summary'] = '{type_legend},type,headline;{gr_source_legend},grSources;{gr_summary_legend},grLinks;{gr_notice_legend},grShowNotice,grNotice;'.$tail;
$GLOBALS['TL_DCA']['tl_content']['palettes']['gr_list'] = '{type_legend},type,headline;{gr_source_legend},grSources;{gr_list_legend},grLimit,grOrder,grMinRating,grOnlyText,grLayout;{gr_notice_legend},grShowNotice,grNotice;'.$tail;

$GLOBALS['TL_DCA']['tl_content']['fields'] += [
    'grSources' => [
        'inputType' => 'checkbox',
        'foreignKey' => 'tl_gr_source.title',
        'eval' => ['mandatory' => true, 'multiple' => true, 'tl_class' => 'clr'],
        'sql' => 'blob NULL',
        'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
    ],
    'grLinks' => [
        'inputType' => 'checkbox',
        'options' => ['reviews', 'write'],
        'reference' => &$GLOBALS['TL_LANG']['tl_content']['grLinkOptions'],
        'eval' => ['multiple' => true, 'tl_class' => 'clr'],
        'sql' => 'blob NULL',
    ],
    'grLimit' => [
        'inputType' => 'text',
        'eval' => ['rgxp' => 'natural', 'maxlength' => 3, 'tl_class' => 'w50'],
        'sql' => 'smallint(5) unsigned NOT NULL default 6',
    ],
    'grOrder' => [
        'inputType' => 'select',
        'options' => ['featured', 'date', 'rating', 'random'],
        'reference' => &$GLOBALS['TL_LANG']['tl_content']['grOrderOptions'],
        'eval' => ['tl_class' => 'w50'],
        'sql' => "varchar(16) NOT NULL default 'featured'",
    ],
    'grMinRating' => [
        'inputType' => 'select',
        'options' => [1, 2, 3, 4, 5],
        'reference' => &$GLOBALS['TL_LANG']['tl_content']['grMinRatingOptions'],
        'eval' => ['tl_class' => 'w50'],
        'sql' => 'tinyint(1) unsigned NOT NULL default 4',
    ],
    'grOnlyText' => [
        'inputType' => 'checkbox',
        'eval' => ['tl_class' => 'w50 m12'],
        'sql' => ['type' => 'boolean', 'default' => true],
    ],
    'grLayout' => [
        'inputType' => 'select',
        'options' => ['grid', 'slider'],
        'reference' => &$GLOBALS['TL_LANG']['tl_content']['grLayoutOptions'],
        'eval' => ['tl_class' => 'w50 clr'],
        'sql' => "varchar(8) NOT NULL default 'grid'",
    ],
    'grShowNotice' => [
        'inputType' => 'checkbox',
        'eval' => ['tl_class' => 'clr'],
        'sql' => ['type' => 'boolean', 'default' => true],
    ],
    'grNotice' => [
        'inputType' => 'textarea',
        'eval' => ['rows' => 3, 'tl_class' => 'clr'],
        'sql' => 'text NULL',
    ],
];
