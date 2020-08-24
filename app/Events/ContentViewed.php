<?php

namespace App\Events;

use Auth;
use App\Models\Brand;
use App\Models\Interfaces\Routable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Jenssegers\Agent\Agent;

class ContentViewed
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    protected $serverall;
    protected $user;
    protected $routeable;
    protected $full_read;
    protected $free;
    protected $brandMachineName;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($routeable)
    {
        $this->brandMachineName = active_host();


        $this->serverall = Request()->server->all();
        $this->user = Auth::user();
        $this->routeable = $routeable;
        if (method_exists($routeable, 'isFree')) {
            $this->free = ($this->user && ! $this->user->isSubscriber()) || $routeable->isFree();
            $this->full_read = $this->user ? $routeable->canView($this->user) : $routeable->isFree();
        } else {
            $this->free = 0;
            $this->full_read = 1;
        }
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getAgent()
    {
        return new Agent($this->serverall);
    }

    public function getRouteable()
    {
        return $this->routeable;
    }

    public function isFullRead()
    {
        return $this->full_read;
    }

    public function isFree()
    {
        return $this->free;
    }

    public function getBrandMachineName()
    {
        return $this->brandMachineName;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
