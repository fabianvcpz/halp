<?php namespace Task;

use BaseModel;
use SoftDeletingTrait;
use User;
use Validator;
use Carbon;
use DB;
use URL;
use Auth;
use Config;

class Task extends BaseModel {
	
	use SoftDeletingTrait;

	protected $fillable  = ['title', 'project_id', 'creator_id', 'claimed_id', 'duration', 'details', 'task_date', 'does_not_expire', 'created_at'];
	protected $dates     = ['deleted_at'];
	public static $rules = ['title'=>'required', 'duration'=>'required', 'project'=>'required'];
	

    // ------------------------------------------------------------------------
    public function toArray() 
    {
    	$array = parent::toArray();
     	return $array;
    }

    // ------------------------------------------------------------------------
    public function scopeClaimed($query)
    {
    	return $query->whereNotNull('claimed_id')->orderBy('created_at', 'DESC');
    }

    // ------------------------------------------------------------------------
    public function scopeForWeek($query, $week_of=null)
    {
    	if($week_of == null) $week_of = Carbon\Carbon::now();
        $date_str = $week_of->toDateString();
    	return $query->whereRaw(DB::raw("YEARWEEK(tasks.`created_at`) = YEARWEEK('{$date_str}')"));
    }
    // ------------------------------------------------------------------------
    public function scopeUnClaimed($query)
    {
    	return $query->whereNull('claimed_id')->orderBy('created_at', 'DESC');
    }

