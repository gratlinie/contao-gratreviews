<?php

declare(strict_types=1);

use Contao\ArrayUtil;

ArrayUtil::arrayInsert($GLOBALS['BE_MOD'], 1, [
    'gratreviews' => [
        'gr_sources' => ['tables' => ['tl_gr_source', 'tl_gr_review']],
    ],
]);
