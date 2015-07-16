<?php $claimed = isset($claimed) ? $claimed : false; ?>
<div class="task {{$claimed?'claimed':''}} task-card-{{$task->id}}">
	<div class="task-details">
	
		<span class="task-name">{{$task->title}}</span>
		<hr>
		<span class="project-name">{{link_to($task->project->getURL(), $task->project->title)}}</span>
		<span class="duration">{{$task->duration}}</span>
		<span class="date">{{$task->created_at->toFormattedDateString()}}</span>
		@if (!$claimed)
			<div class="progress-button small">
				<button class="halp-claim-button" data-id="{{$task->id}}" data-mfp-src="/tasks/{{$task->id}}?json=true"><span>Claim task</span></button>
			</div>
		@endif
	</div>
	<div class="{{$claimed?'claimed':'posted'}}-by">
	@if ($claimed)
		Claimed by {{link_to($task->claimer->getProfileURL(), $task->claimer->getShortName())}}
	@else
		Posted by {{link_to($task->creator->getProfileURL(), $task->creator->getShortName())}}
	@endif
	
	@if ($task->isMine())
		<div class="edit-bar">
			<a class="halp-delete-task-button" href="#delete-task" data-id="{{$task->id}}" data-target=".task-card-{{$task->id}}"><i class="fa fa-trash-o"></i></a>
		</div>
	@endif
	</div>
</div>
