<?php

declare(strict_types=1);

namespace App\CommonMark\Generators;

use League\CommonMark\Extension\Mention\Generator\MentionGeneratorInterface;
use League\CommonMark\Extension\Mention\Mention;
use League\CommonMark\Node\Inline\AbstractInline;

class MentionGenerator implements MentionGeneratorInterface
{
    public function generateMention(Mention $mention): ?AbstractInline
    {
        return $mention;
    }
}
