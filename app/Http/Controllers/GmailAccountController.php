<?php

namespace App\Http\Controllers;

use App\Models\GmailAccount;
use Illuminate\Support\Facades\Auth;

class GmailAccountController extends Controller
{
    public function index()
    {
        
        $accounts = GmailAccount::where('user_id', Auth::id())->get();
        return view('gmail-accounts.index', compact('accounts'));
    }

    public function destroy($id)
    {
        $account = GmailAccount::where('id', $id)
                               ->where('user_id', Auth::id())
                               ->firstOrFail();
        $account->delete();

        return redirect()->route('gmail-accounts.index')
                         ->with('success', '✅ Account removed successfully!');
    }

    public function toggleActive($id)
    {
        $account = GmailAccount::where('id', $id)
                               ->where('user_id', Auth::id())
                               ->firstOrFail();

        $account->update(['is_active' => !$account->is_active]);

        return redirect()->route('gmail-accounts.index')
                         ->with('success', '✅ Account status updated!');
    }

    public function updateLimit($id)
    {
        $account = GmailAccount::where('id', $id)
                               ->where('user_id', Auth::id())
                               ->firstOrFail();

        $account->update([
            'daily_limit' => request()->validate([
                'daily_limit' => 'required|integer|min:1|max:500'
            ])['daily_limit']
        ]);

        return redirect()->route('gmail-accounts.index')
                         ->with('success', '✅ Daily limit updated!');
    }
}