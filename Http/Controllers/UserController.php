<?php

namespace App\Modules\User\Http\Controllers;

use App\Modules\Core\Traits\MenuTrait;
use App\Modules\Core\User;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;

use App\Modules\Core\Language;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    use MenuTrait;

    public function __construct()
    {
        $this->middleware(['auth:api']);
    }

    public function getUser()
    {
        $user = User::where('user_id', auth()->user()->user_id)->with(['roles', 'userData'])->firstOrFail();

        return response()->json([
            'name' => $user->name,
            'user_id' => $user->user_id,
            'role_id' => $user->role->id,
            'user_data' => $user->userData
        ]);
    }

    public function getUserProfile()
    {

        $user = User::where('user_id', auth()->user()->user_id)->with(['userData', 'roles'])->firstOrFail()->toArray();

        $user['user_data']['biography'] = $this->localizeField($user['user_data']['biography']);

        $user['role'] = $user['roles'][0];

        unset($user['roles']);

        return response()->json($user);
    }

    public function postUserProfile()
    {
        request()->validate([
            'name' => 'required',
            'biography' => 'required'
        ]);

        $user = User::where('user_id', auth()->user()->user_id)->with('userData')->firstOrFail();

        $user->name = request()->input('name');

        $user->userData->biography = $this->localizeField(request()->input('biography'));

        $user->userData->save();

        $user->save();

        return response()->json([
            'header' => 'İşlem Başarılı',
            'message' => 'Profil güncellendi',
            'action' => 'Tamam'
        ]);
    }

    public function postUserProfileImage()
    {
        request()->validate([
            'file' => 'required|image|max:33554432',
        ]);

        $user_data = auth()->user()->userData;

        Storage::disk('public')->delete("authors/images/" . $user_data->profile_image);

        $file = request()->file('file');

        $store_name = uniqid('img_') . "." . $file->extension();

        $user_data->profile_image = $store_name;

        $user_data->save();

        $path = Storage::disk('public')->path('authors/images/');

        request()->file('file')->move($path, $store_name);

        return response()->json([
            'header' => 'İşlem Başarılı',
            'message' => 'Profil Fotoğrafı güncellendi',
            'action' => 'Tamam'
        ]);
    }

    public function getDashboard()
    {
        $user = auth()->user();

        $dashboard = Cache::remember("{$user->user_id}:dashboard", 0, static function() use ($user) {
            $cache = [
                'articles' => [],
            ];

            $cache['articles']['most_viewed'] = $user->articles()->with('contents')->orderBy('views', 'desc')->take(3)->get();

            return $cache;
        });

        return response()->json($dashboard);
    }

    public function getUserMenus($locale)
    {
        $role_slug = auth()->user()->role->slug;

        $menus = $this->getRoleMenus($locale, $role_slug);

        return response()->json($menus);
    }

    private function localizeField($field)
    {
        foreach (Language::all() as $language)
            $field[$language->slug] = $field[$language->slug] ?? '';

        return $field;
    }
}
