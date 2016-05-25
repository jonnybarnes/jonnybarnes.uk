<div class="contact h-card">
  <div class="contact-info">
    <span class="p-name">{{ $contact->name }}</span> <a href="/contacts/{{ $contact->nick }}">{{ '@' . $contact->nick }}</a>
    <ul class="contact-links">
      <li><i class="fa fa-globe fa-fw"></i><a href="{{ $contact->homepage }}" class="u-url">{{ $contact->homepagePretty }}</a></li>
      @if($contact->twitter != null)<li><i class="fa fa-twitter fa-fw"></i><a href="https://twitter.com/{{ $contact->twitter }}">{{ $contact->twitter }}</a></li>@endif
    </ul>
   </div>
   <img src="{{ $contact->image }}" alt="" class="u-photo">
</div>