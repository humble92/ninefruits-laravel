<?php

namespace App\Livewire\Portal;

use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Services\AuthorizationService;
use App\Domain\Security\Services\PersonaResolver;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.portal')]
class Dashboard extends Component
{
    public function render(AuthorizationService $authorization, PersonaResolver $personas): View
    {
        /** @var User $user */
        $user = Auth::user();

        return view('livewire.portal.dashboard', [
            'user' => $user,
            'roles' => $authorization->activeRoleNames($user),
            'personas' => $personas->personasFor($user),
            'canAccessAdmin' => $user->can(Permission::AdminPanelAccess->value),
        ])->title(__('portal.dashboard.title'));
    }
}
