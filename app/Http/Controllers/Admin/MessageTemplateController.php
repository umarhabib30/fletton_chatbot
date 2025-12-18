<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use Illuminate\Http\Request;

class MessageTemplateController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Templates',
            'active' => 'templates',
            'templates' => MessageTemplate::all(),
        ];

        return view('admin.templates.index', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Templates',
            'active' => 'templates',
        ];

        return view('admin.templates.create', $data);
    }

    public function store(Request $request)
    {
        if ($request->is_default == 1) {
            $template = MessageTemplate::where('is_default', true)->first();
            if ($template) {
                $template->update(['is_default' => false]);
            }
        }

        if($request->used_for != 'other') {
            if (MessageTemplate::where('used_for', $request->used_for)->exists()) {
                return redirect()->back()->with('error', "Template for {$request->used_for} already exists");
            }
        }

        MessageTemplate::create([
            'title' => $request->title,
            'template_id' => $request->template_id,
            'is_default' => $request->is_default ?? 0,
            'used_for' => $request->used_for,
        ]);

        return redirect()->back()->with('success', 'Template added successfully');
    }

    public function edit(string $id)
    {
        $template = MessageTemplate::find($id);
        $data = [
            'title' => 'Templates',
            'active' => 'templates',
            'template' => $template,
        ];
        return view('admin.templates.edit', $data);
    }

    public function update(Request $request)
    {
        $template = MessageTemplate::findOrFail($request->id);

        if ($request->is_default == 1) {
            MessageTemplate::where('is_default', true)
                ->where('id', '!=', $request->id)
                ->update(['is_default' => false]);
        }

        if (MessageTemplate::where('used_for', $request->used_for)
                ->where('id', '!=', $request->id)
                ->exists()) {
            return redirect()->back()->with('error', "Template for {$request->used_for} already exists");
        }

        $template->update([
            'title' => $request->title,
            'template_id' => $request->template_id,
            'is_default' => $request->is_default ?? 0,
            'used_for' => $request->used_for,
        ]);

        return redirect('admin/templates/index')->with('success', 'Template updated successfully');
    }

    public function delete(string $id)
    {
        $template = MessageTemplate::find($id);
        $template->delete();
        return redirect()->back()->with('success', 'Template deleted successfully');
    }
}
