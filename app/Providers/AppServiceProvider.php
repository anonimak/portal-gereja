<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\BirthRecord;
use App\Models\Church;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventCategory;
use App\Models\EventRoster;
use App\Models\Family;
use App\Models\FinancialCategory;
use App\Models\Fund;
use App\Models\Member;
use App\Models\MemberSacrament;
use App\Models\MinistryRole;
use App\Models\Official;
use App\Models\Transaction;
use App\Models\User;
use App\Observers\ChurchObserver;
use App\Observers\UserObserver;
use App\Policies\BirthRecordPolicy;
use App\Policies\ChurchPolicy;
use App\Policies\EventAttendancePolicy;
use App\Policies\EventCategoryPolicy;
use App\Policies\EventPolicy;
use App\Policies\EventRosterPolicy;
use App\Policies\FamilyPolicy;
use App\Policies\FinancialCategoryPolicy;
use App\Policies\FundPolicy;
use App\Policies\MemberPolicy;
use App\Policies\MemberSacramentPolicy;
use App\Policies\MinistryRolePolicy;
use App\Policies\OfficialPolicy;
use App\Policies\TenantPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Church observer to auto-seed default funds and categories
        Church::observe(ChurchObserver::class);

        // Register User observer — guard anti privilege-escalation di level model
        User::observe(UserObserver::class);

        // ---- Register policies (RBAC) ----
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Church::class, ChurchPolicy::class);
        Gate::policy(Official::class, OfficialPolicy::class);
        Gate::policy(Family::class, FamilyPolicy::class);
        Gate::policy(Member::class, MemberPolicy::class);
        Gate::policy(MemberSacrament::class, MemberSacramentPolicy::class);
        Gate::policy(BirthRecord::class, BirthRecordPolicy::class);
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(EventRoster::class, EventRosterPolicy::class);
        Gate::policy(EventCategory::class, EventCategoryPolicy::class);
        Gate::policy(EventAttendance::class, EventAttendancePolicy::class);
        Gate::policy(Fund::class, FundPolicy::class);
        Gate::policy(FinancialCategory::class, FinancialCategoryPolicy::class);
        Gate::policy(MinistryRole::class, MinistryRolePolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);

        // Fallback default: resource tenant lain yang tidak punya policy spesifik
        // tetap terlindungi oleh aturan tenant (church_id) via TenantPolicy.
        Gate::before(fn (User $user, string $ability, mixed $arguments = null) => null);
    }
}