    // ------------------------------------------------------------------------
    public static function isExpiredSQL($prefix='')
    {	
    	if($prefix !='')$prefix = DB::getTablePrefix().$prefix.'.';
    	$n_days = Config::get('config.task_expiration_days');	
    	return DB::raw("
			(CASE WHEN {$prefix}`task_date` IS NULL
			THEN 
				DATE_ADD(DATE_FORMAT({$prefix}`created_at`, '%Y-%m-%d'), INTERVAL $n_days DAY)
			ELSE 
				{$prefix}`task_date` > CURRENT_DATE
			END)
    		AND
    		{$prefix}`does_not_expire` = 0
    		AS is_expired");	
    }

    // ------------------------------------------------------------------------
    public static function expirationDateSQL()
    {
    	$n_days = Config::get('config.task_expiration_days');	
    	return DB::raw("
				(CASE WHEN `task_date` IS NULL
				THEN 
					DATE_ADD(DATE_FORMAT(`created_at`, '%Y-%m-%d'), INTERVAL $n_days DAY) 
				ELSE 
					`task_date`
				END)
    		AS expiration_date");	
    }

    // ------------------------------------------------------------------------
    public function scopeActive($query, $prefix='')
    {	    	
    	/*
    	-- Task is Active --
		SELECT * FROM `tasks` 
		WHERE `tasks`.`deleted_at` IS NULL 
		AND 
			CURDATE() <= 
			(CASE WHEN `task_date` IS NULL 
				THEN DATE_ADD(DATE_FORMAT(`created_at`, '%Y-%m-%d'), INTERVAL 10 DAY) 
				ELSE `task_date` 
			END)
		OR
		tasks.does_not_expire = 1
		*/
    	if($prefix !='')$prefix = DB::getTablePrefix().$prefix.'.';
    	$n_days = Config::get('config.task_expiration_days');	

		return $query->whereRaw("
			(CURDATE() <=
			(CASE WHEN {$prefix}`task_date` IS NULL
				THEN 
					DATE_ADD(DATE_FORMAT({$prefix}`created_at`, '%Y-%m-%d'), INTERVAL $n_days DAY)
				ELSE 
					{$prefix}`task_date`
			END)
			OR
    		{$prefix}`does_not_expire` = 1)
		");
    }

    // ------------------------------------------------------------------------
    public function scopeExpired($query, $prefix='')
    {	
    	/*
    	-- Task is expired --
		SELECT * FROM `tasks` 
		WHERE `tasks`.`deleted_at` IS NULL 
		AND 
			CURDATE() > 
			(CASE WHEN `task_date` IS NULL 
				THEN DATE_ADD(DATE_FORMAT(`created_at`, '%Y-%m-%d'), INTERVAL 10 DAY) 
			ELSE `task_date` 
		END)
		AND
		tasks.does_not_expire = 0
		*/
    	if($prefix !='')$prefix = DB::getTablePrefix().$prefix.'.';
    	$n_days = Config::get('config.task_expiration_days');	
    	return $query->whereRaw("
    		(CURDATE() >
			(CASE WHEN {$prefix}`task_date` IS NULL
				THEN 
					DATE_ADD(DATE_FORMAT({$prefix}`created_at`, '%Y-%m-%d'), INTERVAL $n_days DAY)
				ELSE 
					{$prefix}`task_date`
			END)
    		AND
    		{$prefix}`does_not_expire` = 0)
    	");
    }

    // ------------------------------------------------------------------------
    public function scopeMostClaimed($query)
    {
    	return $query->whereNotNull('claimed_id')
                     ->groupBy('claimed_id')
                     ->select(array('*', DB::raw('count(*) as claimed_count')))
                     ->orderBy('claimed_count', 'DESC');
    }

	// ------------------------------------------------------------------------
	public function save(array $options = array()) 
	{
  		parent::save();
	}

	// ------------------------------------------------------------------------
	public function getURL($relative=true) 
	{
		return URL::to('tasks/'.$this->id); 
	}

	// ------------------------------------------------------------------------
	public function getClaimURL($relative=true) 
	{
		return URL::to('/?claim_task='.$this->id); 
	}

	// ------------------------------------------------------------------------
	public function isMine()
	{
		return Auth::check() && Auth::id() == $this->creator_id;
	}

	// ------------------------------------------------------------------------
	public function getExpirationDate()
	{
		// create($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $tz = null)
		$date = $this->created_at->startOfDay();
		return $this->task_date == NULL ? $date->addDays(Config::get('config.task_expiration_days')) : new Carbon\Carbon($this->task_date);;
	}

	// ------------------------------------------------------------------------
	public function getExpirationDateForHumans()
	{
		$d = $this->getExpirationDate();
		return $d->isToday() ? "Due Today!" : $d->diffForHumans();
	}

	// ------------------------------------------------------------------------
	public function doesNotExpire()
	{
		return $this->does_not_expire == 1;
	}

	// ------------------------------------------------------------------------
	public function isExpired()
	{
		if($this->does_not_expire) return false;
		return $this->getExpirationDate()->isPast();
	}

	// ------------------------------------------------------------------------
	public function isDueSoon()
	{
		return $this->getExpirationInDays() <= Config::get('config.task_expiration_soon_days') && $this->does_not_expire == false && $this->isClaimed == false;
	}

	// ------------------------------------------------------------------------
	public function getExpirationInDays()
	{
		$expiration_date = $this->getExpirationDate();
		$today = Carbon\Carbon::now()->startOfDay();
		return $expiration_date->diffInDays($today);
	}

	// ------------------------------------------------------------------------
	public function getIsExpiredAttribute()
	{
		if($this->getExpirationDate()->isToday()) {
			return false;
		}
		return $this->getExpirationDate()->isPast();
	}

	// ------------------------------------------------------------------------
	public function isExpiredAndNotClaimed()
	{
		return $this->isExpired && $this->isClaimed == false;
	}

	// ------------------------------------------------------------------------
	public function creator()
	{
		return $this->belongsTo('User');
	}

		// ------------------------------------------------------------------------
	public function claimer()
	{
		return $this->belongsTo('User', 'claimed_id');
	}

	// ------------------------------------------------------------------------
	public function notifications()
	{
		return  $this->hasMany('Notification', 'task_id');
	}

	// ------------------------------------------------------------------------
	public function getIsClaimedAttribute()
	{
		return $this->claimed_id != NULL;
	}
	
	// ------------------------------------------------------------------------
	public function getClaimedAtAttribute($val)
	{
		return new Carbon\Carbon($val);
	}

	// ------------------------------------------------------------------------
	public function hasSetDate()
	{
		return $this->task_date!=NULL;
	}

	// ------------------------------------------------------------------------
	public function getDateAttribute()
	{
		return $this->task_date==NULL ? $this->created_at : new Carbon\Carbon($this->task_date);
	}

	// ------------------------------------------------------------------------
	public function project()
	{
		return $this->belongsTo('Project\Project');
	}

	// ------------------------------------------------------------------------
	public function delete() {
      	$notifications = $this->notification;
      	if($notifications)
      	{
      		foreach ($notifications as $n) {
      			$n->delete();
      		}
      	}
    	parent::delete();
    }

}	