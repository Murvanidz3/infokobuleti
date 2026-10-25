<?php

namespace Database\Seeders;

use App\Enums\BusinessType;
use App\Enums\Role;
use App\Models\User;
use Database\Seeders\Concerns\SeedsDemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Staff accounts, non-hotel businesses and individual users. Hotel owners are created by HotelSeeder.
 */
class DemoUserSeeder extends Seeder
{
    use SeedsDemoData;

    /** @var list<array{0: Role, 1: string, 2: string}> */
    private const array STAFF = [
        [Role::SuperAdmin, 'superadmin@infokobuleti.com', 'ლევან ბერიძე'],
        [Role::Admin, 'admin@infokobuleti.com', 'ნინო ხალვაში'],
        [Role::Moderator, 'moderator@infokobuleti.com', 'თამარ ცინცაძე'],
    ];

    /** @var list<array{0: string, 1: string, 2: string}> type, name, location */
    private const array BUSINESSES = [
        ['agency', 'ზღვის სახლი', 'kobuleti-centre'],
        ['agency', 'Kobuleti Estate', 'north-kobuleti'],
        ['agency', 'Adjara Homes', 'kobuleti-centre'],
        ['restaurant', 'რესტორანი „ბულვარი“', 'kobuleti-centre'],
        ['restaurant', 'აჭარული სახლი', 'kobuleti-centre'],
        ['restaurant', 'თევზის რესტორანი „ნავსადგური“', 'north-kobuleti'],
        ['employer', 'ზღვისპირა მარკეტი', 'kobuleti-centre'],
        ['employer', 'კოლხეთ ბილდი', 'south-kobuleti'],
        ['employer', 'ფარმა ქობულეთი', 'kobuleti-centre'],
        ['employer', 'ენების ცენტრი „ლინგვა“', 'kobuleti-centre'],
        ['employer', 'პლაჟების მომსახურების ცენტრი', 'kobuleti-centre'],
        ['service', 'ქობულეთი ტრანსფერი', 'kobuleti-centre'],
        ['service', 'ავტოსერვისი „ჩაქვი“', 'chakvi'],
        ['service', 'Adjara Adventures', 'north-kobuleti'],
        ['service', 'სილამაზის სალონი „ია“', 'south-kobuleti'],
    ];

    public function run(): void
    {
        $this->staff();

        foreach (self::BUSINESSES as [$type, $name, $location]) {
            $this->createBusiness(BusinessType::from($type), $name, $this->placeAt($location));
        }

        $this->individualUsers();
    }

    private function staff(): void
    {
        $password = config('demo.password');

        if (blank($password)) {
            $password = Str::password(16, symbols: false);
            $this->command?->warn("DEMO_PASSWORD is not set; staff accounts use this one-off password: {$password}");
        }

        foreach (self::STAFF as [$role, $email, $name]) {
            User::factory()->staff($password)->create(['name' => $name, 'email' => $email])->assignRole($role->value);
        }
    }

    private function individualUsers(): void
    {
        $users = User::factory()->count(30)->create()
            ->concat(User::factory()->count(5)->phoneOnly()->create())
            ->concat(User::factory()->count(4)->google()->create())
            ->concat(User::factory()->count(2)->unverified()->create())
            ->push(User::factory()->blocked()->create());

        $users->each(fn (User $user) => $user->assignRole(Role::User->value));

        User::query()->whereKey($users->random(6)->modelKeys())->update(['locale' => 'ru']);
        User::query()->whereKey($users->random(4)->modelKeys())->update(['locale' => 'en']);
    }
}
