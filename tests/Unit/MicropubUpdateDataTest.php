<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Micropub\Data\UpdateData;
use Illuminate\Http\Request;
use JsonException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MicropubUpdateDataTest extends TestCase
{
    /**
     * @throws JsonException
     */
    #[Test]
    public function from_request_parses_json_body(): void
    {
        $request = Request::create('/micropub', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'token_data' => ['me' => 'https://example.com'],
            'url' => 'https://example.com/notes/1',
            'replace' => ['content' => ['new content']],
            'add' => ['syndication' => ['https://twitter.com']],
            'delete' => ['category' => ['old-tag']],
        ], JSON_THROW_ON_ERROR));

        $dto = UpdateData::fromRequest($request);

        $this->assertEquals(['me' => 'https://example.com'], $dto->tokenData);
        $this->assertEquals('https://example.com/notes/1', $dto->updateUrl);
        $this->assertEquals(['content' => ['new content']], $dto->updateReplace);
        $this->assertEquals(['syndication' => ['https://twitter.com']], $dto->updateAdd);
        $this->assertEquals(['category' => ['old-tag']], $dto->updateDelete);
    }

    #[Test]
    public function from_request_parses_form_encoded_body(): void
    {
        $request = Request::create('/micropub', 'POST', [
            'token_data' => ['me' => 'https://example.com'],
            'url' => 'https://example.com/notes/1',
            'replace' => ['content' => ['new content']],
            'add' => ['syndication' => ['https://twitter.com']],
            'delete' => ['category' => ['old-tag']],
        ]);

        $dto = UpdateData::fromRequest($request);

        $this->assertEquals(['me' => 'https://example.com'], $dto->tokenData);
        $this->assertEquals('https://example.com/notes/1', $dto->updateUrl);
        $this->assertEquals(['content' => ['new content']], $dto->updateReplace);
        $this->assertEquals(['syndication' => ['https://twitter.com']], $dto->updateAdd);
        $this->assertEquals(['category' => ['old-tag']], $dto->updateDelete);
    }

    /**
     * @throws JsonException
     */
    #[Test]
    public function from_request_nullable_fields_default_to_null(): void
    {
        $request = Request::create('/micropub', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'token_data' => ['me' => 'https://example.com'],
            'url' => 'https://example.com/notes/1',
        ], JSON_THROW_ON_ERROR));

        $dto = UpdateData::fromRequest($request);

        $this->assertNull($dto->updateReplace);
        $this->assertNull($dto->updateAdd);
        $this->assertNull($dto->updateDelete);
    }

    #[Test]
    public function from_array_maps_all_fields(): void
    {
        $dto = UpdateData::fromArray([
            'token_data' => ['me' => 'https://example.com'],
            'update_url' => 'https://example.com/notes/1',
            'update_replace' => ['content' => ['updated']],
            'update_add' => ['category' => ['new-tag']],
            'update_delete' => ['syndication' => ['https://old.example.com']],
        ]);

        $this->assertEquals(['me' => 'https://example.com'], $dto->tokenData);
        $this->assertEquals('https://example.com/notes/1', $dto->updateUrl);
        $this->assertEquals(['content' => ['updated']], $dto->updateReplace);
        $this->assertEquals(['category' => ['new-tag']], $dto->updateAdd);
        $this->assertEquals(['syndication' => ['https://old.example.com']], $dto->updateDelete);
    }

    #[Test]
    public function from_array_nullable_fields_default_to_null(): void
    {
        $dto = UpdateData::fromArray([
            'token_data' => ['me' => 'https://example.com'],
        ]);

        $this->assertNull($dto->updateUrl);
        $this->assertNull($dto->updateReplace);
        $this->assertNull($dto->updateAdd);
        $this->assertNull($dto->updateDelete);
    }
}
