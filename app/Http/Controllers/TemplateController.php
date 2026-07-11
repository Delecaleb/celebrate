<?php

namespace App\Http\Controllers;

use App\Models\Celebration;
use App\Models\CelebrationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TemplateController extends Controller
{
    /**
     * Apply a template to a celebration.
     * Only the celebration owner may call this.
     */
    public function apply(Request $request, Celebration $celebration)
    {
        abort_if(Auth::id() !== $celebration->user_id, 403);

        $hexRule = ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        $request->validate([
            'template_id' => ['required', 'exists:celebration_templates,id'],
            'custom_bg'   => $hexRule,
            'custom_text' => $hexRule,
        ]);

        $celebration->update([
            'template_id' => $request->template_id,
            'custom_bg'   => $request->custom_bg  ?: null,
            'custom_text' => $request->custom_text ?: null,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Remove a template (reset to default Classic look).
     */
    public function reset(Celebration $celebration)
    {
        abort_if(Auth::id() !== $celebration->user_id, 403);

        $celebration->update(['template_id' => null]);

        return response()->json(['success' => true]);
    }
}
