<?php

namespace Database\Seeders;

use App\Models\Accreditation;
use App\Models\Amendment;
use App\Models\Assessment;
use App\Models\Backup;
use App\Models\CalendarEvent;
use App\Models\Issue;
use App\Models\Lpk;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Petugas Demo',
            'email' => 'demo@kanmis.local',
            'password' => 'password',
        ]);

        $lpks = Lpk::factory(8)->create();

        $lpks->each(function (Lpk $lpk, int $index): void {
            Accreditation::factory()->create([
                'lpk_id' => $lpk->id,
                'status' => ['IN_PROGRESS', 'NOT_STARTED', 'COMPLETED'][$index % 3],
            ]);
        });

        $issues = Issue::factory(6)->create(['created_by' => $user->id]);
        $issues->take(3)->each(function (Issue $issue) use ($user): void {
            $issue->followups()->create([
                'user_id' => $user->id,
                'note' => 'Data awal untuk mempelajari alur tindak lanjut.',
            ]);
        });

        $lpks->take(4)->each(function (Lpk $lpk, int $index) use ($user): void {
            $start = now()->startOfMonth()->addDays($index * 2 + 1)->setTime(9, 0);
            CalendarEvent::factory()->create([
                'lpk_id' => $lpk->id,
                'created_by' => $user->id,
                'start_at' => $start,
                'end_at' => $start->copy()->addHours(2),
                'status' => ['PLANNED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'][$index],
            ]);
        });
        Service::factory(3)->create()->each(fn (Service $service) => $service->checks()->create(['status' => $service->status, 'checked_at' => now(), 'message' => 'Pemeriksaan manual untuk latihan.', 'checked_by' => $user->id]));
        Backup::factory(4)->create(['recorded_by' => $user->id]);
        Amendment::factory(5)->create(['created_by' => $user->id]);
        Assessment::factory(6)->create(['created_by' => $user->id]);
    }
}
