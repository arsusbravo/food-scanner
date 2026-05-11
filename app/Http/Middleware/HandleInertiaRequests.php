<?php

namespace App\Http\Middleware;

use App\Models\ContactConversation;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $contactUnread = 0;
        $adminContactUnread = 0;

        if ($user) {
            if ($user->is_admin) {
                $adminContactUnread = ContactConversation::where('unread_by_admin', true)->count();
            } else {
                $contactUnread = ContactConversation::where('user_id', $user->id)
                    ->where('unread_by_user', true)
                    ->count();
            }
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), [
                    'is_pro' => $user->effectivePlan() !== 'free',
                ]) : null,
            ],
            'sidebarOpen'        => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'locale'             => $request->cookie('locale', 'en'),
            'contactUnread'      => $contactUnread,
            'adminContactUnread' => $adminContactUnread,
        ];
    }
}
