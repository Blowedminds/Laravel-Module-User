<?php


namespace App\Observers;

use App\User;

class UserObserver
{
    public function deleting(User $user)
    {
        $user->userData->delete();
    }

    public function forceDeleted(User $user)
    {
        $user->userData()->onlyTrashed()->forceDelete();

        $user->userData->delete();
    }
}