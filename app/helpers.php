<?php

declare(strict_types=1);

/*
helpers.php
*/

// sourced from https://github.com/flattr/normalize-url/blob/master/normalize_url.php
function normalize_url(?string $url): ?string
{
    if ($url === null) {
        return null;
    }
    $newUrl = '';
    $url = parse_url($url);
    $defaultSchemes = ['http' => 80, 'https' => 443];
    if (isset($url['scheme'])) {
        $url['scheme'] = strtolower($url['scheme']);
        // Strip scheme default ports
        if (
            isset($defaultSchemes[$url['scheme']]) &&
            isset($url['port']) &&
            $defaultSchemes[$url['scheme']] == $url['port']
        ) {
            unset($url['port']);
        }
        $newUrl .= "{$url['scheme']}://";
    }
    if (isset($url['host'])) {
        $url['host'] = mb_strtolower($url['host']);
        $newUrl .= $url['host'];
    }
    if (isset($url['port'])) {
        $newUrl .= ":{$url['port']}";
    }
    // here we only want to drop a slash for the root domain
    // e.g. http://example.com/ -> http://example.com
    // but http://example.com/path/ -/-> http://example.com/path
    if (isset($url['path']) && $url['path'] == '/') {
        unset($url['path']);
    }
    if (isset($url['path'])) {
        // Case normalization
        $url['path'] = normalizer_normalize($url['path'], Normalizer::FORM_C);
        // Strip duplicate slashes
        while (preg_match("/\/\//", $url['path'])) {
            $url['path'] = preg_replace('/\/\//', '/', $url['path']);
        }

        /*
         * Decode unreserved characters, http://www.apps.ietf.org/rfc/rfc3986.html#sec-2.3
         * Heavily rewritten version of urlDecodeUnreservedChars() in Glen Scott's url-normalizer.
         */
        $u = [];
        for ($o = 65; $o <= 90; $o++) {
            $u[] = dechex($o);
        }
        for ($o = 97; $o <= 122; $o++) {
            $u[] = dechex($o);
        }
        for ($o = 48; $o <= 57; $o++) {
            $u[] = dechex($o);
        }
        $chrs = ['-', '.', '_', '~'];
        foreach ($chrs as $chr) {
            $u[] = dechex(ord($chr));
        }
        $url['path'] = preg_replace_callback(
            array_map(
                create_function('$str', 'return "/%" . strtoupper($str) . "/x";'),
                $u
            ),
            create_function('$matches', 'return chr(hexdec($matches[0]));'),
            $url['path']
        );
        // Remove directory index
        $defaultIndexes = ["/default\.aspx/" => 'default.aspx', "/default\.asp/"  => 'default.asp',
                           "/index\.html/"   => 'index.html',   "/index\.htm/"    => 'index.htm',
                           "/default\.html/" => 'default.html', "/default\.htm/"  => 'default.htm',
                           "/index\.php/"    => 'index.php',    "/index\.jsp/"    => 'index.jsp', ];
        foreach ($defaultIndexes as $index => $strip) {
            if (preg_match($index, $url['path'])) {
                $url['path'] = str_replace($strip, '', $url['path']);
            }
        }

        /**
         * Path segment normalization, http://www.apps.ietf.org/rfc/rfc3986.html#sec-5.2.4
         * Heavily rewritten version of removeDotSegments() in Glen Scott's url-normalizer.
         */
        $new_path = '';
        while (! empty($url['path'])) {
            if (preg_match('!^(\.\./|\./)!x', $url['path'])) {
                $url['path'] = preg_replace('!^(\.\./|\./)!x', '', $url['path']);
            } elseif (preg_match('!^(/\./)!x', $url['path'], $matches) || preg_match('!^(/\.)$!x', $url['path'], $matches)) {
                $url['path'] = preg_replace('!^' . $matches[1] . '!', '/', $url['path']);
            } elseif (preg_match('!^(/\.\./|/\.\.)!x', $url['path'], $matches)) {
                $url['path'] = preg_replace('!^' . preg_quote($matches[1], '!') . '!x', '/', $url['path']);
                $new_path = preg_replace('!/([^/]+)$!x', '', $new_path);
            } elseif (preg_match('!^(\.|\.\.)$!x', $url['path'])) {
                $url['path'] = preg_replace('!^(\.|\.\.)$!x', '', $url['path']);
            } else {
                if (preg_match('!(/*[^/]*)!x', $url['path'], $matches)) {
                    $first_path_segment = $matches[1];
                    $url['path'] = preg_replace('/^' . preg_quote($first_path_segment, '/') . '/', '', $url['path'], 1);
                    $new_path .= $first_path_segment;
                }
            }
        }
        $newUrl .= $new_path;
    }

    if (isset($url['fragment'])) {
        unset($url['fragment']);
    }

    // Sort GET params alphabetically, not because the RFC requires it but because it's cool!
    if (isset($url['query'])) {
        if (preg_match('/&/', $url['query'])) {
            $s = explode('&', $url['query']);
            $url['query'] = '';
            sort($s);
            foreach ($s as $z) {
                $url['query'] .= "{$z}&";
            }
            $url['query'] = preg_replace('/&\Z/', '', $url['query']);
        }
        $newUrl .= "?{$url['query']}";
    }

    return $newUrl;
}

// sourced from https://stackoverflow.com/a/9776726
function prettyPrintJson(string $json): string
{
    $result = '';
    $level = 0;
    $in_quotes = false;
    $in_escape = false;
    $ends_line_level = null;
    $json_length = strlen($json);

    for ($i = 0; $i < $json_length; $i++) {
        $char = $json[$i];
        $new_line_level = null;
        $post = '';
        if ($ends_line_level !== null) {
            $new_line_level = $ends_line_level;
            $ends_line_level = null;
        }
        if ($in_escape) {
            $in_escape = false;
        } elseif ($char === '"') {
            $in_quotes = ! $in_quotes;
        } elseif (! $in_quotes) {
            switch ($char) {
                case '}': case ']':
                    $level--;
                    $ends_line_level = null;
                    $new_line_level = $level;
                    break;

                case '{': case '[':
                    $level++;
                case ',':
                    $ends_line_level = $level;
                    break;

                case ':':
                    $post = ' ';
                    break;

                case ' ': case "\t": case "\n": case "\r":
                    $char = '';
                    $ends_line_level = $new_line_level;
                    $new_line_level = null;
                    break;
            }
        } elseif ($char === '\\') {
            $in_escape = true;
        }
        if ($new_line_level !== null) {
            $result .= "\n".str_repeat("\t", $new_line_level);
        }
        $result .= $char.$post;
    }

    return str_replace("\t", '    ', $result);
}
