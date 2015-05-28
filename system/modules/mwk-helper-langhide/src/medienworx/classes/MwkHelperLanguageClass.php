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
 * Run in a custom namespace, so the class can be replaced
 */
namespace medienworx;

/**
 * Class MwkHelperLanguageClass
 * @package medienworx
 */
class MwkHelperLanguageClass extends \Frontend
{

    /**
     * function to override the links language
     * @param $arrRow
     * @param $strParams
     * @param $strUrl
     * @return string
     */
    public function replaceLanguageInUrl($arrRow, $strParams, $strUrl)
    {
        if (\Config::get('addLanguageToUrl') && \Config::get('langHideInUrl')) {
            $objPageFallback = \PageModel::findBy(array('fallback = 1'), array());
            $fallbackLanguage = strtolower(substr($objPageFallback->language, 0, 2));
            if (substr($strUrl, 0, 2) == $fallbackLanguage) {
                return substr($strUrl, 3);
            } else {
                return $strUrl;
            }
        } else {
            return $strUrl;
        }
    }
}