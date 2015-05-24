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

/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
    'medienworx',
));

/**
 * Register the classes
 */
ClassLoader::addClasses(
    array(
        'medienworx\MwkHelperLanguageClass' 		=> 'system/modules/mwk-helper-langhide/src/medienworx/classes/MwkHelperLanguageClass.php'
    )
);

switch(VERSION) {
    case '3.2':
        ClassLoader::addClasses(
            array(
                'medienworx\Frontend' 			    => 'system/modules/mwk-helper-langhide/src/medienworx/classes/3.2/Frontend.php'
            )
        );
        break;
    case '3.3':
        ClassLoader::addClasses(
            array(
                'medienworx\Frontend' 			    => 'system/modules/mwk-helper-langhide/src/medienworx/classes/3.3/Frontend.php'
            )
        );
        break;
    case '3.4':
        ClassLoader::addClasses(
            array(
                'medienworx\FrontendIndex' 			=> 'system/modules/mwk-helper-langhide/src/medienworx/classes/3.4/FrontendIndex.php',
                'medienworx\Frontend' 			    => 'system/modules/mwk-helper-langhide/src/medienworx/classes/3.4/Frontend.php'
            )
        );
        break;
}