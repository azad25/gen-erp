<?php

namespace App\Http\Controllers;

use App\Models\CompanyUser;
use App\Models\Invitation;
use App\Services\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Handles invitation acceptance flow for both existing and new users.
 */
class InvitationController extends Controller
{
    public function accept(Request $request, string $token): View|RedirectResponse
    {
        $invitation = Invitation::withoutGlobalScopes()
            ->where('token', $token)
            ->with('company')
            ->first();

        if (! $invitation) {
            abort(404, __('Invitation not found.'));
        }

        if ($invitation->accepted_at) {
            return redirect()->route('login')
                ->with('status', __('This invitation has already been accepted.'));
        }

        if ($invitation->isExpired()) {
            return view('invitations.expired', ['invitation' => $invitation]);
        }

        // If user is logged in, accept immediately
        if ($user = $request->user()) {
            $this->acceptInvitation($invitation, $user);

            return redirect('/app');
        }

        // Not logged in — redirect to register with invitation context
        return redirect()->route('register', [
            'invite' => $invitation->token,
            'email' => $invitation->email,
        ]);
    }

    /**
     * Accept the invitation: create CompanyUser and mark as accepted.
     */
    private function acceptInvitation(Invitation $invitation, \App\Models\User $user): void
    {
        // Check if already a member
        $alreadyMember = CompanyUser::withoutGlobalScopes()
            ->where('company_id', $invitation->company_id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $alreadyMember) {
            CompanyUser::create([
                'company_id' => $invitation->company_id,
                'user_id' => $user->id,
                'role' => $invitation->role,
                'is_owner' => false,
                'joined_at' => now(),
                'invited_by' => $invitation->invited_by,
                'is_active' => true,
            ]);
        }

        $invitation->update(['accepted_at' => now()]);

        CompanyContext::setActive($invitation->company);
        $user->update(['last_active_company_id' => $invitation->company_id]);
    }
}
