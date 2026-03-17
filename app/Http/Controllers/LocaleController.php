<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use App\Support\LocaleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:en,pt-BR'],
        ]);

        $locale = LocaleManager::normalize($validated['locale']);

        $request->session()->put('locale', $locale);
        app()->setLocale(LocaleManager::toAppLocale($locale));

        $user = Auth::user();

        if ($user) {
            UserSetting::query()->updateOrCreate(
                [
                    'user_uuid' => $user->user_uuid,
                    'user_setting_category' => 'domain',
                    'user_setting_subcategory' => 'language',
                ],
                [
                    'domain_uuid' => $user->domain_uuid,
                    'user_setting_name' => 'code',
                    'user_setting_value' => LocaleManager::toDatabaseLocale($locale),
                    'user_setting_enabled' => true,
                ]
            );
        }

        return redirect()->back(303);
    }
}
