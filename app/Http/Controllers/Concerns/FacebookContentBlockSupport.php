<?php

namespace App\Http\Controllers\Concerns;

/**
 * Dijeljena logika za čitanje/uklanjanje/dodavanje automatskog Facebook bloka u HTML sadržaj.
 */
trait FacebookContentBlockSupport
{
    protected function extractFacebookLinkFromHtml(?string $content): ?string
    {
        $value = (string)$content;
        if ($value === '') {
            return null;
        }

        $markedBlockRegex = '/<!--\s*AUTO_FACEBOOK_LINK_START\s*-->(.*?)<!--\s*AUTO_FACEBOOK_LINK_END\s*-->/is';
        if (preg_match($markedBlockRegex, $value, $blockMatch) === 1
            && preg_match('/href=(["\'])(.*?)\1/i', $blockMatch[1], $linkMatch) === 1) {
            return html_entity_decode($linkMatch[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $legacyRegex = '/<a[^>]*href=(["\'])(.*?)\1[^>]*>\s*(?:<svg[\s\S]*?<\/svg>\s*)?Facebook\s*<\/a>/iu';
        if (preg_match($legacyRegex, $value, $legacyMatch) === 1) {
            return html_entity_decode($legacyMatch[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return null;
    }

    protected function stripFacebookBlockFromHtml(?string $content): string
    {
        $value = (string)$content;
        if ($value === '') {
            return '';
        }

        $markedBlockRegex = '/<!--\s*AUTO_FACEBOOK_LINK_START\s*-->.*?<!--\s*AUTO_FACEBOOK_LINK_END\s*-->/is';
        $value = preg_replace($markedBlockRegex, '', $value) ?? $value;

        $legacyRegex = '/<p[^>]*>\s*(?:<a[^>]*>\s*)?<svg[\s\S]*?<\/svg>\s*Facebook\s*(?:<\/a>)?\s*<\/p>/iu';
        $value = preg_replace($legacyRegex, '', $value) ?? $value;

        return trim($value);
    }

    protected function buildFacebookBlockHtml(string $facebookLink): string
    {
        $safeLink = htmlspecialchars($facebookLink, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $this->facebookBlockStartMarker()
            . '<p style="text-align:center;">'
            . '<a href="' . $safeLink . '" target="_blank" rel="noopener noreferrer">'
            . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="24px" height="24px"><path fill="#3F51B5" d="M42,37c0,2.762-2.238,5-5,5H11c-2.761,0-5-2.238-5-5V11c0-2.762,2.239-5,5-5h26c2.762,0,5,2.238,5,5V37z"></path><path fill="#FFF" d="M34.368,25H31v13h-5V25h-3v-4h3v-2.41c0.002-3.508,1.459-5.59,5.592-5.59H35v4h-2.287C31.104,17,31,17.6,31,18.723V21h4L34.368,25z"></path></svg>'
            . 'Facebook'
            . '</a>'
            . '</p>'
            . $this->facebookBlockEndMarker();
    }

    private function facebookBlockStartMarker(): string
    {
        return '<!--AUTO_FACEBOOK_LINK_START-->';
    }

    private function facebookBlockEndMarker(): string
    {
        return '<!--AUTO_FACEBOOK_LINK_END-->';
    }
}
