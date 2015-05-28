<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2015 - 2015 Agentur medienworx
 *
 * @package     mwk-helper-langhide
 * @author      Christian Kienzl <christian.kienzl@medienworx.eu>
 * @author      Peter Ongyert <peter.ongyert@medienworx.eu>
 * @link        http://www.medienworx.eu
 * @license     http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

// add field for the language to exclude from the url
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] = str_replace('disableAlias', 'disableAlias, langHideInUrl', $GLOBALS['TL_DCA']['tl_settings']['palettes']['default']);

$GLOBALS['TL_DCA']['tl_settings']['fields']['langHideInUrl'] =
    array
    (
        'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['langHideInUrl'],
        'inputType'               => 'checkbox',
        'eval'                    => array('tl_class'=>'w50', 'submitOnChange'=>true)
    );