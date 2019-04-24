# Laravel-Module-Auth

This module supports backend for Angular-Module-User

**Required packages**
*--no required packages--*

**Required Modules**
1. Laravel-Module-Core
2. Laravel-Module-Admin

**Functionalities**
1. Update user profile image
2. Reset user password.

**Installation**
1. Add the module to Laravel project as a submodule. 
`git submodule add https://github.com/bwqr/Laravel-Module-User app/Modules/User`
2. Add the route file `Http/user.php` to `app/Providers/RouteServiceProvider.php`
 and register inside the `map` function, eg.  
 `
    protected function mapUserRoutes()
    {
        Route::prefix('user')
            ->middleware('api')
            ->namespace($this->moduleNamespace . "\User\Http\Controllers")
            ->group(base_path('app/Modules/User/Http/user.php'));
    }
 `
3. Add `Observers/UserObserver` to `app/Providers/AppServiceProvider.php` file 
in boot function. eg, `User::observe(UserObserver::class)`
