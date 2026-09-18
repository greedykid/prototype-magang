<?php

namespace App\Http\Controllers;

use App\Models\Accreditation;
use App\Models\Lpk;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccreditationController extends Controller
{
    public function index(Request $request): View
    {
        $lpkId = $request->integer('lpk_id') ?: null;
        $status = $request->string('status')->toString();
        $startFrom = $request->date('start_from')?->format('Y-m-d');
        $startTo = $request->date('start_to')?->format('Y-m-d');
        $targetFrom = $request->date('target_from')?->format('Y-m-d');
        $targetTo = $request->date('target_to')?->format('Y-m-d');
        $accreditations = Accreditation::query()->with('lpk')->when($lpkId, fn ($query) => $query->where('lpk_id', $lpkId))->when($status, fn ($query) => $query->where('status', $status))->when($startFrom, fn ($query) => $query->whereDate('start_date', '>=', $startFrom))->when($startTo, fn ($query) => $query->whereDate('start_date', '<=', $startTo))->when($targetFrom, fn ($query) => $query->whereDate('target_date', '>=', $targetFrom))->when($targetTo, fn ($query) => $query->whereDate('target_date', '<=', $targetTo))->latest()->paginate(10)->withQueryString();

        return view('accreditations.index', array_merge(['accreditations' => $accreditations, 'lpks' => Lpk::orderBy('name')->get(['id', 'name'])], compact('lpkId', 'status', 'startFrom', 'startTo', 'targetFrom', 'targetTo')));
    }

    public function show(Accreditation $accreditation): View
    {
        return view('accreditations.show', ['accreditation' => $accreditation->load('lpk')]);
    }
}
