<?php
declare(strict_types=1);

namespace Wlb\Crowdsourcing\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * ViewHelper zum Konvertieren von BBCode-Links in HTML-Links
 * 
 * Beispiel:
 * {text -> yourExtension:bbcodeToHtml()}
 * oder
 * <yourExtension:bbcodeToHtml>{text}</yourExtension:bbcodeToHtml>
 */
class BbcodeToHtmlViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize arguments
     */
    public function initializeArguments(): void
    {
        $this->registerArgument('text', 'string', 'Der Text mit BBCode-Links', false);
        $this->registerArgument('target', 'string', 'Link-Target (z.B. "_blank")', false, '');
        $this->registerArgument('class', 'string', 'CSS-Klasse für die Links', false, '');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        $text = $arguments['text'] ?? $renderChildrenClosure();
        $target = $arguments['target'] ?? '';
        $cssClass = $arguments['class'] ?? '';

        if (empty($text)) {
            return '';
        }

        return self::convertBbcodeLinks($text, $target, $cssClass);
    }

    /**
     * Konvertiert BBCode-Links zu HTML-Links
     *
     * @param string $text
     * @param string $target
     * @param string $cssClass
     * @return string
     */
    protected static function convertBbcodeLinks(string $text, string $target = '', string $cssClass = ''): string
    {
        // Pattern für verschiedene BBCode-Link-Formate:
        // [url]http://example.com[/url]
        // [url=http://example.com]Link Text[/url]
        
        $patterns = [
            // [url=http://example.com]Link Text[/url]
            '/\[url=([^\]]+)\]([^\[]*)\[\/url\]/i',
            // [url]http://example.com[/url]
            '/\[url\]([^\[]*)\[\/url\]/i'
        ];

        $replacements = [];
        
        // Zusätzliche HTML-Attribute vorbereiten
        $attributes = [];
        if (!empty($target)) {
            $attributes[] = 'target="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"';
        }
        if (!empty($cssClass)) {
            $attributes[] = 'class="' . htmlspecialchars($cssClass, ENT_QUOTES, 'UTF-8') . '"';
        }
        $attributeString = !empty($attributes) ? ' ' . implode(' ', $attributes) : '';

        // Replacement für [url=URL]Text[/url]
        $replacements[] = function ($matches) use ($attributeString) {
            $url = self::sanitizeUrl($matches[1]);
            $linkText = htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8');
            return '<a href="' . $url . '"' . $attributeString . '>' . $linkText . '</a>';
        };

        // Replacement für [url]URL[/url]
        $replacements[] = function ($matches) use ($attributeString) {
            $url = self::sanitizeUrl($matches[1]);
            $linkText = htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8');
            return '<a href="' . $url . '"' . $attributeString . '>' . $linkText . '</a>';
        };

        // BBCode-Links durch HTML-Links ersetzen
        foreach ($patterns as $index => $pattern) {
            $text = preg_replace_callback($pattern, $replacements[$index], $text);
        }

        return $text;
    }

    /**
     * Bereinigt und validiert URLs
     *
     * @param string $url
     * @return string
     */
    protected static function sanitizeUrl(string $url): string
    {
        $url = trim($url);
        
        // Nur HTTP(S), FTP und mailto URLs erlauben
        $allowedProtocols = ['http://', 'https://', 'ftp://', 'mailto:'];
        $hasValidProtocol = false;
        
        foreach ($allowedProtocols as $protocol) {
            if (strpos(strtolower($url), $protocol) === 0) {
                $hasValidProtocol = true;
                break;
            }
        }
        
        // Wenn kein Protokoll angegeben, https:// hinzufügen
        if (!$hasValidProtocol && !empty($url)) {
            // Prüfen ob es eine E-Mail-Adresse ist
            if (filter_var($url, FILTER_VALIDATE_EMAIL)) {
                $url = 'mailto:' . $url;
            } else {
                $url = 'https://' . $url;
            }
        }
        
        // URL escapen für HTML-Ausgabe
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
}