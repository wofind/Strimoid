<?php namespace Strimoid\Models;

use Auth, Settings, Validator;
use DateTimeZone;
use Jenssegers\Mongodb\Model;

class BaseModel extends Model
{

    public function getLocalTime()
    {
        $timezone = new DateTimeZone(Settings::get('timezone'));

        return $this->created_at->setTimeZone($timezone)->format('d/m/Y H:i:s');
    }

    public function getVoteState()
    {
        if (Auth::guest() || !$this->votes()) return 'none';

        $vote = $this->votes()->where('user_id', Auth::user()->getKey())->first();

        if (!$vote) return 'none';

        return $vote->up ? 'uv' : 'dv';
    }

    public function getVoteStateAttribute() {
        return $this->getVoteState();
    }

    public function votes()
    {
        return $this->embedsMany('Strimoid\Models\Vote', 'votes');
    }

    public function saves()
    {
        return $this->embedsMany('Strimoid\Models\Save', 'saves');
    }

    public function isSaved(User $user = null)
    {
        if (!$user)
        {
            if (Auth::guest()) return false;
            $user = Auth::user();
        }

        return $this->saves()->where('user_id', $user->getKey())->first();
    }

    public function mpush($column, $value = null, $unique = false)
    {
        if (!$this->_id)
            return new Exception('Tried to push on model without id');

        $builder = $this->newQuery()->where('_id', $this->_id);
        $builder->push($column, $value, $unique);
    }

    public function mpull($column, $value = null)
    {
        if (!$this->_id)
            return new Exception('Tried to pull on model without id');

        $builder = $this->newQuery()->where('_id', $this->_id);
        $builder->pull($column, $value);
    }

    public static function validate($input)
    {
        return Validator::make($input, static::$rules);
    }

}