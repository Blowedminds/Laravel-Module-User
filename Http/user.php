<?php

Route::get('info', 'UserController@getUserInfo');

Route::get('profile', 'UserController@getUserProfile');

Route::post('profile', 'UserController@postUserProfile');

Route::post('profile-image', 'UserController@postUserProfileImage');

Route::get('menus/{language_slug}', 'UserController@getMenus');

Route::get('dashboard', function(){return response()->json(null, 200);});