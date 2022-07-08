<?php

declare(strict_types=1);

namespace App\CommonMark\Renderers;

use App\Models\Contact;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

class ContactMentionRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        $contact = Contact::where('nick', $node->getIdentifier())->first();

        if ($contact === null) {
            return '<a href="https://twitter.com/' . $node->getIdentifier() . '">@' . $node->getIdentifier() . '</a>';
        }

        return trim(view('templates.mini-hcard', ['contact' => $contact])->render());
    }
}
