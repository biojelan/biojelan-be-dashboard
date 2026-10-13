<?php

namespace App\Http\Controllers\Kilang;

use App\Actions\Kilang\FactoryInventory;
use App\Actions\Kilang\FinancialReport;
use App\Actions\Kilang\FinancialReportPdf;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kilang\SaveFinanceEntryRequest;
use App\Models\Factory;
use App\Models\FinanceEntry;
use App\Models\Price;
use App\Models\TransactionAgent;
use App\Models\TransactionClient;
use App\Models\User;
use App\PriceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class FinanceController extends Controller
{
    public function index(Request $request, FinancialReport $report, FactoryInventory $inventory): Response
    {
        [$from, $to] = $this->period($request);
        /** @var User $user */ $user = $request->user();
        $factory = $user->admin->factory;
        $filters = $request->validate(['status' => ['nullable', Rule::in(['PENDING', 'ACCEPTED', 'REJECTED', 'CANCEL_REQUESTED', 'CANCELLED'])]]);
        $clients = TransactionClient::query()->with(['client:id,name', 'agent:id,name', 'price'])->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))->latest('id')->paginate(15, ['*'], 'clients_page')->withQueryString();
        $agents = TransactionAgent::query()->with(['driver:id,name', 'agent:id,name', 'price', 'pickup'])->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))->latest('id')->paginate(15, ['*'], 'transactions_page')->withQueryString();
        $entries = FinanceEntry::query()->where('factory_id', $factory->factory_id)->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)->latest('date')->latest('id')->paginate(15, ['*'], 'entries_page')->withQueryString();
        $prices = [];
        foreach (PriceType::cases() as $type) {
            $prices[] = Price::query()->where('price_type', $type)->whereDate('start_date', '<=', today())
                ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', today()))->latest('start_date')->latest('price_id')->first();
        }

        return Inertia::render('kilang/finance', [
            'report' => $report->totals($factory, $from, $to), 'allTimeReport' => $report->totals($factory, '2020-01-01', today()->toDateString()),
            'inventory' => $inventory->totals($factory), 'clientTransactions' => $clients, 'agentTransactions' => $agents, 'entries' => $entries, 'prices' => $prices,
            'filters' => ['from' => $from, 'to' => $to, 'status' => $filters['status'] ?? ''],
        ]);
    }

    public function store(SaveFinanceEntryRequest $request, FactoryInventory $inventory): RedirectResponse
    {
        /** @var User $user */ $user = $request->user();
        DB::transaction(function () use ($user, $request, $inventory): void {
            $factory = Factory::query()->lockForUpdate()->findOrFail($user->admin->factory_id);
            $data = $request->validated();
            if ($data['type'] === 'SALE' && (float) $data['volume_liter'] > $inventory->totals($factory)['stock_liter']) {
                throw ValidationException::withMessages(['volume_liter' => 'Volume penjualan melebihi stok Factory yang sudah diterima.']);
            }
            $data['volume_liter'] = $data['type'] === 'SALE' ? $data['volume_liter'] : null;
            FinanceEntry::query()->create([...$data, 'factory_id' => $factory->factory_id, 'created_by' => $user->id]);
        });

        return back()->with('success', 'Catatan keuangan berhasil disimpan.');
    }

    public function price(Request $request): RedirectResponse
    {
        $data = $request->validate(['price_type' => ['required', Rule::enum(PriceType::class)], 'price_per_liter' => ['required', 'numeric', 'min:0.01', 'max:9999999999', 'decimal:0,2']]);
        /** @var User $user */ $user = $request->user();
        DB::transaction(function () use ($data, $user): void {
            Factory::query()->whereKey($user->admin->factory_id)->lockForUpdate()->firstOrFail();
            Price::query()->where('price_type', $data['price_type'])->whereNull('end_date')->update(['end_date' => today()->toDateString()]);
            Price::query()->create([...$data, 'start_date' => today()->toDateString(), 'end_date' => null]);
        });

        return back()->with('success', 'Harga terbaru disimpan. Harga transaksi sebelumnya tetap tersimpan.');
    }

    public function pdf(Request $request, FinancialReport $report, FinancialReportPdf $pdf): HttpResponse
    {
        [$from, $to] = $this->period($request);
        /** @var User $user */ $user = $request->user();
        $factory = $user->admin->factory;
        $entries = FinanceEntry::query()->where('factory_id', $factory->factory_id)->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)->orderBy('date')->orderBy('id')->get();
        $bytes = $pdf->render($factory, $report->totals($factory, $from, $to), $entries);

        return response($bytes, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="biojelan-laporan-'.$from.'-'.$to.'.pdf"', 'Cache-Control' => 'private, no-store']);
    }

    /** @return array{string, string} */
    private function period(Request $request): array
    {
        $data = $request->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from', 'before_or_equal:today']]);
        $from = $data['from'] ?? today()->startOfMonth()->toDateString();
        $to = $data['to'] ?? today()->toDateString();
        if ($from > $to) {
            throw ValidationException::withMessages(['from' => 'Tanggal awal harus sebelum tanggal akhir.']);
        }

        return [$from, $to];
    }
}
