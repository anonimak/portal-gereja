<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Events\Pages;

use App\Filament\Clusters\Events\EventsCluster;
use App\Models\Event;
use App\Models\EventCategory;
use App\Support\ChurchContext;
use App\Traits\HasChurchScope;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class KalenderIbadahPage extends Page
{
    use HasChurchScope;

    protected static ?string $cluster = EventsCluster::class;

    protected static ?string $navigationLabel = 'Kalender Ibadah';

    protected static ?string $title = 'Kalender Ibadah & Acara';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDays;

    protected string $view = 'filament.pages.kalender-ibadah';

    public string $month = '';

    public ?int $categoryId = null;

    public ?int $churchSelect = null;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('event.view');
    }

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        $this->churchSelect = ChurchContext::activeChurchId();
    }

    public function updatedChurchSelect(int|string|null $value): void
    {
        if (auth()->user()?->role !== 'super_admin') {
            return;
        }

        ChurchContext::setActiveChurch($value ? (int) $value : null);
    }

    public function canSelectChurch(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    public function churchOptions(): array
    {
        return \App\Models\Church::query()
            ->withoutGlobalScopes()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function isAllChurches(): bool
    {
        return ChurchContext::isAll();
    }

    public function prevMonth(): void
    {
        $this->month = Carbon::parse($this->month.'-01')->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::parse($this->month.'-01')->addMonth()->format('Y-m');
    }

    public function todayMonth(): void
    {
        $this->month = now()->format('Y-m');
    }

    /**
     * @return array<string, mixed>
     */
    public function getCalendarData(): array
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $this->month = now()->format('Y-m');
        }

        $monthCarbon = Carbon::parse($this->month.'-01');
        $startOfMonth = $monthCarbon->copy()->startOfMonth();
        $endOfMonth = $monthCarbon->copy()->endOfMonth();

        // Rentang tampilan kalender (minggu pertama mulai dari Minggu, minggu terakhir berakhir di Sabtu)
        $calendarStart = $startOfMonth->copy()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $endOfMonth->copy()->endOfWeek(Carbon::SATURDAY);

        $eventsQuery = $this->scopeToActiveChurch(Event::query())
            ->with(['category', 'recurringSchedule'])
            ->whereBetween('start_datetime', [$calendarStart->copy()->startOfDay(), $calendarEnd->copy()->endOfDay()])
            ->orderBy('start_datetime');

        if ($this->categoryId) {
            $eventsQuery->where('category_id', $this->categoryId);
        }

        $events = $eventsQuery->get();
        $eventsByDate = $events->groupBy(fn (Event $e): string => $e->start_datetime->format('Y-m-d'));

        $weeks = [];
        $cursor = $calendarStart->copy();

        while ($cursor->lte($calendarEnd)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $dateStr = $cursor->format('Y-m-d');
                $dayEvents = $eventsByDate->get($dateStr, collect());

                $week[] = [
                    'date' => $dateStr,
                    'day' => $cursor->day,
                    'isCurrentMonth' => $cursor->month === $monthCarbon->month,
                    'isToday' => $cursor->isToday(),
                    'events' => $dayEvents,
                ];

                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        $categories = $this->scopeToActiveChurch(EventCategory::query())
            ->orderBy('name')
            ->get();

        $totalInMonth = $events->filter(fn (Event $e): bool => $e->start_datetime->month === $monthCarbon->month && $e->start_datetime->year === $monthCarbon->year)->count();

        return [
            'churchName' => $this->activeChurchName(),
            'monthLabel' => $monthCarbon->translatedFormat('F Y'),
            'month' => $this->month,
            'weeks' => $weeks,
            'categories' => $categories,
            'totalInMonth' => $totalInMonth,
        ];
    }
}
