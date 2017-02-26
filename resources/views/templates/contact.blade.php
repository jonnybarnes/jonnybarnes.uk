<div class="h-card contact">
    <div>
        <img src="{{ $image }}" alt="" class="u-photo">
    </div>
    <div>
        <span class="p-name">{{ $contact->name }}</span> <a href="/contacts/{{ $contact->nick }}">{{ '@' . $contact->nick }}</a>
        <ul class="contact-links">
            <li><a class="u-url" href="{{ $contact->homepage }}">{{ $contact->homepageHost }}</a></li>
            @if($contact->twitter)<li><a class="u-url" href="https://twitter.com/{{ $contact->twitter }}">Twitter Profile</a></li>@endif

            @if($contact->facebook)<li><a class="u-url" href="https://www.facebook.com/{{ $contact->facebook }}">Facebook Profile</a></li>@endif

        </ul>
    </div>
</div>
