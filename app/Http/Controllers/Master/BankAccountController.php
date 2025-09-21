<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BankAccountController extends Controller
{
    /**
     * Display a listing of the bank accounts.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $bankAccounts = BankAccount::orderBy('bank_name')->get();
        return view('master.bank_accounts.index', compact('bankAccounts'));
    }

    /**
     * Show the form for creating a new bank account.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('master.bank_accounts.create');
    }

    /**
     * Store a newly created bank account in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // If this is set as active, deactivate all others
        if ($request->has('is_active') && $request->is_active) {
            DB::transaction(function () use ($request) {
                // Deactivate all existing accounts
                BankAccount::where('is_active', true)->update(['is_active' => false]);
                
                // Create the new account with active status
                BankAccount::create([
                    'bank_name' => $request->bank_name,
                    'account_number' => $request->account_number,
                    'account_name' => $request->account_name,
                    'description' => $request->description,
                    'is_active' => true
                ]);
            });
        } else {
            // Create the account without changing active status
            BankAccount::create([
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'account_name' => $request->account_name,
                'description' => $request->description,
                'is_active' => false
            ]);
        }

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Rekening bank berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified bank account.
     *
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\Http\Response
     */
    public function edit(BankAccount $bankAccount)
    {
        return view('master.bank_accounts.edit', compact('bankAccount'));
    }

    /**
     * Update the specified bank account in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BankAccount $bankAccount)
    {
        $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Check if the is_active status is being changed
        $isBeingActivated = $request->has('is_active') && $request->is_active && !$bankAccount->is_active;

        DB::transaction(function () use ($request, $bankAccount, $isBeingActivated) {
            // If being activated, deactivate all others
            if ($isBeingActivated) {
                BankAccount::where('id', '!=', $bankAccount->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            // Update the account
            $bankAccount->update([
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'account_name' => $request->account_name,
                'description' => $request->description,
                'is_active' => $request->has('is_active') ? $request->is_active : false,
            ]);
        });

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Rekening bank berhasil diperbarui.');
    }

    /**
     * Remove the specified bank account from storage.
     *
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\Http\Response
     */
    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();
        
        return redirect()->route('bank-accounts.index')
            ->with('success', 'Rekening bank berhasil dihapus.');
    }

    /**
     * Set the specified bank account as active and deactivate all others.
     *
     * @param  \App\Models\BankAccount  $bankAccount
     * @return \Illuminate\Http\Response
     */
    public function setActive(BankAccount $bankAccount)
    {
        DB::transaction(function () use ($bankAccount) {
            // Deactivate all accounts
            BankAccount::where('is_active', true)->update(['is_active' => false]);
            
            // Activate the selected account
            $bankAccount->update(['is_active' => true]);
        });
        
        return redirect()->route('bank-accounts.index')
            ->with('success', 'Rekening bank "' . $bankAccount->bank_name . ' - ' . $bankAccount->account_number . '" telah diatur sebagai rekening aktif.');
    }
}
