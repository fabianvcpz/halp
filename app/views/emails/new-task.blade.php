@extends('emails.layout')

@section('content')
		
	<h2>Alert! Alert! {{$task->creator->getShortName()}} needs help.</h2>
	<hr>
	<h3>{{link_to($task->creator->getProfileURL(), $task->creator->firstname)}} is looking for help with:</h3>
	<h1>{{link_to($task->getClaimURL(), $task->title.' for '.$task->project->title)}}</h1>
	<p>This task will take {{$task->duration}} to complete. If you think you can help, claim the task on Halp.</p>
	<a href="{{URL::to($task->getClaimURL())}}"><div class="rounded-button">Go to Halp</div></a>

@stop