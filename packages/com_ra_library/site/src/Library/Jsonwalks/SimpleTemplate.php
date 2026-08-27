<?php

namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks;

/*
 * copyright: Chris Vaughan
 * email: [ruby.tuesday@ramblers-webs.org.uk](mailto:ruby.tuesday@ramblers-webs.org.uk)
 */

class SimpleTemplate {

    private string $template;
    private array $values = [];
    private int $pos = 0;

    public function __construct(string|array $template) {
        if (is_array($template)) {
            $template = self::convertOldTemplate($template);
        }

        // {from ?company} → {if:company}from {company}{/if}
        $this->template = preg_replace(
                '/\{([^}]*?)\?([a-z_][a-z0-9_+]*)\}/i',
                '{if:$2}$1{$2}{/if}',
                $template
        );

        $this->validateTemplate();
    }

    private function validateTemplate(): void {
        $pos = 0;
        $len = strlen($this->template);
        $invalidTags = [];

        while ($pos < $len) {
            $ch = $this->template[$pos];

            if ($ch === '{') {
                if (preg_match('/^(\{(?:[a-z_][a-z0-9_+]*|if:[^}]+|if_not:[a-z_][a-z0-9_+]*|\/if|\/if_not)\})/i', substr($this->template, $pos), $match)) {
                    $pos += strlen($match[1]);
                    continue;
                }

                $closeBrace = strpos($this->template, '}', $pos);
                if ($closeBrace !== false) {
                    $possibleTag = substr($this->template, $pos, $closeBrace - $pos + 1);
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
                            '. Valid formats are: {fieldname}, {if:fieldname}...{/if}, {if:fieldname=value}...{/if}, {if:fieldname!=value}...{/if}, {if_not:fieldname}...{/if_not}, {text ?fieldname}'
                    );
        }
    }

    public static function convertOldTemplate(array $oldTemplate): string {
        $result = '';
        $previousField = null;

        foreach ($oldTemplate as $part) {
            if (strpos($part, '{') === false) {
                $result .= $part;
                continue;
            }

            if (preg_match('/^\{,([a-z_][a-z0-9_+]*)\}$/i', $part, $match)) {
                $field = $match[1];
                $result .= "{, ?$field}";
                $previousField = $field;
                continue;
            }
            if (preg_match('/^\{;([a-z_][a-z0-9_+]*)\}$/i', $part, $match)) {
                $field = $match[1];
                $result .= "{<br> ?$field}";
                $previousField = $field;
                continue;
            }

            if (preg_match('/^\{\[(.+?)\]([a-z_][a-z0-9_+]*)\}$/i', $part, $match)) {
                $prefix = $match[1];
                $field = $match[2];
                $result .= "{{$prefix}?{$field}}";
                $previousField = $field;
                continue;
            }

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

            if (preg_match('/^\{([a-z_][a-z0-9_+]*)\}$/i', $part, $match)) {
                $field = $match[1];
                $result .= "{{$field}}";
                $previousField = $field;
                continue;
            }

            $result .= $part;
            $previousField = null;
        }

        return $result;
    }

    private function tokenize(): array {
        $tokens = [];
        $pos = 0;
        $len = strlen($this->template);

        while ($pos < $len) {
            $ch = $this->template[$pos];

            if ($ch === '{') {
                if (preg_match('/^(\{(?:[a-z_][a-z0-9_+]*|if:[^}]+|if_not:[a-z_][a-z0-9_+]*|\/if|\/if_not)\})/i', substr($this->template, $pos), $match)) {
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

            if (preg_match('/^\{if:([^}=!]+)(?:\s*[=!]=\s*[^}]+)?\}$/i', $tag, $m)) {
                $fields[trim($m[1])] = true;
            }

            if (preg_match('/^\{if_not:([a-z_][a-z0-9_+]*)\}$/i', $tag, $m)) {
                $fields[$m[1]] = true;
            }
        }

        return array_keys($fields);
    }

    public function render(array $values): string {
        $tokens = $this->tokenize();
        $this->pos = 0;
        $this->values = $values;

        return $this->parseBlock($tokens);
    }

    private function evaluateCondition(string $condition): bool {
        $condition = trim($condition);

        if (preg_match('/^([a-z_][a-z0-9_+]*)\s*(=|!=)\s*(.*?)$/i', $condition, $m)) {
            $field = $m[1];
            $op = $m[2];
            $expected = $m[3];
            $actual = (string) ($this->values[$field] ?? '');

            return $op === '=' ? $actual === $expected : $actual !== $expected;
        }

        $field = $condition;
        return !empty($this->values[$field] ?? '');
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

            $tag = $token['value'];

            if (preg_match('/^\{([a-z_][a-z0-9_+]*)\}$/i', $tag, $matchField)) {
                $field = $matchField[1];
                $output .= $this->values[$field] ?? '';
                $this->pos++;
                continue;
            }

            if (preg_match('/^\{if:([^}]+)\}$/i', $tag, $matchIf)) {
                $this->pos++;
                $condition = $matchIf[1];
                $sub = $this->parseBlock($tokens);

                if ($this->evaluateCondition($condition)) {
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
        }

        return $output;
    }
}
