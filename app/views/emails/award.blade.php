@extends('emails.layout', ['css_class'=>'award news-letter'])

@section('content')
	<h1>{{Award::isOnce($award->name) ? 'You passed a major milestone. Celebrate!' : 'You’re a winner!'}}</h1>
	<img src="{{production_url(str_replace('svg', 'png', $award->image))}}">
	{{$award->getEmailMessage()}}
	<a href="{{production_url('login')}}"><div class="rounded-button">See The Award</div></a>
@stop