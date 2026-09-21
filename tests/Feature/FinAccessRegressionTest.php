<?php

namespace Tests\Feature;

use App\Models\Core\PagePermission;
use App\Models\Core\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinAccessRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('FIN');
            $table->string('position')->default('staff');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('page_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('module');
            $table->string('page');
            $table->string('permission_level')->nullable();
            $table->timestamps();
        });
        Schema::create('workforce_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->timestamps();
        });
        Schema::create('user_module_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('module');
            $table->timestamps();
        });
    }

    private function makeFinStaff(): User
    {
        return User::create([
            'name' => 'FIN Staff',
            'email' => 'fin.staff@test.local',
            'password' => Hash::make('password123'),
            'role' => 'FIN',
            'position' => 'staff',
            'is_active' => true,
        ]);
    }

    public function test_fin_staff_can_open_granted_pages(): void
    {
        $staff = $this->makeFinStaff();
        PagePermission::create(['user_id' => $staff->id, 'module' => 'FIN', 'page' => 'dashboard', 'permission_level' => 'view']);
        PagePermission::create(['user_id' => $staff->id, 'module' => 'FIN', 'page' => 'receivables', 'permission_level' => 'view']);

        // Both granted pages open for staff (previously 403 via position:manager).
        $this->actingAs($staff)->get(route('fin.manager.dashboard'))->assertStatus(200);
        $this->actingAs($staff)->get(route('fin.manager.receivables'))->assertStatus(200);

        // Ungranted pages stay locked.
        $this->actingAs($staff)->get(route('fin.manager.payables'))->assertStatus(403);
    }

    public function test_dashboard_lands_on_first_granted_page_when_dashboard_not_granted(): void
    {
        $staff = $this->makeFinStaff();
        PagePermission::create(['user_id' => $staff->id, 'module' => 'FIN', 'page' => 'receivables', 'permission_level' => 'view']);
        PagePermission::create(['user_id' => $staff->id, 'module' => 'FIN', 'page' => 'payables', 'permission_level' => 'view']);

        // Native FIN staff landing (fin.employee.dashboard) would 403 without
        // a dashboard grant — the resolver must send them to receivables.
        $response = $this->actingAs($staff)->get(route('dashboard'));
        $response->assertRedirect(route('fin.manager.receivables'));
    }

    public function test_dashboard_shows_waiting_page_when_all_fin_pages_disabled(): void
    {
        $staff = $this->makeFinStaff();
        PagePermission::create(['user_id' => $staff->id, 'module' => 'FIN', 'page' => 'dashboard', 'permission_level' => 'disabled']);
        PagePermission::create(['user_id' => $staff->id, 'module' => 'FIN', 'page' => 'receivables', 'permission_level' => 'disabled']);

        $this->actingAs($staff)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/AwaitingAccess'));
    }

    public function test_stale_page_visit_redirects_to_waiting_page_when_all_disabled(): void
    {
        $staff = $this->makeFinStaff();
        PagePermission::create(['user_id' => $staff->id, 'module' => 'FIN', 'page' => 'dashboard', 'permission_level' => 'disabled']);
        PagePermission::create(['user_id' => $staff->id, 'module' => 'FIN', 'page' => 'receivables', 'permission_level' => 'disabled']);

        // Stale sidebar link / bookmark (e.g. open before IT disabled
        // everything) lands friendly instead of a raw 403.
        $this->actingAs($staff)->get(route('fin.manager.receivables'))
            ->assertRedirect(route('awaiting.access'));
    }

    public function test_forbidden_page_stays_403_when_user_holds_other_grants(): void
    {
        $staff = $this->makeFinStaff();
        PagePermission::create(['user_id' => $staff->id, 'module' => 'FIN', 'page' => 'dashboard', 'permission_level' => 'view']);
        PagePermission::create(['user_id' => $staff->id, 'module' => 'FIN', 'page' => 'receivables', 'permission_level' => 'view']);

        // Has access elsewhere → exact 403 is preserved (no redirect).
        $this->actingAs($staff)->get(route('fin.manager.payables'))->assertStatus(403);
    }
}
