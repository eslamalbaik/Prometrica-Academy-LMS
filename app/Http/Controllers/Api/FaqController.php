<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    private array $rules = [
        'question'    => 'required|string|max:500',
        'question_en' => 'nullable|string|max:500',
        'answer'      => 'required|string',
        'answer_en'   => 'nullable|string',
        'is_active'   => 'boolean',
        'sort'        => 'nullable|integer|min:0',
    ];

    /** GET /api/dashboard/faqs */
    public function index()
    {
        return response()->json(Faq::orderBy('sort')->orderBy('id')->get());
    }

    /** POST /api/dashboard/faqs */
    public function store(Request $request)
    {
        return response()->json(Faq::create($request->validate($this->rules)), 201);
    }

    /** PUT /api/dashboard/faqs/{faq} */
    public function update(Request $request, Faq $faq)
    {
        $faq->update($request->validate($this->rules));

        return response()->json($faq);
    }

    /** DELETE /api/dashboard/faqs/{faq} */
    public function destroy(Faq $faq)
    {
        $faq->delete();

        return response()->json(['message' => 'FAQ deleted']);
    }
}
