<?php

namespace App\Http\Controllers\Sec;

use App\Models\Sec\SecretaryMemo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SecretaryMemoController extends SecretaryController
{
    public function index(Request $request)
    {
        $query = SecretaryMemo::with('creator')->latest();

        if ($search = $request->search) {
            $query->where(fn ($q) => $q
                ->where('ref_no', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%"));
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        return Inertia::render('Dashboard/SEC/Memos', [
            'memos' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'audience' => 'required|string|max:64',
            'priority' => 'required|in:normal,urgent',
        ]);

        SecretaryMemo::create([
            ...$validated,
            'ref_no' => $this->refNo('SEC-MEMO', SecretaryMemo::class),
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        return back()->with('message', 'Memo drafted successfully.');
    }

    public function publish(SecretaryMemo $memo)
    {
        $memo->update(['status' => 'published', 'published_at' => now()]);

        return back()->with('message', "Memo {$memo->ref_no} published.");
    }

    public function archive(SecretaryMemo $memo)
    {
        $memo->update(['status' => 'archived']);

        return back()->with('message', "Memo {$memo->ref_no} archived.");
    }

    public function destroy(SecretaryMemo $memo)
    {
        $memo->delete();

        return back()->with('message', 'Memo removed.');
    }
}
