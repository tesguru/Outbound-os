<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TemplateController extends Controller
{
    public function index()
    {
     
        $templates = Template::where('user_id', Auth::id())
                             ->orderBy('created_at', 'desc')
                             ->get();
        return view('templates.index', compact('templates'));
    }

    public function create()
    {
        return view('templates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'type'     => 'required|in:personal,company',
            'category' => 'required|in:initial,followup',
            'subject'  => 'required|string|max:255',
            'body'     => 'required|string',
        ]);

        Template::create([
            'user_id'   => Auth::id(),
            'name'      => $request->name,
            'type'      => $request->type,
            'category'  => $request->category,
            'subject'   => $request->subject,
            'body'      => $request->body,
            'has_price' => str_contains($request->body, '{{price}}'),
        ]);

        return redirect()->route('templates.index')
                         ->with('success', '✅ Template created successfully!');
    }

    public function edit($id)
    {
        $template = Template::where('id', $id)
                            ->where('user_id', Auth::id())
                            ->firstOrFail();
        return view('templates.edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $template = Template::where('id', $id)
                            ->where('user_id', Auth::id())
                            ->firstOrFail();

        $request->validate([
            'name'     => 'required|string|max:255',
            'type'     => 'required|in:personal,company',
            'category' => 'required|in:initial,followup',
            'subject'  => 'required|string|max:255',
            'body'     => 'required|string',
        ]);

        $template->update([
            'name'      => $request->name,
            'type'      => $request->type,
            'category'  => $request->category,
            'subject'   => $request->subject,
            'body'      => $request->body,
            'has_price' => str_contains($request->body, '{{price}}'),
        ]);

        return redirect()->route('templates.index')
                         ->with('success', '✅ Template updated successfully!');
    }

    public function destroy($id)
    {
        $template = Template::where('id', $id)
                            ->where('user_id', Auth::id())
                            ->firstOrFail();
        $template->delete();

        return redirect()->route('templates.index')
                         ->with('success', '✅ Template deleted!');
    }
}
