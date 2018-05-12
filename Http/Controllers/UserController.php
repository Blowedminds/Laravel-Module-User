<?php

namespace App\Modules\User\Http\Controllers;

use App\User;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Controller;

use App\Language;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api']);
    }

    public function getUserInfo()
    {
        $user = User::where('user_id', auth()->user()->user_id)->with('roles')->first();

        return response()->json([
            'name' => $user->name,
            'user_id' => $user->user_id,
            'role_id' => $user->role->id
        ]);
    }

    public function getUserProfile()
    {

        $user = User::where('user_id', auth()->user()->user_id)->with(['userData', 'roles'])->first()->toArray();

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

        $user = User::where('user_id', auth()->user()->user_id)->with('userData')->first();

        $user->name = request()->input('name');

        $user->userData->biography = $this->localizeField(request()->input('biography'));

        $user->userData->save();

        $user->save();

        return response()->json(['Tebrikler']);
    }

    public function postUserProfileImage()
    {
        request()->validate([
            'file' => 'required|image|max:33554432',
        ]);

        $user_data = auth()->user()->userData;

        $file = request()->file('file');

        $extension = $file->extension();

        $u_id = uniqid('img_');

        $store_name = $u_id . "." . $extension;

        File::delete("images/author/" . $user_data->profile_image);

        $user_data->profile_image = $store_name;

        $user_data->save();

        $path = rtrim(app()->basePath('public/' . "images/author"), '/');

        request()->file('file')->move($path, $store_name);

        return response()->json(['TEBRIKLER']);
    }

    public function getMenus($language_slug)
    {
        $menus = auth()->user()->role->menus()->orderBy('weight', 'DESC')->get()->map(function ($menu) use ($language_slug) {
            return [
                'name' => json_decode($menu->name, true)[$language_slug],
                'tooltip' => json_decode($menu->tooltip, true)[$language_slug],
                'url' => $menu->url,
                'weight' => $menu->weight
            ];
        })->toArray();

        return response()->json($menus);
    }

    private function localizeField($field)
    {
        foreach (Language::all() as $language)
            $field[$language->slug] = $field[$language->slug] ?? '';

        return $field;
    }
}
