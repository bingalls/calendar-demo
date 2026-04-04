<?php

use App\Models\Meeting;
use App\Models\User;
use Carbon\Carbon;
use Guava\Calendar\ValueObjects\CalendarEvent;

it('can be created via factory', static function () {
    $meeting = Meeting::factory()->create();

    expect($meeting)->toBeInstanceOf(Meeting::class)
        ->and($meeting->title)->not->toBeEmpty()
        ->and($meeting->starts_at)->toBeInstanceOf(Carbon::class)
        ->and($meeting->ends_at)->toBeInstanceOf(Carbon::class);
});

it('has fillable attributes', static function () {
    $meeting = Meeting::factory()->create([
        'title' => 'Weekly Standup',
        'description' => 'Daily sync meeting',
    ]);

    expect($meeting->title)->toBe('Weekly Standup')
        ->and($meeting->description)->toBe('Daily sync meeting');
});

it('casts starts_at and ends_at to datetime', static function () {
    $meeting = Meeting::factory()->create();

    expect($meeting->starts_at)->toBeInstanceOf(Carbon::class)
        ->and($meeting->ends_at)->toBeInstanceOf(Carbon::class);
});

it('converts to a CalendarEvent', static function () {
    $meeting = Meeting::factory()->create([
        'title' => 'Board Meeting',
        'starts_at' => '2026-04-03 09:00:00',
        'ends_at' => '2026-04-03 10:00:00',
    ]);

    $event = $meeting->toCalendarEvent();

    expect($event)->toBeInstanceOf(CalendarEvent::class);
});

it('calendar event has correct title', static function () {
    $meeting = Meeting::factory()->create(['title' => 'Sprint Review']);
    $event = $meeting->toCalendarEvent();

    expect($event->getTitle())->toBe('Sprint Review');
});

it('calendar event has correct start and end times', static function () {
    $starts = Carbon::parse('2026-04-05 10:00:00');
    $ends = Carbon::parse('2026-04-05 11:00:00');

    $meeting = Meeting::factory()->create([
        'starts_at' => $starts,
        'ends_at' => $ends,
    ]);

    $event = $meeting->toCalendarEvent();

    expect($event->getStart()->toDateTimeString())->toBe($starts->toDateTimeString())
        ->and($event->getEnd()->toDateTimeString())->toBe($ends->toDateTimeString());
});

it('calendar event extended props include participant count', static function () {
    $meeting = Meeting::factory()->create();
    $users = User::factory()->count(3)->create();
    $meeting->users()->attach($users->pluck('id'));

    $event = $meeting->toCalendarEvent();

    expect($event->getExtendedProps())->toHaveKey('participants', 3);
});

it('calendar event is not duration editable', static function () {
    $meeting = Meeting::factory()->create();
    $event = $meeting->toCalendarEvent();

    expect($event->getDurationEditable())->toBeFalse();
});

it('belongs to many users', static function () {
    $meeting = Meeting::factory()->create();
    $users = User::factory()->count(2)->create();
    $meeting->users()->attach($users->pluck('id'));

    expect($meeting->users)->toHaveCount(2);
});


