<?php /** @noinspection StaticClosureCanBeUsedInspection */

use App\Enums\Priority;
use App\Models\Sprint;
use Carbon\Carbon;
use Guava\Calendar\ValueObjects\CalendarEvent;

it('can be created via factory', function () {
    $sprint = Sprint::factory()->create();

    expect($sprint)->toBeInstanceOf(Sprint::class)
        ->and($sprint->title)->not->toBeEmpty()
        ->and($sprint->starts_at)->toBeInstanceOf(Carbon::class)
        ->and($sprint->ends_at)->toBeInstanceOf(Carbon::class);
});

it('has fillable attributes', function () {
    $sprint = Sprint::factory()->create([
        'title' => 'Sprint 1',
        'priority' => Priority::High,
    ]);

    expect($sprint->title)->toBe('Sprint 1')
        ->and($sprint->priority)->toBe(Priority::High);
});

it('casts priority to Priority enum', function () {
    $sprint = Sprint::factory()->create(['priority' => Priority::Urgent]);

    expect($sprint->priority)->toBeInstanceOf(Priority::class)
        ->and($sprint->priority)->toBe(Priority::Urgent);
});

it('casts starts_at and ends_at to datetime', function () {
    $sprint = Sprint::factory()->create();

    expect($sprint->starts_at)->toBeInstanceOf(Carbon::class)
        ->and($sprint->ends_at)->toBeInstanceOf(Carbon::class);
});

it('converts to a CalendarEvent', function () {
    $sprint = Sprint::factory()->create();

    expect($sprint->toCalendarEvent())->toBeInstanceOf(CalendarEvent::class);
});

it('calendar event has correct title', function () {
    $sprint = Sprint::factory()->create(['title' => 'Q2 Sprint']);
    $event = $sprint->toCalendarEvent();

    expect($event->getTitle())->toBe('Q2 Sprint');
});

it('calendar event has correct start and end', function () {
    $starts = Carbon::parse('2026-04-07');
    $ends = Carbon::parse('2026-04-14');

    $sprint = Sprint::factory()->create([
        'starts_at' => $starts,
        'ends_at' => $ends,
    ]);

    $event = $sprint->toCalendarEvent();

    expect($event->getStart()->toDateString())->toBe($starts->toDateString())
        ->and($event->getEnd()->toDateString())->toBe($ends->toDateString());
});

it('calendar event extended props include priority label', function () {
    $sprint = Sprint::factory()->create(['priority' => Priority::High]);
    $event = $sprint->toCalendarEvent();

    expect($event->getExtendedProps())->toHaveKey('priority', 'High priority');
});

