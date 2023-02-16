<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

class HelpersTest extends TestCase
{
    /** @test */
    public function normalizeUrlIsIdempotent(): void
    {
        $input = 'http://example.org:80/index.php?foo=bar&baz=1';
        $this->assertEquals(normalize_url(normalize_url($input)), normalize_url($input));
    }

    /**
     * @test
     *
     * @dataProvider urlProvider
     */
    public function normalizeUrlOnDataProvider(string $input, string $output): void
    {
        $this->assertEquals($output, normalize_url($input));
    }

    /** @test */
    public function prettyPrintJson(): void
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $json = <<<'JSON'
        {"glossary": {"title": "example glossary", "GlossDiv": {"title": "S", "GlossList": {"GlossEntry": {"ID": "SGML", "SortAs": "SGML", "GlossTerm": "Standard Generalized Markup Language", "Acronym": "SGML", "Abbrev": "ISO 8879:1986", "GlossDef": {"para": "A meta-markup language, used to create markup languages such as DocBook.", "GlossSeeAlso": ["GML", "XML"]}, "GlossSee": "markup"}}}}}
        JSON;
        // phpcs:enable Generic.Files.LineLength.TooLong

        $expected = <<<'EXPECTED'
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

    public function urlProvider(): array
    {
        return [
            ['https://example.org/', 'https://example.org'],
            ['https://example.org:443/', 'https://example.org'],
            ['http://www.foo.bar/index.php/', 'http://www.foo.bar'],
            ['https://example.org/?foo=bar&baz=true', 'https://example.org?baz=true&foo=bar'],
            [
                'http://example.org/?ref_src=twcamp^share|twsrc^ios|twgr^software.studioh.Indigenous.share-micropub',
                'http://example.org',
            ],
        ];
    }
}
