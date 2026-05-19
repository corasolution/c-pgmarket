<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Cart;
use App\Models\Category;
use App\Models\ChatbotSetting;
use App\Models\Favorite;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'locale' => app()->getLocale(),
            'siteLogo' => function (): string {
                $logo = SiteSetting::get('site_logo');

                return ($logo !== null && $logo !== '') ? '/storage/' . $logo : '/logo.png';
            },
            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
            'navCategories' => fn () => Cache::remember('nav_categories', 600, fn () => Category::query()
                ->where('is_active', true)
                ->whereNull('parent_id')
                ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')->select(['id', 'parent_id', 'name_i18n', 'slug'])])
                ->orderBy('sort_order')
                ->get(['id', 'name_i18n', 'slug'])
                ->toArray()
            ),
            'cartCount' => function () use ($request): int {
                try {
                    $cart = $request->user() !== null
                        ? Cart::where('user_id', $request->user()->id)->first()
                        : Cart::where('session_id', $request->session()->getId())->first();

                    return $cart ? (int) $cart->items()->sum('quantity') : 0;
                } catch (\Throwable) {
                    return 0;
                }
            },
            'chatbotEnabled' => fn (): bool => Cache::remember('chatbot_enabled', 300, function (): bool {
                try {
                    return ChatbotSetting::current()->is_enabled;
                } catch (\Throwable) {
                    return false;
                }
            }),
            'favoriteIds' => function () use ($request): array {
                if (! $request->user()) {
                    return [];
                }
                try {
                    return Cache::remember(
                        'user_favorites_' . $request->user()->id,
                        120,
                        fn () => Favorite::where('user_id', $request->user()->id)
                            ->pluck('product_id')
                            ->toArray()
                    );
                } catch (\Throwable) {
                    return [];
                }
            },
        ];
    }
}
