<?php

namespace App\Http\Controllers;

use App\Services\PdfReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        return view('reports.index', [
            'periods'  => PdfReport::PERIODS,
            'hasAi'    => $request->user()->aiRecommendations()->exists(),
        ]);
    }

    public function download(Request $request)
    {
        $data = $request->validate([
            'period'  => ['required', Rule::in(array_keys(PdfReport::PERIODS))],
            'from'    => ['nullable', 'required_if:period,custom', 'date'],
            'to'      => ['nullable', 'required_if:period,custom', 'date', 'after_or_equal:from'],
            'details' => ['nullable', 'boolean'],
            'debts'   => ['nullable', 'boolean'],
            'ai'      => ['nullable', 'boolean'],
            'inline'  => ['nullable', 'boolean'],
        ], [
            'from.required_if' => 'Elegí la fecha de inicio del período.',
            'to.required_if'   => 'Elegí la fecha de fin del período.',
        ]);

        [$from, $to] = PdfReport::resolvePeriod($data['period'], $data['from'] ?? null, $data['to'] ?? null);

        if ($from->diffInDays($to) > 366 * 3) {
            throw ValidationException::withMessages(['to' => 'El período no puede superar los 3 años.']);
        }

        // Evita que una consulta muy pesada deje la página colgada
        @set_time_limit(120);

        $pdf = (new PdfReport($request->user()))->render($from, $to, [
            'details' => $request->boolean('details'),
            'debts'   => $request->boolean('debts'),
            'ai'      => $request->boolean('ai'),
        ]);

        $filename = PdfReport::filename($from, $to);
        $disposition = $request->boolean('inline') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }
}
