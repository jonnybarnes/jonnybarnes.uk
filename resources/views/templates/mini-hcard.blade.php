<span class="u-category h-card">
    <a class="mini-h-card u-url p-name" href="{{ $contact->homepage }}">
        <img class="u-photo" alt="" src="{{ $contact->photo }}">
        {!! $contact->name !!}
    </a>
    @if ($contact->facebook)<a class="u-url" href="https://www.facebook.com/{{ $contact->facebook }}"></a>@endif
    @if ($contact->twitter)<a class="u-url" href="https://twitter.com/{{ $contact->twitter }}"></a>@endif
</span>
