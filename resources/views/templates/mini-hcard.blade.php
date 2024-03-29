<span class="u-category h-card mini-h-card">
    <a class="u-url p-name" href="{{ $contact->homepage }}">{!! $contact->name !!}</a>
    <span class="hovercard">
        <img class="u-photo" alt="" src="{{ $contact->photo }}">
        @if ($contact->facebook)
            <a class="u-url" href="https://www.facebook.com/{{ $contact->facebook }}">
                <img class="social-icon" src="/assets/img/social-icons/facebook.svg" alt=""> Facebook
            </a>
        @endif
        @if ($contact->twitter)
            <a class="u-url" href="https://twitter.com/{{ $contact->twitter }}">
                <img class="social-icon" src="/assets/img/social-icons/twitter.svg" alt=""> {{ $contact->twitter }}
            </a>
        @endif
    </span>
</span>
