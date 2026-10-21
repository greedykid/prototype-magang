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
                'schedules' => [],
                'accounts' => [],
                'total' => 0,
                'view_all_url' => route('lpks.index'),
                'view_all_label' => 'Lihat semua hasil pencarian LPK',
            ]);
        }

        $user = $request->user();
        $isPic = $user?->isPic();
        $isAdmin = $user?->isAdmin();

        $assessmentTerms = Assessment::expandSearchTerms($q);

        // 1. Search LPK (Direct attributes + related assessments)
        $lpks = Lpk::query()
            ->with(['assessments'])
            ->primaryFor($user)
            ->where(function ($query) use ($q, $assessmentTerms) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('registration_number', 'like', "%{$q}%")
                    ->orWhere('no_reg', 'like', "%{$q}%")
                    ->orWhere('accreditation_number', 'like', "%{$q}%")
                    ->orWhere('accreditation_type', 'like', "%{$q}%")
                    ->orWhere('scope', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%")
                    ->orWhereHas('assessments', function ($aq) use ($assessmentTerms, $q) {
                        $aq->where(function ($sub) use ($assessmentTerms) {
                            foreach ($assessmentTerms as $term) {
                                $sub->orWhere('title', 'like', "%{$term}%")
                                    ->orWhere('assessment_type', 'like', "%{$term}%");
                            }
                        })
                        ->orWhere('sk_number', 'like', "%{$q}%")
                        ->orWhere('lead_assessor', 'like', "%{$q}%");
                    });
            })
            ->limit(5)
            ->get(['id', 'name', 'registration_number', 'no_reg', 'accreditation_number', 'accreditation_type', 'status', 'certificate_date', 'expired_at'])
            ->map(function ($lpk) {
                foreach ($lpk->assessments as $assessment) {
                    $assessment->setRelation('lpk', $lpk);
                }

                return [
                    'id' => $lpk->id,
                    'name' => $lpk->name,
                    'reg' => $lpk->accreditation_number ?: ($lpk->registration_number ?: '-'),
                    'type' => $lpk->accreditation_type ?: 'Laboratorium Terakreditasi',
                    'status' => $lpk->dynamic_status,
                    'url' => route('lpks.show', $lpk->id),
                ];
            });

        // 2. Search Assessments & Schedules in a single query
        $rawAssessments = Assessment::query()
            ->with(['lpk:id,name'])
            ->when($isPic, fn ($query) => $query->whereHas('lpk', fn ($lq) => $lq->primaryFor($user)))
            ->where(function ($query) use ($assessmentTerms, $q) {
                $query->where(function ($sub) use ($assessmentTerms) {
                    foreach ($assessmentTerms as $term) {
                        $sub->orWhere('title', 'like', "%{$term}%")
                            ->orWhere('assessment_type', 'like', "%{$term}%");
                    }
                })
                ->orWhere('sk_number', 'like', "%{$q}%")
                ->orWhere('lead_assessor', 'like', "%{$q}%")
                ->orWhere('location', 'like', "%{$q}%")
                ->orWhereHas('lpk', fn ($lq) => $lq->where('name', 'like', "%{$q}%")
                    ->orWhere('registration_number', 'like', "%{$q}%")
                    ->orWhere('no_reg', 'like', "%{$q}%")
                    ->orWhere('accreditation_number', 'like', "%{$q}%")
                );
            })
            ->orderBy('start_at', 'desc')
            ->limit(10)
            ->get();

        $assessments = $rawAssessments
            ->take(5)
            ->map(fn ($assessment) => [
                'id' => $assessment->id,
                'title' => $assessment->display_title,
                'type_label' => $assessment->assessment_type_label,
                'lpk_name' => $assessment->lpk?->name ?: '-',
                'date' => $assessment->start_at ? $assessment->start_at->translatedFormat('d M Y') : '-',
                'status' => $assessment->status,
                'url' => route('assessments.show', $assessment->id),
            ]);

        // 3. Search Schedules / Jadwal (Kalender)
        $schedules = $rawAssessments
            ->filter(fn ($assessment) => ! empty($assessment->start_at))
            ->take(5)
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
            ])
            ->values();

        // 4. Search Users / Accounts (Admin & PIC only)
        $accounts = collect();
        if ($isAdmin) {
            $accounts = User::query()
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                })
                ->limit(4)
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
                ->where('role', User::ROLE_PIC)
                ->when($user, fn ($query) => $query->where('id', '!=', $user->id))
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                })
                ->limit(4)
                ->get(['id', 'name', 'email', 'role'])
                ->map(fn ($account) => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'email' => $account->email,
                    'role_label' => $account->role_label,
                    'url' => route('account-links.index', ['search' => $account->email]),
                ]);
        }

        $viewAllLinks = [];
        if ($lpks->isNotEmpty()) {
            $viewAllLinks[] = [
                'type' => 'lpk',
                'label' => 'Laboratorium',
                'url' => route('lpks.index', ['search' => $q]),
                'count' => $lpks->count(),
            ];
        }
        if ($assessments->isNotEmpty()) {
            $viewAllLinks[] = [
                'type' => 'assessment',
                'label' => 'Asesmen',
                'url' => route('assessments.index', ['search' => $q]),
                'count' => $assessments->count(),
            ];
        }
        if ($schedules->isNotEmpty()) {
            $viewAllLinks[] = [
                'type' => 'calendar',
                'label' => 'Kalender',
                'url' => route('calendar.index', ['view' => 'month']),
                'count' => $schedules->count(),
            ];
        }
        if ($accounts->isNotEmpty()) {
            $viewAllLinks[] = [
                'type' => 'account',
                'label' => $isPic ? 'Tautan Akun' : 'Anggota',
                'url' => $isPic ? route('account-links.index', ['search' => $q]) : route('users.index', ['search' => $q]),
                'count' => $accounts->count(),
            ];
        }

        $total = $lpks->count() + $assessments->count() + $schedules->count() + $accounts->count();

        $viewAllUrl = route('lpks.index', ['search' => $q]);
        $viewAllLabel = 'Lihat semua hasil pencarian LPK';

        if ($lpks->isEmpty() && $assessments->isNotEmpty()) {
            $viewAllUrl = route('assessments.index', ['search' => $q]);
            $viewAllLabel = 'Lihat semua hasil pencarian Asesmen';
        } elseif ($lpks->isEmpty() && $assessments->isEmpty() && $accounts->isNotEmpty()) {
            $viewAllUrl = $isPic ? route('account-links.index', ['search' => $q]) : route('users.index', ['search' => $q]);
            $viewAllLabel = $isPic ? 'Lihat semua Tautan Akun' : 'Lihat semua Anggota';
        }

        return response()->json([
            'lpks' => $lpks,
            'assessments' => $assessments,
            'schedules' => $schedules,
            'accounts' => $accounts,
            'total' => $total,
            'view_all_url' => $viewAllUrl,
            'view_all_label' => $viewAllLabel,
            'view_all_links' => $viewAllLinks,
        ]);
    }
}
