<?php


namespace App\Modules\User\Test\Feature;


use App\Modules\Core\Role;
use App\Modules\Core\Tests\TestCase;
use App\Modules\Core\User;
use App\Modules\Core\UserData;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class UserModuleTest extends TestCase
{
    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->make();
    }

    public function testRoutes(): void
    {
        $this->assertTrue($this->checkRoute($this->userRoute . 'info'));
        $this->assertTrue($this->checkRoute($this->userRoute . 'profile'));
        $this->assertTrue($this->checkRoute($this->userRoute . 'profile', 'post'));
        $this->assertTrue($this->checkRoute($this->userRoute . 'profile-image', 'post'));
        $this->assertTrue($this->checkRoute($this->userRoute . 'menus/{language_slug}'));
        $this->assertTrue($this->checkRoute($this->userRoute . 'dashboard'));
    }

    public function testGetUser(): void
    {
        $user = factory(User::class)->create();

        factory(UserData::class)->create([
            'user_id' => $user->user_id,
            'role_id' => static function () {
                return factory(Role::class)->create()->id;
            }
        ]);

        $response = $this->actingAs($user)->getJson($this->userRoute . 'info');

        $response->assertStatus(200);

        $data = json_decode($response->getContent(), true);

        $this->assertSame($user->user_id, $data['user_id']);
    }

    public function testGetUserProfile(): void
    {
        $user = factory(User::class)->create();

        factory(UserData::class)->create([
            'user_id' => $user->user_id,
            'role_id' => static function () {
                return factory(Role::class)->create()->id;
            }
        ]);

        $response = $this->actingAs($user)->getJson($this->userRoute . 'profile');

        $response->assertStatus(200);

        $data = json_decode($response->getContent(), true);

        $this->assertSame($user->user_id, $data['user_id']);
    }

    public function testPostUserProfile(): void
    {
        $user = factory(User::class)->create();

        factory(UserData::class)->create([
            'user_id' => $user->user_id,
            'role_id' => static function () {
                return factory(Role::class)->create()->id;
            }
        ]);

        $user1 = factory(User::class)->make();
        $userData1 = factory(UserData::class)->make();

        $response = $this->actingAs($user)->postJson($this->userRoute . 'profile', [
            'name' => $user1->name,
            'biography' => $userData1->biography
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas($user->getTable(), ['name' => $user1->name, 'user_id' => $user->user_id]);
        $this->assertDatabaseHas($userData1->getTable(), ['biography' => json_encode($userData1->biography), 'user_id' => $user->user_id]);
    }

    public function testPostUserProfileImage(): void
    {
        $userData = factory(UserData::class)->create();

        Storage::fake('public');

        $file = UploadedFile::fake()->image('author.jpeg');

        $response = $this->actingAs($userData->user)->postJson($this->userRoute . 'profile-image', [
            'file' => $file
        ]);

        $response->assertStatus(200);

        $userDataDB = UserData::where('user_id', $userData->user_id)->firstOrFail();

        $this->assertNotSame($userData->profile_image, $userDataDB->profile_image);

        Storage::disk('public')->assertExists("authors/images/{$userDataDB->profile_image}");
    }

    public function testGetDashboard(): void
    {
        $this->actingAs($this->user)->getJson($this->userRoute . 'dashboard')->assertStatus(200);
    }

    public function testUserMenus(): void
    {
        $userData = factory(UserData::class)->create();

        $response = $this->actingAs($userData->user)->getJson($this->userRoute . 'menus/' . Config::get('app.locale'));

        $response->assertStatus(200);
    }
}
