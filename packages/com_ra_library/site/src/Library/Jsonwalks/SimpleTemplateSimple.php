<?php

namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks;

/*
 * copyright: Chris Vaughan
 * email: ruby.tuesday@ramblers-webs.org.uk
 */

// usage:
//    $template = "Hello {name} {if:company}from {company}{/if}";
//
//    $temp   = new SimpleTemplate($template);
//    $fields = $temp->getFields();          // → ['name', 'company']
//    $values = $this->fetchValues($fields); // your Joomla‑style fetch logic
//    echo $temp->render($values);

class SimpleTemplate {

    private $template;
    private $values;
    private $pos;

    // Constructor takes the template string
    public function __construct(string $template) {
        // if old format
        if (is_array($template)) {
            $template = this->convertOldTemplate($template);
        }
        // {from ?company} → {if:company}from {company}{/if}
        $this->template = preg_replace(
                '/\{([^}]*?)\?([a-z_][a-z0-9_+]*)\}/i',
                '{if:$2}$1{$2}{/if}',
                $template
        );

        // Validate template for invalid tags
        $this->validateTemplate();
    }

    /**
     * Validate template for invalid field syntax
     * @throws \InvalidArgumentException if invalid tags found
     */
    private function validateTemplate(): void {
        $pos = 0;
        $len = strlen($this->template);
        $invalidTags = [];

        while ($pos < $len) {
            $ch = $this->template[$pos];

            if ($ch === '{') {
                // Check if it's a valid tag
                if (preg_match('/^(\{(?:[a-z_][a-z0-9_+]*|if:[a-z_][a-z0-9_+]*|if_not:[a-z_][a-z0-9_+]*|\/if|\/if_not)\})/i', substr($this->template, $pos), $match)) {
                    $pos += strlen($match[1]);
                    continue;
                }

                // Found an opening brace but not a valid tag - check if it's an invalid tag
                $closeBrace = strpos($this->template, '}', $pos);
                if ($closeBrace !== false) {
                    $possibleTag = substr($this->template, $pos, $closeBrace - $pos + 1);
                    // Check if it looks like a tag attempt (has content between braces)
                    if (preg_match('/^\{.+\}$/', $possibleTag)) {
                        $invalidTags[] = $possibleTag;
                    }
                    $pos = $closeBrace + 1;
                    continue;
                }
            }

            $pos++;
        }

        if (!empty($invalidTags)) {
            throw new \InvalidArgumentException(
                            'Invalid template syntax found: ' . implode(', ', $invalidTags) .
                            '. Valid formats are: {fieldname}, {if:fieldname}...{/if}, {if_not:fieldname}...{/if_not}, {text ?fieldname}'
                    );
        }
    }

    /**
     * Convert old array-based template to new SimpleTemplate string format
     * 
     * @param array $oldTemplate Array of template parts
     * @return string New template string (using shorthand syntax)
     */
    public static function convertOldTemplate(array $oldTemplate): string {
        $result = '';
        $previousField = null;

        foreach ($oldTemplate as $part) {
            // Handle literal strings (no braces)
            if (strpos($part, '{') === false) {
                $result .= $part;
                continue;
            }

            // {,name} → {, ?name}
            if (preg_match('/^\{,([a-z_][a-z0-9_+]*)\}$/i', $part, $match)) {
                $field = $match[1];
                $result .= "{, ?$field}";
                $previousField = $field;
                continue;
            }

            // {[prefix text ]field} → {prefix text ?field}
            if (preg_match('/^\{\[(.+?)\]([a-z_][a-z0-9_+]*)\}$/i', $part, $match)) {
                $prefix = $match[1];
                $field = $match[2];
                $result .= "{{$prefix}?{$field}}";
                $previousField = $field;
                continue;
            }

            // {<conditional text>field} → {conditional text?previousField}{field}
            if (preg_match('/^\{<(.+?)>([a-z_][a-z0-9_+]*)\}$/i', $part, $match)) {
                $conditionalText = $match[1];
                $field = $match[2];

                if ($previousField !== null) {
                    $result .= "{{$conditionalText}?{$previousField}}{{$field}}";
                } else {
                    $result .= "{{$field}}";
                }
                $previousField = $field;
                continue;
            }

            // Simple {name} → {name}
            if (preg_match('/^\{([a-z_][a-z0-9_+]*)\}$/i', $part, $match)) {
                $field = $match[1];
                $result .= "{{$field}}";
                $previousField = $field;
                continue;
            }

            // If nothing matched, keep as-is
            $result .= $part;
            $previousField = null;
        }

        return $result;
    }

