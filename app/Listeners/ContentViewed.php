<?php

namespace App\Listeners;

use App\Events\ContentViewed as Event;
use App\Models\User;
use App\Models\View;
use Exception;
use GuzzleHttp\Client;
use Ramsey\Uuid\Uuid;

class ContentViewed
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(Event $event)
    {
        $user = $event->getUser() ?? new User();

        if ($user->email === 'cookiepro@lbresearch.com') {
            // don't register if cookiepro views
            return;
        }

        $agent = $event->getAgent();

        $entity = $event->getRouteable();

        View::create([
            'brand_machine_name' => $event->getBrandMachineName(),
            'routable_id' => $entity->getId(),
            'routable_type' => class_basename($entity),
            'platform' => $agent->platform(),
            'device' => $agent->device(),
            'browser' => $agent->browser(),
            'is_desktop' => $agent->isDesktop(),
            'is_phone' => $agent->isPhone(),
            'is_robot' => $agent->robot() ? 1 : 0,
            'version' => $agent->version($agent->browser()),
            'user_id' => $event->getUser() ? $event->getUser()->id : null,
            'is_full_read' => $event->isFullRead(),
            'is_free' => $event->isFree(),
            'type' => 'read',
            'ip' => Request()->ip(),
            'route' => Request()->route()->getName() ?? 'none'
        ]);

        try {
            $response = self::callShieldSquare($user);

            if (class_basename($entity) === 'Article') {
                $uid = $user->id;
                $payload = [
                    'interactionRef' => Uuid::uuid4(),
                    'viewRef' => Uuid::uuid4(),
                    'viewOfRef' => 500, //type of content: articles
                    'type' => 1,
                    'brand' => get_host_config('services.gbmginteractions'),
                    'viewDateTime' => now()->toISOString(),
                    'contentGuid' => $entity->getOriginalId(),
                    'contentApiId' => $entity->getId(),
                    'userId' => $uid,
                    //   'unregisteredUserId'=> string (for non registered user if you have an identifier, optional),
                    'robot' => (bool)$agent->robot() || self::isShieldSquareRobot($response->responsecode),
                    'ipAddress' => request()->ip(),
                    'userAgent' => $agent->getUserAgent(),
                    'environment' => app()->environment('production') ? 'prod' : app()->environment(),
                ];

                $client = new Client();
                $client->request('POST', 'https://gbmginteractions.azurewebsites.net/api/LogInteraction?code=Eo/axCM1kGsx2f6OUhI/bX9ngOUSBh0NyFeXp5t66RINT/J2bVqqjA==', [
                    'json' => $payload,
                ]);
                logger('interaction payload', $payload);
            }
        } catch (Exception $e) {
            report($e);
        }
    }

    private static function callShieldSquare(User $user)
    {
        $username = "";
        if ($user->id) {
            $username = hash('sha256', $user->id . '@' . get_host_config('host') . "-SomeRandomSeed-");
        }

        return shieldsquare_validaterequest($username);
    }

    private static function isShieldSquareRobot($responseCode)
    {
        return in_array($responseCode, [
            //-1 Timeout, 0 ALLOW, 2 CAPTCHA, 3 Block, 4 Feed Fake Data
            2, 3, 4,
        ]);
    }
}
