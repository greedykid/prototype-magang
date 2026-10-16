<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    /**
     * Handle live global search query for LPKs, Assessments, and Users.
     */
    public function search(Request $request): JsonResponse
    {
        $q = $request->string('q')->trim()->toString();

        if (mb_strlen($q) < 2) {
            return response()->json([
                'lpks' => [],
                'assessments' => [],
                'accounts' => [],
                'total' => 0,
                'view_all_url' => route('lpks.index'),
            ]);
        }

        $user = $request->user();
        $isPic = $user?->isPic();
        $isAdmin = $user?->isAdmin();

        // 1. Search LPK
        $lpks = Lpk::query()
            ->primaryFor($user)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('registration_number', 'like', "%{$q}%")
                    ->orWhere('no_reg', 'like', "%{$q}%")
                    ->orWhere('accreditation_number', 'like', "%{$q}%")
                    ->orWhere('accreditation_type', 'like', "%{$q}%");
            })
            ->limit(4)
            ->get(['id', 'name', 'registration_number', 'accreditation_number', 'accreditation_type', 'status'])
            ->map(fn ($lpk) => [
                'id' => $lpk->id,
                'name' => $lpk->name,
                'reg' => $lpk->accreditation_number ?: ($lpk->registration_number ?: '-'),
                'type' => $lpk->accreditation_type ?: 'Laboratorium Terakreditasi',
                'status' => $lpk->dynamic_status,
                'url' => route('lpks.show', $lpk->id),
            ]);

        // 2. Search Assessments
        $assessments = Assessment::query()
            ->with(['lpk:id,name'])
            ->when($isPic, fn ($query) => $query->whereHas('lpk', fn ($lq) => $lq->primaryFor($user)))
            ->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('assessment_type', 'like', "%{$q}%")
                    ->orWhereHas('lpk', fn ($lq) => $lq->where('name', 'like', "%{$q}%"));
            })
            ->orderBy('start_at', 'desc')
            ->limit(3)
            ->get()
            ->map(fn ($assessment) => [
                'id' => $assessment->id,
                'title' => $assessment->display_title,
                'type_label' => $assessment->assessment_type_label,
                'lpk_name' => $assessment->lpk?->name ?: '-',
                'date' => $assessment->start_at ? $assessment->start_at->translatedFormat('d M Y') : '-',
                'status' => $assessment->status,
                'url' => route('calendar.index', [
                    'view' => 'month',
                    'date' => $assessment->start_at?->toDateString(),
                    'highlight' => $assessment->id,
                    'selected' => 1,
                ]),
            ]);

        // 3. Search Users / Accounts (Admin & PIC only)
        $accounts = collect();
        if ($isAdmin) {
            $accounts = User::query()
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                })
                ->limit(3)
                ->get(['id', 'name', 'email', 'role'])
                ->map(fn ($account) => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'email' => $account->email,
                    'role_label' => $account->role_label,
                    'url' => route('users.index', ['search' => $account->email]),
                ]);
        } elseif ($isPic) {
            $accounts = User::query()
                ->where('role', 'LPK')
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                })
                ->limit(3)
                ->get(['id', 'name', 'email', 'role'])
                ->map(fn ($account) => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'email' => $account->email,
                    'role_label' => $account->role_label,
                    'url' => route('account-links.index', ['search' => $account->email]),
                ]);
        }

        $total = $lpks->count() + $assessments->count() + $accounts->count();

        return response()->json([
            'lpks' => $lpks,
            'assessments' => $assessments,
            'accounts' => $accounts,
            'total' => $total,
            'view_all_url' => route('lpks.index', ['search' => $q]),
        ]);
    }
}
