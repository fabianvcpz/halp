<div class="user-card">
	<div class="user-image">
		<a href="{{$user->getProfileURL()}}">
			<img src="{{$user->profileImage->url('s72')}}">
		</a>
	</div>
	<div class="user-details">
		<span class="user-name">
			<h4><a href="{{$user->getProfileURL()}}">{{$user->getName()}}</a></h4>
		</span>
		<hr>
		<span class="total-task">
		<h2>{{$user->totalClaimed()}}</h2>
		<h6>Completed Tasks</h6>
		</span>
	</div>
</div>