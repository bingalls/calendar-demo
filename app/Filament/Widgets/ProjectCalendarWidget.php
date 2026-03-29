<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\Task;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Guava\Calendar\Attributes\CalendarSchema;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\CalendarWidget as BaseCalendarWidget;
use Guava\Calendar\ValueObjects\EventDropInfo;
use Guava\Calendar\ValueObjects\EventResizeInfo;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

class ProjectCalendarWidget extends BaseCalendarWidget
{
    protected bool $eventClickEnabled = true;

    protected bool $eventDragEnabled = true;

    protected bool $eventResizeEnabled = true;

    protected HtmlString|string|bool|null $heading = 'Project Calendar';

    protected CalendarViewType $calendarView = CalendarViewType::ResourceTimeGridDay;

    public function getEvents(FetchInfo $info): Collection | array
    {
        return collect()
            ->push(...Task::query()->get())
        ;
    }

    public function getResources(): Collection | array
    {
        return collect()
            ->push(...Project::query()->get())
        ;
    }

    public function getHeaderActions(): array
    {
        return [
            CreateAction::make('createTask')
                ->model(Task::class),
        ];
    }

    public function getEventClickContextMenuActions(): array
    {
        return [
            $this->editAction(),
            $this->deleteAction(),
        ];
    }

    // This is a custom method, just to avoid duplicating code
    private function getDateContextMenuActions(): array
    {
        return [
            CreateAction::make('ctxCreateTask')
                ->model(Task::class)
                ->mountUsing(function (Form $form, array $arguments) {
                    $projectId = data_get($arguments, 'resource.id');
                    $date = data_get($arguments, 'dateStr');
                    $startsAt = Carbon::make(data_get($arguments, 'startStr', $date));
                    $endsAt = Carbon::make(data_get($arguments, 'endStr', $date));

                    if ($endsAt->diffInMinutes($startsAt) < 0.01) {
                        $endsAt->addMinutes(30);
                    }

                    if ($startsAt) {    // $endsAt > 0 due to check above
                        $form->fill([
                            'project_id' => $projectId,
                            'starts_at' => Carbon::make($startsAt),
                            'ends_at' => Carbon::make($endsAt),
                        ]);
                    }
                }),
        ];
    }

    public function getDateClickContextMenuActions(): array
    {
        return $this->getDateContextMenuActions();
    }

    public function getDateSelectContextMenuActions(): array
    {
        return $this->getDateContextMenuActions();
    }

    #[CalendarSchema(Task::class)]
    public function taskSchema(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->required(),
            RichEditor::make('description'),
            Group::make([
                DateTimePicker::make('starts_at')
                    ->native(false)
                    ->seconds(false)
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->native(false)
                    ->seconds(false)
                    ->required(),
            ])->columns(),
            Select::make('user_id')
                ->relationship('user', 'name')
                ->searchable()
                ->preload(),
            Select::make('project_id')
                ->relationship('project', 'title')
                ->searchable()
                ->preload()
                ->required(),
        ]);
    }

    public function onEventDrop(EventDropInfo $info, Model $event): bool
    {
        if ($this->getModel() === Task::class) {
            $record = $this->getRecord();

            if ($delta = data_get($info, 'delta')) {
                $startsAt = $record->starts_at;
                $endsAt = $record->ends_at;
                $startsAt->addSeconds(data_get($delta, 'seconds'));
                $endsAt->addSeconds(data_get($delta, 'seconds'));
                $record->update([
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                ]);

                Notification::make()
                    ->title('Event date moved!')
                    ->success()
                    ->send()
                ;
            }

            return true;
        }

        return false;
    }

    public function onEventResize(EventResizeInfo $info, Model $event): bool
    {
        if ($this->getModel() === Task::class) {
            $record = $this->getRecord();
            if ($delta = data_get($info, 'endDelta')) {
                $endsAt = $record->ends_at;
                $endsAt->addSeconds(data_get($delta, 'seconds'));
                $record->update([
                    'ends_at' => $endsAt,
                ]);
            }

            Notification::make()
                ->title('Event duration changed!')
                ->success()
                ->send()
            ;

            return true;

        }

        Notification::make()
            ->title('Duration of this event cannot be changed!')
            ->danger()
            ->send()
        ;

        return false;
    }

    public function getOptions(): array
    {
        return [
            'slotMinTime' => '08:00:00',
            'slotMaxTime' => '16:00:00',
        ];
    }
}
