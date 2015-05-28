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

        /**
         * mwk-helper-langhide start
         */
        if (empty($_GET['language']) && \Config::get('addLanguageToUrl') && \Config::get('langHideInUrl')) {
            // get the fallback language
            $objPageFallback = \PageModel::findBy(array('fallback = 1'), array());
            $fallbackLanguage = strtolower(substr($objPageFallback->language, 0, 2));

            // get the browser language
            $browserLanguages = \Environment::get('httpAcceptLanguage');
            $browserLanguage = strtolower(substr($browserLanguages[0], 0, 2));

            // check if the browser language has an root page
            $objPage = \PageModel::findBy(array('language = ?', 'type = "root"'), $browserLanguage);

            // browser language is fallback language OR no page s found for the browser language
            if ($browserLanguage == $fallbackLanguage || empty($_GET['language']) || $objPage == NULL) {
                $_GET['language'] = $fallbackLanguage;
            }
        }
        /**
         * mwk-helper-langhide end
         */

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
                if($objRootPage->language != $fallbackLanguage) {

                    static::redirect((!\Config::get('rewriteURL') ? 'index.php/' : '') . $objRootPage->language . '/', 301);
                }
                /**
                 * mwk-helper-langhide end
                 */
            }
        }

        return $objRootPage;
    }

    /**
     * Split the current request into fragments, strip the URL suffix, recreate the $_GET array and return the page ID
     * @return mixed
     */
    public static function getPageIdFromUrl()
    {
        if (\Config::get('disableAlias'))
        {
            return is_numeric(\Input::get('id')) ? \Input::get('id') : null;
        }

        if (\Environment::get('request') == '')
        {
            return null;
        }

        // Get the request string without the index.php fragment
        if (\Environment::get('request') == 'index.php')
        {
            $strRequest = '';
        }
        else
        {
            list($strRequest) = explode('?', str_replace('index.php/', '', \Environment::get('request')), 2);
        }

        // URL decode here (see #6232)
        $strRequest = rawurldecode($strRequest);

        // The request string must not contain "auto_item" (see #4012)
        if (strpos($strRequest, '/auto_item/') !== false)
        {
            return false;
        }

        // Remove the URL suffix if not just a language root (e.g. en/) is requested
        if ($strRequest != '' && (!\Config::get('addLanguageToUrl') || !preg_match('@^[a-z]{2}(\-[A-Z]{2})?/$@', $strRequest)))
        {
            $intSuffixLength = strlen(\Config::get('urlSuffix'));

            // Return false if the URL suffix does not match (see #2864)
            if ($intSuffixLength > 0)
            {
                if (substr($strRequest, -$intSuffixLength) != \Config::get('urlSuffix'))
                {
                    return false;
                }

                $strRequest = substr($strRequest, 0, -$intSuffixLength);
            }
        }

        // Extract the language
        if (\Config::get('addLanguageToUrl'))
        {
            $arrMatches = array();

            // Use the matches instead of substr() (thanks to Mario Müller)
            if (preg_match('@^([a-z]{2}(\-[A-Z]{2})?)/(.*)$@', $strRequest, $arrMatches))
            {
                \Input::setGet('language', $arrMatches[1]);

                // Trigger the root page if only the language was given
                if ($arrMatches[3] == '')
                {
                    return null;
                }

                $strRequest = $arrMatches[3];
            }
            else
            {
                /**
                 * mwk-helper-langhide start
                 */
                // get the fallback language
                $objPageFallback = \PageModel::findBy(array('fallback = 1'), array());
                $fallbackLanguage = strtolower(substr($objPageFallback->language, 0, 2));

                // get the browser language
                $browserLanguages = \Environment::get('httpAcceptLanguage');
                $browserLanguage = strtolower(substr($browserLanguages[0], 0, 2));

                // check if the browser language has an root page
                $objPage = \PageModel::findBy(array('language = ?', 'type = "root"'), $browserLanguage);

                // browser language is fallback language OR no page s found for the browser language
                if ($browserLanguage == $fallbackLanguage || empty($_GET['language']) || $objPage == NULL) {
                    \Input::setGet('language', $fallbackLanguage);
                } else {
                    return false;
                }
                /**
                 * mwk-helper-langhide end
                 */
            }
        }

        $arrFragments = null;

        // Use folder-style URLs
        if (\Config::get('folderUrl') && strpos($strRequest, '/') !== false)
        {
            $strAlias = $strRequest;
            $arrOptions = array($strAlias);

            // Compile all possible aliases by applying dirname() to the request (e.g. news/archive/item, news/archive, news)
            while ($strAlias != '/' && strpos($strAlias, '/') !== false)
            {
                $strAlias = dirname($strAlias);
                $arrOptions[] = $strAlias;
            }

            // Check if there are pages with a matching alias
            $objPages = \PageModel::findByAliases($arrOptions);

            if ($objPages !== null)
            {
                $arrPages = array();

                // Order by domain and language
                while ($objPages->next())
                {
                    $objPage = $objPages->current()->loadDetails();

                    $domain = $objPage->domain ?: '*';
                    $arrPages[$domain][$objPage->rootLanguage][] = $objPage;

                    // Also store the fallback language
                    if ($objPage->rootIsFallback)
                    {
                        $arrPages[$domain]['*'][] = $objPage;
                    }
                }

                $strHost = \Environment::get('host');

                // Look for a root page whose domain name matches the host name
                if (isset($arrPages[$strHost]))
                {
                    $arrLangs = $arrPages[$strHost];
                }
                else
                {
                    $arrLangs = $arrPages['*']; // Empty domain
                }

                $arrAliases = array();

                // Use the first result (see #4872)
                if (!\Config::get('addLanguageToUrl'))
                {
                    $arrAliases = current($arrLangs);
                }
                // Try to find a page matching the language parameter
                elseif (($lang = \Input::get('language')) != '' && isset($arrLangs[$lang]))
                {
                    $arrAliases = $arrLangs[$lang];
                }

                // Return if there are no matches
                if (empty($arrAliases))
                {
                    return false;
                }

                $objPage = $arrAliases[0];

                // The request consists of the alias only
                if ($strRequest == $objPage->alias)
                {
                    $arrFragments = array($strRequest);
                }
                // Remove the alias from the request string, explode it and then re-insert the alias at the beginning
                else
                {
                    $arrFragments = explode('/', substr($strRequest, strlen($objPage->alias) + 1));
                    array_unshift($arrFragments, $objPage->alias);
                }
            }
        }

        // If folderUrl is deactivated or did not find a matching page
        if ($arrFragments === null)
        {
            if ($strRequest == '/')
            {
                return false;
            }
            else
            {
                $arrFragments = explode('/', $strRequest);
            }
        }

        // Add the second fragment as auto_item if the number of fragments is even
        if (\Config::get('useAutoItem') && count($arrFragments) % 2 == 0)
        {
            array_insert($arrFragments, 1, array('auto_item'));
        }

        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['getPageIdFromUrl']) && is_array($GLOBALS['TL_HOOKS']['getPageIdFromUrl']))
        {
            foreach ($GLOBALS['TL_HOOKS']['getPageIdFromUrl'] as $callback)
            {
                $arrFragments = static::importStatic($callback[0])->$callback[1]($arrFragments);
            }
        }

        // Return if the alias is empty (see #4702 and #4972)
        if ($arrFragments[0] == '' && count($arrFragments) > 1)
        {
            return false;
        }

        // Add the fragments to the $_GET array
        for ($i=1, $c=count($arrFragments); $i<$c; $i+=2)
        {
            // Skip key value pairs if the key is empty (see #4702)
            if ($arrFragments[$i] == '')
            {
                continue;
            }

            // Return false if there is a duplicate parameter (duplicate content) (see #4277)
            if (isset($_GET[$arrFragments[$i]]))
            {
                return false;
            }

            // Return false if the request contains an auto_item keyword (duplicate content) (see #4012)
            if (\Config::get('useAutoItem') && in_array($arrFragments[$i], $GLOBALS['TL_AUTO_ITEM']))
            {
                return false;
            }

            \Input::setGet($arrFragments[$i], (string) $arrFragments[$i+1], true);
        }
        return $arrFragments[0] ?: null;
    }
}