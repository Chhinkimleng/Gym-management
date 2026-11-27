<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GymManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Owner',
            'email' => 'admin@test.gym',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'password' => Hash::make('password123'),
        ]);

        $this->staff = User::create([
            'name' => 'Front Desk Staff',
            'email' => 'staff@test.gym',
            'role' => User::ROLE_STAFF,
            'is_active' => true,
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_guest_is_redirected_to_login_and_inactive_user_cannot_access(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');

        $inactive = User::create([
            'name' => 'Inactive Staff',
            'email' => 'inactive@test.gym',
            'role' => User::ROLE_STAFF,
            'is_active' => false,
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($inactive)
            ->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_role_permissions_are_enforced_on_backend(): void
    {
        // Staff cannot create plans, access backup, or create users
        $this->actingAs($this->staff)
            ->get('/membership-plans/create')
            ->assertStatus(403);

        $this->actingAs($this->staff)
            ->get('/settings/backup')
            ->assertStatus(403);

        $this->actingAs($this->staff)
            ->post('/settings/users', [
                'name' => 'Hacker',
                'email' => 'h@test.gym',
                'role' => 'admin',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertStatus(403);

        // Admin can access plan creation and backup
        $this->actingAs($this->admin)
            ->get('/membership-plans/create')
            ->assertStatus(200);

        $this->actingAs($this->admin)
            ->get('/settings/backup')
            ->assertStatus(200);
    }

    public function test_member_crud_search_filter_and_deactivate(): void
    {
        $this->actingAs($this->staff)
            ->post('/members', [
                'name' => 'Maya Lin',
                'email' => 'maya@example.com',
                'phone' => '+15551234',
                'date_of_birth' => '1995-04-12',
                'gender' => 'female',
                'joining_date' => now()->toDateString(),
                'emergency_contact_name' => 'Alex Lin',
                'emergency_contact_phone' => '+15559999',
            ])
            ->assertRedirect();

        $member = Member::where('email', 'maya@example.com')->firstOrFail();
        $this->assertStringStartsWith('GYM-', $member->membership_id);

        $this->actingAs($this->staff)
            ->get('/members?search=Maya&status=active')
            ->assertStatus(200)
            ->assertSee('Maya Lin');

        $this->actingAs($this->staff)
            ->patch("/members/{$member->id}/deactivate")
            ->assertRedirect();

        $this->assertEquals('inactive', $member->fresh()->status);
    }

    public function test_plan_price_update_preserves_historical_membership_and_prevents_referenced_delete(): void
    {
        $this->actingAs($this->admin)
            ->post('/membership-plans', [
                'name' => 'Pro Monthly',
                'billing_cycle' => 'monthly',
                'price' => 60.00,
                'duration_months' => 1,
                'benefits_text' => "24/7 Access\nSauna",
                'is_active' => '1',
            ])
            ->assertRedirect('/membership-plans');

        $plan = MembershipPlan::where('name', 'Pro Monthly')->firstOrFail();

        $member = Member::create([
            'membership_id' => 'GYM-TEST-001',
            'name' => 'Devon Brooks',
            'email' => 'devon@example.com',
            'phone' => '555-0001',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($this->staff)
            ->post('/subscriptions', [
                'member_id' => $member->id,
                'membership_plan_id' => $plan->id,
                'start_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $sub = Subscription::where('member_id', $member->id)->firstOrFail();
        $this->assertEquals(60.00, (float) $sub->effectivePrice());

        // Update plan price later
        $this->actingAs($this->admin)
            ->put("/membership-plans/{$plan->id}", [
                'name' => 'Pro Monthly',
                'billing_cycle' => 'monthly',
                'price' => 95.00,
                'duration_months' => 1,
                'is_active' => '1',
            ])
            ->assertRedirect('/membership-plans');

        // Historical membership still has 60.00 snapshot
        $this->assertEquals(60.00, (float) $sub->fresh()->effectivePrice());

        // Cannot delete referenced plan
        $this->actingAs($this->admin)
            ->delete("/membership-plans/{$plan->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('membership_plans', ['id' => $plan->id, 'deleted_at' => null]);
    }

    public function test_partial_payment_installment_refund_and_pdf_receipt(): void
    {
        $plan = MembershipPlan::create([
            'name' => 'Quarterly',
            'billing_cycle' => 'quarterly',
            'price' => 150.00,
            'duration_months' => 3,
            'is_active' => true,
        ]);

        $member = Member::create([
            'membership_id' => 'GYM-TEST-002',
            'name' => 'Elena Rostova',
            'email' => 'elena@example.com',
            'phone' => '555-0002',
            'date_of_birth' => '1992-03-03',
            'gender' => 'female',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $sub = Subscription::create([
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'plan_price' => $plan->price,
            'duration_months' => 3,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'status' => 'active',
        ]);

        // Record partial payment of 90.00
        $this->actingAs($this->staff)
            ->post('/payments', [
                'member_id' => $member->id,
                'subscription_id' => $sub->id,
                'total_amount' => 150.00,
                'amount_paid' => 90.00,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $payment1 = Payment::firstOrFail();
        $this->assertEquals('partially_paid', $payment1->status);
        $this->assertEquals(60.00, (float) $payment1->remaining_balance);
        $this->assertEquals(60.00, $sub->fresh()->remainingBalance());

        // Record installment for remaining 60.00
        $this->actingAs($this->staff)
            ->post("/payments/{$payment1->id}/installment", [
                'amount_paid' => 60.00,
                'payment_date' => now()->toDateString(),
                'payment_method' => 'bank_transfer',
            ])
            ->assertRedirect();

        $this->assertEquals(90.00, (float) $payment1->fresh()->amount_paid);
        $this->assertEquals(0.00, (float) $payment1->fresh()->remaining_balance);
        $this->assertEquals(0.00, $sub->fresh()->remainingBalance());

        // PDF Receipt downloads cleanly
        $this->actingAs($this->staff)
            ->get("/payments/{$payment1->id}/receipt")
            ->assertStatus(200);

        // Staff cannot refund; Admin can
        $this->actingAs($this->staff)
            ->post("/payments/{$payment1->id}/refund", [
                'refund_amount' => 90.00,
                'refund_reason' => 'Test',
            ])
            ->assertStatus(403);

        $this->actingAs($this->admin)
            ->post("/payments/{$payment1->id}/refund", [
                'refund_amount' => 90.00,
                'refund_reason' => 'Customer requested cancellation',
            ])
            ->assertRedirect();

        $this->assertEquals('refunded', $payment1->fresh()->status);
        $this->assertEquals(90.00, (float) $payment1->fresh()->refunded_amount);
    }

    public function test_attendance_eligibility_and_duplicate_checkin_prevention(): void
    {
        $member = Member::create([
            'membership_id' => 'GYM-TEST-003',
            'name' => 'Liam OConnor',
            'email' => 'liam@example.com',
            'phone' => '555-0003',
            'date_of_birth' => '1994-06-15',
            'gender' => 'male',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        // Without active subscription, check-in is blocked
        $this->actingAs($this->staff)
            ->post('/attendance/check-in', ['member_id' => $member->id])
            ->assertSessionHas('error');

        $plan = MembershipPlan::create([
            'name' => 'Monthly',
            'billing_cycle' => 'monthly',
            'price' => 50.00,
            'duration_months' => 1,
            'is_active' => true,
        ]);

        Subscription::create([
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'plan_price' => $plan->price,
            'duration_months' => 1,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);

        // Eligible check-in succeeds
        $this->actingAs($this->staff)
            ->post('/attendance/check-in', ['member_id' => $member->id])
            ->assertSessionHas('success');

        // Duplicate active check-in is blocked
        $this->actingAs($this->staff)
            ->post('/attendance/check-in', ['member_id' => $member->id])
            ->assertSessionHas('error');

        $att = Attendance::where('member_id', $member->id)->firstOrFail();
        $this->actingAs($this->staff)
            ->patch("/attendance/{$att->id}/check-out")
            ->assertSessionHas('success');

        $this->assertNotNull($att->fresh()->check_out);
    }

    public function test_dashboard_and_reports_endpoints_render_and_export(): void
    {
        $this->actingAs($this->admin)->get('/dashboard')->assertStatus(200);
        $this->actingAs($this->admin)->get('/memberships')->assertStatus(200);
        $this->actingAs($this->admin)->get('/reports')->assertStatus(200);
        $this->actingAs($this->admin)->get('/reports/export/csv')->assertStatus(200);
        $this->actingAs($this->admin)->get('/reports/export/pdf')->assertStatus(200);
        $this->actingAs($this->admin)->get('/settings')->assertStatus(200);
    }

    public function test_khmer_locale_is_default_and_language_switcher_works(): void
    {
        $this->actingAs($this->admin)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('ផ្ទាំងគ្រប់គ្រង');

        $this->actingAs($this->admin)
            ->get('/locale/en')
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('Monthly Revenue Trend');

        $this->actingAs($this->admin)
            ->get('/locale/km')
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('ចំណូលប្រចាំខែ');
    }

    public function test_demo_login_buttons_work_for_admin_and_staff_roles(): void
    {
        $this->post('/demo-login', ['role' => 'admin'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->admin);
        $this->get('/settings/backup')->assertStatus(200);

        $this->post('/logout');

        $this->post('/demo-login', ['role' => 'staff'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->staff);
        $this->get('/settings/backup')->assertStatus(403);
    }
}
