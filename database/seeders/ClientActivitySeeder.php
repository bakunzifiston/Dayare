<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientActivity;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seed client activities for testing CRM: calls, emails, meetings.
 * Rwanda context; occurred_at in the past.
 */
class ClientActivitySeeder extends Seeder
{
    private const SUBJECTS = [
        'call' => ['Follow-up on order', 'Delivery schedule', 'Quality feedback', 'New order inquiry'],
        'email' => ['Quote request', 'Contract renewal', 'Invoice sent', 'Product availability'],
        'meeting' => ['Site visit — Kigali', 'Contract signing', 'Quarterly review', 'New product demo'],
        'note' => ['Client prefers weekend delivery', 'RWF payment terms agreed', 'Preferred cut: beef ribs'],
    ];

    public function run(): void
    {
        $clients = Client::where('is_active', true)->get();
        if ($clients->isEmpty()) {
            $this->command?->warn('No active clients. Run TestDataSeeder first.');
            return;
        }

        $userIds = User::pluck('id')->toArray();
        $userId = ! empty($userIds) ? $userIds[array_rand($userIds)] : null;

        foreach ($clients as $client) {
            $count = rand(2, 4);
            foreach (['call', 'email', 'meeting', 'note'] as $idx => $type) {
                if ($idx >= $count) {
                    break;
                }
                $subjects = self::SUBJECTS[$type] ?? ['Activity'];
                $subject = $subjects[array_rand($subjects)];
                ClientActivity::firstOrCreate(
                    [
                        'business_id' => $client->business_id,
                        'client_id' => $client->id,
                        'activity_type' => $type,
                        'subject' => $subject,
                        'occurred_at' => now()->subDays(rand(1, 30))->setHour(rand(9, 17))->setMinute(0),
                    ],
                    [
                        'notes' => 'Rwanda — ' . $subject . ' for ' . $client->name . '.',
                        'user_id' => $userId,
                    ]
                );
            }
        }

        $this->command?->info('Client activities seeded (calls, emails, meetings, notes).');
    }
}
