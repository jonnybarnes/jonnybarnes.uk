<?php

declare(strict_types=1);

namespace App\CommonMark\Renderers;

use App\Models\Contact;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

class MentionRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement|string
    {
        $contact = Contact::where('nick', $node->getIdentifier())->first();

        // If we have a contact, render a mini-hcard
        if ($contact) {
            // rendering a blade template to a string, so can’t be an HtmlElement
            return trim(view('templates.mini-hcard', ['contact' => $contact])->render());
        }

        // Otherwise, check the link is to the Mastodon profile
        $mentionText = $node->getIdentifier();
        $parts = explode('@', $mentionText);

        // This is not [@]handle@instance, so return a Twitter link
        if (count($parts) === 1) {
            return new HtmlElement('a', ['href' => 'https://twitter.com/' . $parts[0]], '@' . $mentionText);
        }

        // Render the Mastodon profile link
        return new HtmlElement('a', ['href' => 'https://' . $parts[1] . '/@' . $parts[0]], '@' . $mentionText);
    }
}
