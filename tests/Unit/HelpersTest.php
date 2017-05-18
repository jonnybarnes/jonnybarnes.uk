<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class HelpersTest extends TestCase
{
    public function test_normalize_url_is_idempotent()
    {
        $input = 'http://example.org:80/index.php?foo=bar&baz=1';
        $this->assertEquals(normalize_url(normalize_url($input)), normalize_url($input));
    }

    /**
     * @dataProvider urlProvider
     */
    public function test_normalize_url($input, $output)
    {
        $this->assertEquals($output, normalize_url($input));
    }

    public function urlProvider()
    {
        return [
            ['https://example.org/', 'https://example.org'],
            ['https://example.org:443/', 'https://example.org'],
            ['http://www.foo.bar/index.php/', 'http://www.foo.bar'],
            ['https://example.org/?foo=bar&baz=true', 'https://example.org?baz=true&foo=bar'],
        ];
    }

    public function test_pretty_print_json()
    {
        $json = <<<JSON
{"glossary": {"title": "example glossary", "GlossDiv": {"title": "S", "GlossList": {"GlossEntry": {"ID": "SGML", "SortAs": "SGML", "GlossTerm": "Standard Generalized Markup Language", "Acronym": "SGML", "Abbrev": "ISO 8879:1986", "GlossDef": {"para": "A meta-markup language, used to create markup languages such as DocBook.", "GlossSeeAlso": ["GML", "XML"]}, "GlossSee": "markup"}}}}}
JSON;
        $expected = <<<EXPECTED
{
    "glossary": {
        "title": "example glossary",
        "GlossDiv": {
            "title": "S",
            "GlossList": {
                "GlossEntry": {
                    "ID": "SGML",
                    "SortAs": "SGML",
                    "GlossTerm": "Standard Generalized Markup Language",
                    "Acronym": "SGML",
                    "Abbrev": "ISO 8879:1986",
                    "GlossDef": {
                        "para": "A meta-markup language, used to create markup languages such as DocBook.",
                        "GlossSeeAlso": [
                            "GML",
                            "XML"
                        ]
                    },
                    "GlossSee": "markup"
                }
            }
        }
    }
}
EXPECTED;
        $this->assertEquals($expected, prettyPrintJson($json));
    }
}
