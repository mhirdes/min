<?php

/*  | This extension is made with ❤ for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2016-2025 Armin Vieweg <armin@v.ieweg.de>
 */

// Add CSS/JS Minifier (legacy hooks for the PageRenderer compressor).
// TYPO3 v14 removed frontend asset concatenation/compression (see breaking 108055),
// so these hooks are not evaluated anymore there. Asset compression for v14
// happens through the AssetCollector event listeners (AssetRendererEventListener).
if (
    class_exists(\TYPO3\CMS\Core\Information\Typo3Version::class)
    && (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() < 14
) {
    $GLOBALS['TYPO3_CONF_VARS']['FE']['cssCompressHandler'] = T3\Min\Minifier::class . '->minifyStylesheet';
    $GLOBALS['TYPO3_CONF_VARS']['FE']['jsCompressHandler'] = T3\Min\Minifier::class . '->minifyJavaScript';
}