    // Tokenize (static‑style, reused internally)
    private function tokenize(): array {
        $tokens = [];
        $pos = 0;
        $len = strlen($this->template);

        while ($pos < $len) {
            $ch = $this->template[$pos];

            if ($ch === '{') {
                if (preg_match('/^(\{(?:[a-z_][a-z0-9_+]*|if:[a-z_][a-z0-9_+]*|if_not:[a-z_][a-z0-9_+]*|\/if|\/if_not)\})/i', substr($this->template, $pos), $match)) {
                    $tokens[] = ['type' => 'tag', 'value' => $match[1]];
                    $pos += strlen($match[1]);
                    continue;
                }
            }

            $next = strpos($this->template, '{', $pos);
            if ($next === false) {
                $tokens[] = ['type' => 'text', 'value' => substr($this->template, $pos)];
                break;
            }

            $tokens[] = ['type' => 'text', 'value' => substr($this->template, $pos, $next - $pos)];
            $pos = $next;
        }

        return $tokens;
    }

    // Get list of fields used in the template
    public function getFields(): array {
        $tokens = $this->tokenize();
        $fields = [];

        foreach ($tokens as $token) {
            if ($token['type'] !== 'tag') {
                continue;
            }

            $tag = $token['value'];

            if (preg_match('/^\{([a-z_][a-z0-9_+]*)\}$/i', $tag, $m)) {
                $fields[$m[1]] = true;
            }

            if (preg_match('/^\{if:([a-z_][a-z0-9_+]*)\}$/i', $tag, $m)) {
                $fields[$m[1]] = true;
            }

            if (preg_match('/^\{if_not:([a-z_][a-z0-9_+]*)\}$/i', $tag, $m)) {
                $fields[$m[1]] = true;
            }
        }

        return array_keys($fields);
    }

    // Render the template with given values
    public function render(array $values): string {
        $tokens = $this->tokenize();
        $this->pos = 0;
        $this->values = $values;

        return $this->parseBlock($tokens);
    }

    private function parseBlock(array $tokens): string {
        $output = '';

        while ($this->pos < count($tokens)) {
            $token = $tokens[$this->pos];

            if ($token['type'] === 'text') {
                $output .= $token['value'];
                $this->pos++;
                continue;
            }

            if ($token['type'] === 'tag') {
                $tag = $token['value'];

                if (preg_match('/^\{([a-z_][a-z0-9_+]*)\}$/i', $tag, $matchField)) {
                    $field = $matchField[1];
                    $output .= $this->values[$field] ?? '';
                    $this->pos++;
                    continue;
                }

                if (preg_match('/^\{if:([a-z_][a-z0-9_+]*)\}$/i', $tag, $matchIf)) {
                    $this->pos++;
                    $fieldName = $matchIf[1];
                    $sub = $this->parseBlock($tokens);

                    if (!empty($this->values[$fieldName] ?? '')) {
                        $output .= $sub;
                    }
                    continue;
                }

                if (preg_match('/^\{if_not:([a-z_][a-z0-9_+]*)\}$/i', $tag, $matchIfNot)) {
                    $this->pos++;
                    $fieldName = $matchIfNot[1];
                    $sub = $this->parseBlock($tokens);

                    if (empty($this->values[$fieldName] ?? '')) {
                        $output .= $sub;
                    }
                    continue;
                }

                if ($tag === '{/if}' || $tag === '{/if_not}') {
                    $this->pos++;
                    break;
                }

                $output .= $tag;
                $this->pos++;
                continue;
            }
        }

        return $output;
    }
}
