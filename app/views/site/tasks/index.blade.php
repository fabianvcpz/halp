@extends('site.layouts.default', ['use_navigation'=>true, 'use_footer'=>false])

{{-- Web site Title --}}
@section('title')
{{Config::get('config.site_name')}}
@stop

@section('head')
@stop

@section('scripts')
@stop


@section('content')
	
	<div id="test-popup" class="white-popup mfp-hide">
	</div>

	@if (Auth::check() && isset($tasks))
		@include('site.partials.create-task')
	@endif
		
	<section class="content">
	@forelse ($tasks as $task)
		@include('site.tasks.card', array('task' => $task, 'claimed'=>false))
	@empty
		<h3>No Tasks</h3>
	@endforelse
	</section>

	<div class="turtle-break">
		<div class="turtle-line"></div>
		<img src="{{asset('assets/img/happy-turtle.png')}}" width="111px" height="58px" />
		<div class="turtle-line"></div>
	</div>

	<section class="content">
	@forelse ($claimed_tasks as $task)
		@include('site.tasks.card', array('task' => $task, 'claimed'=>true))
	@empty
		<h3>No Claimed Tasks</h3>
	@endforelse
	</section>
@stop
  
