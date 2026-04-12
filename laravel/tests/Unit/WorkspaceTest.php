<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_can_be_created()
    {
        $workspace = Workspace::factory()->create([
            'name' => 'Test Workspace',
        ]);

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'name' => 'Test Workspace',
        ]);
    }

    public function test_workspace_has_owner()
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'created_by_user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $workspace->owner);
        $this->assertEquals($user->id, $workspace->owner->id);
    }

    public function test_workspace_can_have_multiple_users()
    {
        $workspace = Workspace::factory()->create();
        $users = User::factory()->count(3)->create();

        $workspace->users()->attach($users);

        $this->assertCount(3, $workspace->users);
    }

    public function test_user_can_belong_to_multiple_workspaces()
    {
        $user = User::factory()->create();
        $workspaces = Workspace::factory()->count(2)->create();

        $user->workspaces()->attach($workspaces);

        $this->assertCount(2, $user->workspaces);
    }
}
