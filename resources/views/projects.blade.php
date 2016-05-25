@extends('master')
@section('title')Jonny Barnes’ Projects @stop

@section('content')
<div id="projects">
<h2>Projects</h2>
  <h3><a href="https://shaaaaaaaaaaaaa.com">Shaaaaaaaaaaaaa.com</a></h3>
  <p>I’m collaborating on a project with Eric Mill (@konklone) to help people test their HTTPS certificates for weak signature algorithms. SHA-1 is the current standard, but is too weak. People should use a form of SHA-2.</p>
  <h3><a href="https://github.com/jonnybarnes/indieweb">IndieWeb tools</a></h3>
  <p>This library consists of various useful tools for running an IndieWeb aware site.</p>
  <h3><a href="https://github.com/jonnybarnes/webmentions-parser">Webmentions Parser</a></h3>
  <p>A tool to parse incoming webmentions to a site, including determining the author of the source webmention.</p>
</div>
@stop
