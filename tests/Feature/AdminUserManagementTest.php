<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    private function staffAdmin()
    {
        return DB::table('tbl_admin as a')
            ->join('admin_roles as r','r.id','=','a.role_id')
            ->where('a.is_active',1)
            ->where('r.permissions','like','%"staff"%')
            ->select('a.*')
            ->first();
    }

    public function testAdministratorAccountsAndRolesCanBeEdited()
    {
        $signedIn=$this->staffAdmin();
        if(!$signedIn)return $this->assertTrue(true);

        DB::beginTransaction();
        try {
            $roleId=DB::table('admin_roles')->insertGetId([
                'name'=>'Test role '.str_random(8),
                'permissions'=>json_encode(['dashboard']),
                'is_system'=>0,
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
            $adminId=DB::table('tbl_admin')->insertGetId([
                'admin_name'=>'edit_'.str_random(8),
                'full_name'=>'Before Edit',
                'admin_email'=>null,
                'role_id'=>$roleId,
                'is_active'=>1,
                'admin_password'=>Hash::make('OldPassword1'),
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
            $session=['admin_id'=>$signedIn->admin_id,'admin_name'=>$signedIn->admin_name];

            $this->withSession($session)->get('/admin-users')
                ->assertStatus(200)
                ->assertSee('Account information')
                ->assertSee('Confirm new password')
            ->assertSee('Allowed permissions (25 total)')
                ->assertSee('System role - permissions editable');

            $this->withSession($session)->post('/admin-roles/'.$roleId.'/update',[
                'name'=>'Support Editor '.str_random(6),
                'permissions'=>['dashboard','customers'],
            ])->assertSessionHas('message');
            $this->assertStringContainsString('customers',DB::table('admin_roles')->where('id',$roleId)->value('permissions'));

            $newUsername='edited_'.str_random(8);
            $this->withSession($session)->post('/admin-users/'.$adminId.'/update',[
                'admin_name'=>$newUsername,
                'full_name'=>'Edited Administrator',
                'admin_email'=>'edited-'.str_random(6).'@example.com',
                'role_id'=>$roleId,
            ])->assertSessionHas('message');
            $this->assertDatabaseHas('tbl_admin',['admin_id'=>$adminId,'admin_name'=>$newUsername,'full_name'=>'Edited Administrator']);

            $this->withSession($session)->post('/admin-users/'.$adminId.'/password',[
                'password'=>'NewPassword123',
                'password_confirmation'=>'NewPassword123',
            ])->assertSessionHas('message');
            $this->assertTrue(Hash::check('NewPassword123',DB::table('tbl_admin')->where('admin_id',$adminId)->value('admin_password')));
        } finally {
            DB::rollBack();
        }
    }

    public function testPasswordResetRequiresMatchingConfirmation()
    {
        $signedIn=$this->staffAdmin();
        if(!$signedIn)return $this->assertTrue(true);
        $this->withSession(['admin_id'=>$signedIn->admin_id,'admin_name'=>$signedIn->admin_name])
            ->from('/admin-users')
            ->post('/admin-users/'.$signedIn->admin_id.'/password',[
                'password'=>'NewPassword123',
                'password_confirmation'=>'DifferentPassword123',
            ])->assertRedirect('/admin-users')->assertSessionHasErrors('password');
    }

    public function testSuperAdminRoleIncludesTheProductCodePermissions()
    {
        $permissions = json_decode(DB::table('admin_roles')->where('name', 'Super Admin')->value('permissions'), true);
        $this->assertIsArray($permissions);
        $this->assertCount(25, $permissions);
        $this->assertContains('view_product_code_configuration', $permissions);
        $this->assertContains('change_product_code_configuration', $permissions);
        $this->assertContains('view_product_code_history', $permissions);
    }

    public function testSuperAdminPermissionsCanBeUpdated()
    {
        $role = DB::table('admin_roles')->where('name', 'Super Admin')->first();
        $this->assertNotNull($role);

        DB::beginTransaction();
        try {
            $editorRoleId = DB::table('admin_roles')->insertGetId([
                'name' => 'Role Editor '.str_random(8),
                'permissions' => json_encode(['dashboard', 'staff']),
                'is_system' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $editorAdminId = DB::table('tbl_admin')->insertGetId([
                'admin_name' => $editorName = 'editor_'.str_random(8),
                'full_name' => 'Role Editor',
                'admin_email' => null,
                'role_id' => $editorRoleId,
                'is_active' => 1,
                'admin_password' => Hash::make('EditorPassword123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $session = ['admin_id' => $editorAdminId, 'admin_name' => $editorName];

            $this->withSession($session)->get('/admin-users')->assertStatus(200);

            $this->withSession($session)->from('/admin-users')->post('/admin-roles/'.$role->id.'/update', [
                'name' => 'Super Admin',
                'permissions' => ['dashboard', 'catalog', 'inventory'],
            ])->assertRedirect('/admin-users');

            $permissions = json_decode(DB::table('admin_roles')->where('id', $role->id)->value('permissions'), true);
            $this->assertSame(['dashboard', 'catalog', 'inventory'], $permissions);

            $this->withSession($session)->from('/admin-users')->post('/admin-roles/'.$role->id.'/update', [
                'name' => 'Super Admin',
                'permissions' => [
                    'dashboard',
                    'catalog',
                    'inventory',
                    'view_product_code_configuration',
                    'change_product_code_configuration',
                ],
            ])->assertRedirect('/admin-users');

            $permissions = json_decode(DB::table('admin_roles')->where('id', $role->id)->value('permissions'), true);
            $this->assertContains('view_product_code_configuration', $permissions);
            $this->assertContains('change_product_code_configuration', $permissions);
            $this->assertNotContains('view_product_code_history', $permissions);
        } finally {
            DB::rollBack();
        }
    }
}
