/**
 * WP Flyout Code Block Component JavaScript
 *
 * Handles copy to clipboard functionality and basic syntax highlighting.
 *
 * @package     ArrayPress\WPFlyout
 * @version     1.0.0
 */
(function ($) {
    'use strict';

    /**
     * Simple syntax highlighter
     */
    class CodeHighlighter {
        static highlight(code, language) {
            switch (language.toLowerCase()) {
                case 'php':
                    return this.highlightPHP(code);
                case 'javascript':
                case 'js':
                    return this.highlightJS(code);
                case 'css':
                    return this.highlightCSS(code);
                case 'sql':
                    return this.highlightSQL(code);
                default:
                    return this.escapeHtml(code);
            }
        }

        static escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        static highlightPHP(code) {
            let highlighted = this.escapeHtml(code);

            // Comments (must come before strings to avoid conflicts)
            highlighted = highlighted.replace(/(\/\/[^\n]*)/g, '<span class="code-comment">$1</span>');
            highlighted = highlighted.replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="code-comment">$1</span>');
            highlighted = highlighted.replace(/(#[^\n]*)/g, '<span class="code-comment">$1</span>');

            // Strings (must come before functions/keywords)
            highlighted = highlighted.replace(/("(?:[^"\\]|\\.)*")/g, '<span class="code-string">$1</span>');
            highlighted = highlighted.replace(/('(?:[^'\\]|\\.)*')/g, '<span class="code-string">$1</span>');

            // PHP variables
            highlighted = highlighted.replace(/(\$\w+)/g, '<span class="code-variable">$1</span>');

            // Keywords
            highlighted = highlighted.replace(/\b(function|class|interface|trait|extends|implements|public|private|protected|static|const|final|abstract|if|else|elseif|foreach|for|while|do|switch|case|break|continue|return|new|echo|print|require|include|require_once|include_once|namespace|use|try|catch|throw|finally)\b/g, '<span class="code-keyword">$1</span>');

            // Built-in functions
            highlighted = highlighted.replace(/\b(array|isset|empty|count|sizeof|in_array|is_array|is_string|is_int|is_bool|is_null|strlen|substr|str_replace|preg_match|preg_replace|explode|implode|trim|ltrim|rtrim|strtolower|strtoupper|ucfirst|ucwords)\b(?=\()/g, '<span class="code-builtin">$1</span>');

            return highlighted;
        }

        static highlightJS(code) {
            let highlighted = this.escapeHtml(code);

            // Comments
            highlighted = highlighted.replace(/(\/\/[^\n]*)/g, '<span class="code-comment">$1</span>');
            highlighted = highlighted.replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="code-comment">$1</span>');

            // Strings (including template literals)
            highlighted = highlighted.replace(/("(?:[^"\\]|\\.)*")/g, '<span class="code-string">$1</span>');
            highlighted = highlighted.replace(/('(?:[^'\\]|\\.)*')/g, '<span class="code-string">$1</span>');
            highlighted = highlighted.replace(/(`(?:[^`\\]|\\.)*`)/g, '<span class="code-string">$1</span>');

            // Keywords
            highlighted = highlighted.replace(/\b(const|let|var|function|class|extends|if|else|for|while|do|switch|case|break|continue|return|new|async|await|import|export|default|from|try|catch|throw|finally|typeof|instanceof|in|of|this|super)\b/g, '<span class="code-keyword">$1</span>');

            // Built-in objects and functions
            highlighted = highlighted.replace(/\b(console|document|window|Array|Object|String|Number|Boolean|Math|Date|JSON|Promise)\b/g, '<span class="code-builtin">$1</span>');

            // Numbers
            highlighted = highlighted.replace(/\b(\d+)\b/g, '<span class="code-number">$1</span>');

            return highlighted;
        }

        static highlightCSS(code) {
            let highlighted = this.escapeHtml(code);

            // Comments
            highlighted = highlighted.replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="code-comment">$1</span>');

            // Strings
            highlighted = highlighted.replace(/("(?:[^"\\]|\\.)*")/g, '<span class="code-string">$1</span>');
            highlighted = highlighted.replace(/('(?:[^'\\]|\\.)*')/g, '<span class="code-string">$1</span>');

            // Selectors (basic - before the opening brace)
            highlighted = highlighted.replace(/([^{}]+)(?=\s*{)/g, function (match) {
                // Don't re-highlight already highlighted content
                if (match.includes('<span')) return match;
                return '<span class="code-selector">' + match + '</span>';
            });

            // Properties
            highlighted = highlighted.replace(/([a-z-]+)(?=\s*:)/g, '<span class="code-property">$1</span>');

            // Important
            highlighted = highlighted.replace(/(!important)/g, '<span class="code-keyword">$1</span>');

            // Numbers with units
            highlighted = highlighted.replace(/(\d+(?:px|em|rem|%|vh|vw|deg|s|ms)?)/g, '<span class="code-number">$1</span>');

            return highlighted;
        }

        static highlightSQL(code) {
            let highlighted = this.escapeHtml(code);

            // Comments
            highlighted = highlighted.replace(/(--[^\n]*)/g, '<span class="code-comment">$1</span>');
            highlighted = highlighted.replace(/(\/\*[\s\S]*?\*\/)/g, '<span class="code-comment">$1</span>');

            // Strings
            highlighted = highlighted.replace(/("(?:[^"\\]|\\.)*")/g, '<span class="code-string">$1</span>');
            highlighted = highlighted.replace(/('(?:[^'\\]|\\.)*')/g, '<span class="code-string">$1</span>');

            // Keywords (case-insensitive)
            highlighted = highlighted.replace(/\b(SELECT|FROM|WHERE|AND|OR|NOT|IN|LIKE|BETWEEN|JOIN|INNER|LEFT|RIGHT|OUTER|ON|AS|GROUP BY|ORDER BY|HAVING|LIMIT|OFFSET|INSERT|INTO|VALUES|UPDATE|SET|DELETE|CREATE|TABLE|ALTER|DROP|INDEX|PRIMARY|KEY|FOREIGN|REFERENCES|UNIQUE|DEFAULT|NULL|AUTO_INCREMENT)\b/gi, '<span class="code-keyword">$1</span>');

            // Functions
            highlighted = highlighted.replace(/\b(COUNT|SUM|AVG|MIN|MAX|ROUND|CONCAT|SUBSTRING|LENGTH|NOW|DATE|YEAR|MONTH)\b/gi, '<span class="code-builtin">$1</span>');

            return highlighted;
        }
    }

    /**
     * Initialize syntax highlighting on page load and flyout open
     */
    function initializeHighlighting() {
        $('.code-block-code[data-language]').each(function () {
            const $code = $(this);
            const language = $code.data('language');

            // Skip if already highlighted or highlighting is disabled
            if ($code.data('highlighted') || !$code.closest('[data-highlight="true"]').length) {
                return;
            }

            const originalCode = $code.text();
            const highlightedCode = CodeHighlighter.highlight(originalCode, language);
            $code.html(highlightedCode);
            $code.data('highlighted', true);
        });
    }

    // Initialize on document ready
    $(document).ready(function () {
        initializeHighlighting();
    });

    // Initialize when flyouts open
    $(document).on('wpflyout:opened', function (e, data) {
        $(data.element).find('.code-block-code[data-language]').each(function () {
            const $code = $(this);
            if (!$code.data('highlighted') && $code.closest('[data-highlight="true"]').length) {
                const language = $code.data('language');
                const originalCode = $code.text();
                const highlightedCode = CodeHighlighter.highlight(originalCode, language);
                $code.html(highlightedCode);
                $code.data('highlighted', true);
            }
        });
    });

    // Copy to clipboard functionality
    $(document).on('click', '.code-block-copy', function (e) {
        e.preventDefault();

        const $button = $(this);
        const $code = $button.siblings('.code-block-pre').find('.code-block-code');

        // Get the original text content (without HTML tags)
        const text = $code[0].textContent || $code[0].innerText;

        // Copy to clipboard
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                // Show success
                $button.addClass('copied');
                $button.find('.copy-text').text('Copied!');

                // Reset after 2 seconds
                setTimeout(() => {
                    $button.removeClass('copied');
                    $button.find('.copy-text').text('Copy');
                }, 2000);
            });
        } else {
            // Fallback method
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();

            // Show success
            $button.addClass('copied');
            $button.find('.copy-text').text('Copied!');

            setTimeout(() => {
                $button.removeClass('copied');
                $button.find('.copy-text').text('Copy');
            }, 2000);
        }
    });

})(jQuery);