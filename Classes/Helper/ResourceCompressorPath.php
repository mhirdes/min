<?php

namespace T3\Min\Helper;

/*  | This extension is made with ❤ for TYPO3 CMS and is licensed
 *  | under GNU General Public License.
 *  |
 *  | (c) 2017-2025 Armin Vieweg <armin@v.ieweg.de>
 */
/**
 * Provides the compressed assets target directory and CSS path rewriting.
 *
 * In TYPO3 v14 the core class TYPO3\CMS\Core\Resource\ResourceCompressor has
 * been removed (see Breaking-108055). This helper keeps EXT:min independent of
 * that removed class while still supporting TYPO3 v12 and v13.
 */
class ResourceCompressorPath
{
    /**
     * Same default as the (former) core ResourceCompressor.
     */
    protected string $targetDirectory = 'typo3temp/assets/compressed/';

    public function __toString(): string
    {
        return $this->targetDirectory;
    }

    /**
     * Fixes the relative paths inside of url() and @import references in CSS files
     * after they have been moved to the compressed assets target directory.
     *
     * Logic ported from the former TYPO3 \TYPO3\CMS\Core\Resource\ResourceCompressor.
     *
     * @param string $code     CSS code to process
     * @param string $filename Path of the original CSS file, relative to the public web root
     */
    public function fixRelativeUrlPathsInCssCode(string $code, string $filename): string
    {
        // Build a back-path from the target directory ("typo3temp/assets/compressed/")
        // to the public web root, then append the original file's directory.
        $depth = substr_count(rtrim($this->targetDirectory, '/'), '/') + 1;
        $backPath = str_repeat('../', $depth);
        $sourceDir = trim(\dirname($filename), '/.');
        $newDir = $backPath . ($sourceDir !== '' ? $sourceDir . '/' : '');

        if (false !== stripos($code, 'url')) {
            $regex = '/url(\\(\\s*["\']?(?!\\/)([^"\']+)["\']?\\s*\\))/iU';
            $code = $this->findAndReplaceUrlPathsByRegex($code, $regex, $newDir, '(\'|\')');
        }
        if (false !== stripos($code, '@import')) {
            $regex = '/@import\\s*(["\']?(?!\\/)([^"\']+)["\']?)/i';
            $code = $this->findAndReplaceUrlPathsByRegex($code, $regex, $newDir, '"|"');
        }

        return $code;
    }

    private function findAndReplaceUrlPathsByRegex(string $contents, string $regex, string $newDir, string $wrap): string
    {
        $matches = [];
        $replacements = [];
        $wrapParts = explode('|', $wrap);
        preg_match_all($regex, $contents, $matches);
        foreach ($matches[2] as $matchCount => $match) {
            $match = trim($match, '\'" ');
            // Skip data URIs, anchors and absolute URLs
            if (!str_starts_with($match, '#') && !str_contains($match, ':') && !preg_match('/url\\s*\\(/i', $match)) {
                $newPath = $this->resolveBackPath($newDir . $match);
                $replacements[$matches[1][$matchCount]] = $wrapParts[0] . $newPath . $wrapParts[1];
            }
        }
        if (!empty($replacements)) {
            $contents = str_replace(array_keys($replacements), array_values($replacements), $contents);
        }

        return $contents;
    }

    /**
     * Resolves "../" sections inside the path string.
     */
    private function resolveBackPath(string $pathStr): string
    {
        if (!str_contains($pathStr, '..')) {
            return $pathStr;
        }
        $parts = explode('/', $pathStr);
        $output = [];
        $c = 0;
        foreach ($parts as $part) {
            if ('..' === $part) {
                if ($c) {
                    array_pop($output);
                    --$c;
                } else {
                    $output[] = $part;
                }
            } else {
                ++$c;
                $output[] = $part;
            }
        }

        return implode('/', $output);
    }
}
