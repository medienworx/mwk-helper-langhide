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

namespace medienworx;

/**
 * Class Frontend
 * @package medienworx
 */
class Frontend extends \Contao\Frontend
{

    /**
     * Try to find a root page based on language and URL
     * @return \Model
     */
    public static function getRootPageFromUrl()
    {
        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['getRootPageFromUrl']) && is_array($GLOBALS['TL_HOOKS']['getRootPageFromUrl']))
        {
            foreach ($GLOBALS['TL_HOOKS']['getRootPageFromUrl'] as $callback)
            {
                if (is_object(($objRootPage = static::importStatic($callback[0])->$callback[1]())))
                {
                    return $objRootPage;
                }
            }
        }

        $host = \Environment::get('host');

        // The language is set in the URL
        if (\Config::get('addLanguageToUrl') && !empty($_GET['language']))
        {
            $objRootPage = \PageModel::findFirstPublishedRootByHostAndLanguage($host, \Input::get('language'));

            // No matching root page found
            if ($objRootPage === null)
            {
                header('HTTP/1.1 404 Not Found');
                \System::log('No root page found (host "' . $host . '", language "'. \Input::get('language') .'")', __METHOD__, TL_ERROR);
                die_nicely('be_no_root', 'No root page found');
            }
        }

        // No language given
        else
        {
            $accept_language = \Environment::get('httpAcceptLanguage');

            // Find the matching root pages (thanks to Andreas Schempp)
            $objRootPage = \PageModel::findFirstPublishedRootByHostAndLanguage($host, $accept_language);

            // No matching root page found
            if ($objRootPage === null)
            {

                header('HTTP/1.1 404 Not Found');
                \System::log('No root page found (host "' . \Environment::get('host') . '", languages "'.implode(', ', \Environment::get('httpAcceptLanguage')).'")', __METHOD__, TL_ERROR);
                die_nicely('be_no_root', 'No root page found');
            }

            // Redirect to the language root (e.g. en/)
            if (\Config::get('addLanguageToUrl') && !\Config::get('doNotRedirectEmpty') && \Environment::get('request') == '')
            {
                /**
                 * mwk-helper-langhide start
                 */
                if ($objRootPage->language != \Config::get('langHideInUrl')) {
                    static::redirect((!\Config::get('rewriteURL') ? 'index.php/' : '') . $objRootPage->language . '/', 301);
                }
                /**
                 * mwk-helper-langhide end
                 */
            }
        }

        return $objRootPage;
    }
}