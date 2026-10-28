<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create(['name' => 'A', 'username' => 'adm', 'password' => 'LamaPass123']);
    }

    public function test_guest_cannot_access_and_admin_sees_form_and_menu_link(): void
    {
        $this->get(route('admin.password.edit'))->assertRedirect(route('admin.login'));

        $this->actingAs($this->admin(), 'admin')->get(route('admin.password.edit'))
            ->assertOk()->assertSee('Password Saat Ini')->assertSee('Ganti Password');
    }

    public function test_changes_password_with_correct_current_password_and_is_audited(): void
    {
        $a = $this->admin();

        $this->actingAs($a, 'admin')->put(route('admin.password.update'), [
            'current_password' => 'LamaPass123', 'password' => 'BaruPass2026x', 'password_confirmation' => 'BaruPass2026x',
        ])->assertRedirect(route('admin.password.edit'))->assertSessionHas('success');

        $this->assertTrue(Hash::check('BaruPass2026x', $a->fresh()->password));
        $this->assertFalse(Hash::check('LamaPass123', $a->fresh()->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.password_changed']);
        $this->assertStringNotContainsString('BaruPass2026x', json_encode(AuditLog::all()->toArray()));
    }

    public function test_rejects_wrong_current_weak_mismatched_or_same_password(): void
    {
        $a = $this->admin();
        $cases = [
            'current_password' => ['current_password' => 'salah', 'password' => 'BaruPass2026x', 'password_confirmation' => 'BaruPass2026x'],
            'password' => ['current_password' => 'LamaPass123', 'password' => 'pendek1', 'password_confirmation' => 'pendek1'],
        ];
        foreach ($cases as $field => $payload) {
            $this->actingAs($a, 'admin')->put(route('admin.password.update'), $payload)->assertSessionHasErrors($field);
        }
        $this->actingAs($a, 'admin')->put(route('admin.password.update'), ['current_password' => 'LamaPass123', 'password' => 'BaruPass2026x', 'password_confirmation' => 'Beda'])->assertSessionHasErrors('password');
        $this->actingAs($a, 'admin')->put(route('admin.password.update'), ['current_password' => 'LamaPass123', 'password' => 'LamaPass123', 'password_confirmation' => 'LamaPass123'])->assertSessionHasErrors('password');
        $this->actingAs($a, 'admin')->put(route('admin.password.update'), ['current_password' => 'LamaPass123', 'password' => 'abcdefghijkl', 'password_confirmation' => 'abcdefghijkl'])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('LamaPass123', $a->fresh()->password));
    }
}
