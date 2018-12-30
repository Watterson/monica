<?php

namespace Tests\Unit\Services\Contact\Call;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Contact\Reminder;
use App\Models\User\User;
use App\Models\Contact\ReminderRule;
use App\Services\Contact\Reminder\DestroyReminder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DestroyReminderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_reminder()
    {
        $reminder = factory(Reminder::class)->create([
            'initial_date' => '2017-02-02',
            'frequency_type' => 'year',
            'frequency_number' => 1,
            'title' => 'title',
            'description' => 'description',
        ]);

        $request = [
            'account_id' => $reminder->account->id,
            'reminder_id' => $reminder->id,
        ];

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
        ]);

        (new DestroyReminder)->execute($request);

        $this->assertDatabaseMissing('reminders', [
            'id' => $reminder->id,
        ]);
    }

    public function test_it_destroys_scheduled_reminders()
    {
        // prepare a reminder and schedule some notifications
        Carbon::setTestNow(Carbon::create(2017, 2, 1));
        $user = factory(User::class)->create([]);
        $reminder = factory(Reminder::class)->create([
            'account_id' => $user->account_id,
            'initial_date' => '2017-02-02',
            'frequency_type' => 'year',
            'frequency_number' => 1,
            'title' => 'title',
            'description' => 'description',
        ]);
        $reminderRule = factory(ReminderRule::class)->create([
            'account_id' => $reminder->account_id,
            'number_of_days_before' => 30,
            'active' => 1,
        ]);

        $reminder->schedule();

        $this->assertDatabaseHas('reminder_outbox', [
            'reminder_id' => $reminder->id,
        ]);

        $request = [
            'account_id' => $reminder->account->id,
            'reminder_id' => $reminder->id,
        ];

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
        ]);

        (new DestroyReminder)->execute($request);

        $this->assertDatabaseMissing('reminders', [
            'id' => $reminder->id,
        ]);

        $this->assertDatabaseMissing('reminder_outbox', [
            'reminder_id' => $reminder->id,
        ]);
    }
}
