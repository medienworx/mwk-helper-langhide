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
 * Class FrontendIndex
 * @package medienworx
 */
class FrontendIndex extends \Contao\FrontendIndex
{
    /**
     * Run the controller
     */
    public function run()
    {
        global $objPage;
        $pageId = $this->getPageIdFromUrl();
        $objRootPage = null;

        /**
         * mwk-helper-langhide start
         */
        // get the fallback language
        if (empty($_GET['language']) && \Config::get('addLanguageToUrl') && \Config::get('langHideInUrl')) {
            // no language is set check the browser language
            // get the fallback language
            $objPageFallback = \PageModel::findBy(array('fallback = 1'), array());
            $fallbackLanguage = strtolower(substr($objPageFallback->language, 0, 2));

            // get the browser language
            $browserLanguages = \Environment::get('httpAcceptLanguage');
            $browserLanguage = strtolower(substr($browserLanguages[0], 0, 2));

            // check if the browser language has an root page
            $objPage = \PageModel::findBy(array('language = ?', 'type = "root"'), $browserLanguage);

            // check if the browser language and the hide language is the same and browser language exists
            if ($browserLanguage == $fallbackLanguage || empty($_GET['language']) || $objPage == NULL) {
                $_GET['language'] = $fallbackLanguage;
                list($strRequest) = explode('?', str_replace('index.php/', '', \Environment::get('request')), 2);
                $arrMatches = array();

                $strRequest = str_replace(\Config::get('urlSuffix'), '', $strRequest);
                $objPageL = \PageModel::findByAliases(array($strRequest));
                $pageId = $objPageL->id;
            }
        }
        /**
         * mwk-helper-langhide end
         */

        // Load a website root page object if there is no page ID
        if ($pageId === null)
        {
            $objRootPage = $this->getRootPageFromUrl();
            $objHandler = new $GLOBALS['TL_PTY']['root']();
            $pageId = $objHandler->generate($objRootPage->id, true);
        }
        // Throw a 404 error if the request is not a Contao request (see #2864)
        elseif ($pageId === false)
        {
            $this->User->authenticate();
            $objHandler = new $GLOBALS['TL_PTY']['error_404']();
            $objHandler->generate($pageId);
        }
        // Throw a 404 error if URL rewriting is active and the URL contains the index.php fragment
        elseif (\Config::get('rewriteURL') && strncmp(\Environment::get('request'), 'index.php/', 10) === 0)
        {
            $this->User->authenticate();
            $objHandler = new $GLOBALS['TL_PTY']['error_404']();
            $objHandler->generate($pageId);
        }

        // Get the current page object(s)
        $objPage = \PageModel::findPublishedByIdOrAlias($pageId);

        // Check the URL and language of each page if there are multiple results
        if ($objPage !== null && $objPage->count() > 1)
        {
            $objNewPage = null;
            $arrPages = array();

            // Order by domain and language
            while ($objPage->next())
            {
                $objCurrentPage = $objPage->current()->loadDetails();

                $domain = $objCurrentPage->domain ?: '*';
                $arrPages[$domain][$objCurrentPage->rootLanguage] = $objCurrentPage;

                // Also store the fallback language
                if ($objCurrentPage->rootIsFallback)
                {
                    $arrPages[$domain]['*'] = $objCurrentPage;
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
                $arrLangs = $arrPages['*'] ?: array(); // empty domain
            }

            // Use the first result (see #4872)
            if (!\Config::get('addLanguageToUrl'))
            {
                $objNewPage = current($arrLangs);
            }

            // Try to find a page matching the language parameter
            elseif (($lang = \Input::get('language')) != '' && isset($arrLangs[$lang]))
            {
                $objNewPage = $arrLangs[$lang];
            }

            // Store the page object
            if (is_object($objNewPage))
            {
                $objPage = $objNewPage;
            }
        }

        // Throw a 404 error if the page could not be found or the result is still ambiguous
        if ($objPage === null || ($objPage instanceof \Model\Collection && $objPage->count() != 1))
        {
            $this->User->authenticate();
            $objHandler = new $GLOBALS['TL_PTY']['error_404']();
            $objHandler->generate($pageId);
        }

        // Make sure $objPage is a Model
        if ($objPage instanceof \Model\Collection)
        {
            $objPage = $objPage->current();
        }

        // Load a website root page object (will redirect to the first active regular page)
        if ($objPage->type == 'root')
        {
            $objHandler = new $GLOBALS['TL_PTY']['root']();
            $objHandler->generate($objPage->id);
        }

        // Inherit the settings from the parent pages if it has not been done yet
        if (!is_bool($objPage->protected))
        {
            $objPage->loadDetails();
        }

        // Set the admin e-mail address
        if ($objPage->adminEmail != '')
        {
            list($GLOBALS['TL_ADMIN_NAME'], $GLOBALS['TL_ADMIN_EMAIL']) = \String::splitFriendlyEmail($objPage->adminEmail);
        }
        else
        {
            list($GLOBALS['TL_ADMIN_NAME'], $GLOBALS['TL_ADMIN_EMAIL']) = \String::splitFriendlyEmail(\Config::get('adminEmail'));
        }

        // Exit if the root page has not been published (see #2425)
        // Do not try to load the 404 page, it can cause an infinite loop!
        if (!BE_USER_LOGGED_IN && !$objPage->rootIsPublic)
        {
            header('HTTP/1.1 404 Not Found');
            die_nicely('be_no_page', 'Page not found');
        }



        // Check wether the language matches the root page language
        if (\Config::get('addLanguageToUrl') && \Input::get('language') != $objPage->rootLanguage)
        {
            $this->User->authenticate();
            $objHandler = new $GLOBALS['TL_PTY']['error_404']();
            $objHandler->generate($pageId);
        }

        // Check whether there are domain name restrictions
        if ($objPage->domain != '')
        {
            // Load an error 404 page object
            if ($objPage->domain != \Environment::get('host'))
            {
                $this->User->authenticate();
                $objHandler = new $GLOBALS['TL_PTY']['error_404']();
                $objHandler->generate($objPage->id, $objPage->domain, \Environment::get('host'));
            }
        }

        // Authenticate the user
        if (!$this->User->authenticate() && $objPage->protected && !BE_USER_LOGGED_IN)
        {
            $objHandler = new $GLOBALS['TL_PTY']['error_403']();
            $objHandler->generate($pageId, $objRootPage);
        }

        // Check the user groups if the page is protected
        if ($objPage->protected && !BE_USER_LOGGED_IN)
        {
            $arrGroups = $objPage->groups; // required for empty()

            if (!is_array($arrGroups) || empty($arrGroups) || !count(array_intersect($arrGroups, $this->User->groups)))
            {
                $this->log('Page "' . $pageId . '" can only be accessed by groups "' . implode(', ', (array) $objPage->groups) . '" (current user groups: ' . implode(', ', $this->User->groups) . ')', __METHOD__, TL_ERROR);

                $objHandler = new $GLOBALS['TL_PTY']['error_403']();
                $objHandler->generate($pageId, $objRootPage);
            }
        }

        // Load the page object depending on its type
        $objHandler = new $GLOBALS['TL_PTY'][$objPage->type]();

        // Backup some globals (see #7659)
        $arrHead = $GLOBALS['TL_HEAD'];
        $arrBody = $GLOBALS['TL_BODY'];
        $arrMootools = $GLOBALS['TL_MOOTOOLS'];
        $arrJquery = $GLOBALS['TL_JQUERY'];

        try
        {
            // Generate the page
            switch ($objPage->type)
            {
                case 'root':
                case 'error_404':
                    $objHandler->generate($pageId);
                    break;

                case 'error_403':
                    $objHandler->generate($pageId, $objRootPage);
                    break;

                default:
                    $objHandler->generate($objPage, true);
                    break;
            }
        }
        catch (\UnusedArgumentsException $e)
        {
            // Restore the globals (see #7659)
            $GLOBALS['TL_HEAD'] = $arrHead;
            $GLOBALS['TL_BODY'] = $arrBody;
            $GLOBALS['TL_MOOTOOLS'] = $arrMootools;
            $GLOBALS['TL_JQUERY'] = $arrJquery;

            // Render the error page (see #5570)
            $objHandler = new $GLOBALS['TL_PTY']['error_404']();
            $objHandler->generate($pageId, null, null, true);
        }

        // Stop the script (see #4565)
        exit;
    }
}