<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Church;
use App\Models\FinancialCategory;
use App\Models\Fund;
use App\Models\OnlineOffering;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicOfferingController extends Controller
{
    /**
     * Halaman publik persembahan digital / donasi gereja.
     */
    public function index(?string $churchCode = null): View
    {
        $churches = Church::all();

        $selectedChurch = null;
        if ($churchCode) {
            $selectedChurch = Church::where('code', $churchCode)->first();
        }

        if (! $selectedChurch) {
            $selectedChurch = $churches->first();
        }

        $funds = $selectedChurch
            ? Fund::where('church_id', $selectedChurch->id)->get()
            : collect();

        $categories = $selectedChurch
            ? FinancialCategory::where('church_id', $selectedChurch->id)
                ->where('type', 'debit')
                ->get()
            : collect();

        return view('public.offering.index', compact('churches', 'selectedChurch', 'funds', 'categories'));
    }

    /**
     * Simpan persembahan online dari jemaat / publik.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'church_id' => ['required', 'exists:churches,id'],
            'fund_id' => ['required', 'exists:funds,id'],
            'financial_category_id' => ['required', 'exists:financial_categories,id'],
            'donor_name' => ['nullable', 'string', 'max:255'],
            'donor_phone' => ['nullable', 'string', 'max:50'],
            'donor_email' => ['nullable', 'email', 'max:255'],
            'amount' => ['required', 'integer', 'min:10000'],
            'payment_method' => ['required', 'in:qris,bank_transfer,va'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'prayer_notes' => ['nullable', 'string', 'max:1000'],
            'proof' => ['nullable', 'image', 'max:5120'], // max 5MB
        ]);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('offering-proofs', 'public');
        }

        $referenceCode = OnlineOffering::generateReferenceCode();

        $offering = OnlineOffering::create([
            'church_id' => $validated['church_id'],
            'member_id' => auth()->user()?->member_id ?? null,
            'fund_id' => $validated['fund_id'],
            'financial_category_id' => $validated['financial_category_id'],
            'donor_name' => filled($validated['donor_name'] ?? null) ? $validated['donor_name'] : 'Hamba Allah',
            'donor_phone' => $validated['donor_phone'] ?? null,
            'donor_email' => $validated['donor_email'] ?? null,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'bank_name' => $validated['bank_name'] ?? null,
            'reference_code' => $referenceCode,
            'proof_path' => $proofPath,
            'prayer_notes' => $validated['prayer_notes'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->back()->with('offering_success', [
            'reference_code' => $referenceCode,
            'amount' => $offering->amount,
            'donor_name' => $offering->donor_name,
        ]);
    }
}
