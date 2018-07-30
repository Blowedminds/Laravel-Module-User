<?php

namespace App\Modules\User\Http\Controllers;

use App\Modules\Core\User;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Controller;

use App\Modules\Core\Language;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api']);
    }

    public function getUser()
    {
        $user = User::where('user_id', auth()->user()->user_id)->with(['roles', 'userData'])->first();

        return response()->json([
            'name' => $user->name,
            'user_id' => $user->user_id,
            'role_id' => $user->role->id,
            'user_data' => $user->userData
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

        return response()->json([
            'header' => 'İşlem Başarılı',
            'message' => 'Profil güncellendi',
            'state' => 'success',
            'action' => 'Tamam'
        ]);
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

        return response()->json([
            'header' => 'İşlem Başarılı',
            'message' => 'Profil Fotoğrafı güncellendi',
            'state' => 'success',
            'action' => 'Tamam'
        ]);
    }

    public function getMenus($language_slug)
    {

        $menus = auth()->user()->role->with('permissions.menus')
            ->first()
            ->permissions
            ->reduce(function ($menus, $reduce) use ($language_slug) {

                foreach ($reduce->menus as $key => $value) {

                    $exist = false;

                    foreach ($menus as $menu) {
                        if ($menu['id'] === $value['id']) {
                            $exist = true;
                            break;
                        }
                    }

                    if (!$exist) {
                        $value->name = $value->name[$language_slug];

                        $value->tooltip = $value->tooltip[$language_slug];

                        $value = $value->toArray();

                        $value['children'] = [];

                        $reduce->menus[$key] = $value;
                        $menus[] = $reduce->menus[$key];
                    }
                }

                return $menus;
            }, []);

        //        $menus = auth()->user()->role->menus()
//            ->orderBy('weight', 'DESC')
//            ->get()
//            ->map(function ($menu) use ($language_slug) {
//                return [
//                    'id' => $menu->id,
//                    'name' => $menu->name[$language_slug] ?? '',
//                    'tooltip' => $menu->tooltip[$language_slug] ?? '',
//                    'url' => $menu->url,
//                    'weight' => $menu->weight,
//                    'parent' => $menu->parent,
//                    'children' => []
//                ];
//            })->toArray();

        for ($i = 0, $count = count($menus); $i < $count; $i++) {

            $menu = array_pop($menus);

            $placed = false;

            foreach ($menus as $key => $target) {
                if ($this->recurseMenus($menus[$key], $menu)) {
                    $placed = true;
                    break;
                }
            }

            if (!$placed) {
                array_unshift($menus, $menu);
            }
        }

        usort($menus, function ($a, $b) {
            return $a['weight'] - $b['weight'];
        });

        return response()->json($menus);
    }

    private function recurseMenus(&$target, &$menu)
    {
        if ($menu['parent'] === $target['id']) {
            $target['children'][] = $menu;
            return true;
        }

        foreach ($target['children'] as $key => $child) {
            if ($this->recurseMenus($target['children'][$key], $menu)) {
                return true;
            };
        }

        return false;
    }

    private function localizeField($field)
    {
        foreach (Language::all() as $language)
            $field[$language->slug] = $field[$language->slug] ?? '';

        return $field;
    }
}
