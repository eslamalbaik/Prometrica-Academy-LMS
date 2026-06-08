<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PricingPlan;
use Illuminate\Http\Request;

/**
 * Admin CRUD for global platform pricing plans (the landing-page packages).
 * Features are a bilingual, grouped JSON matrix the admin edits freely.
 */
class PricingPlanController extends Controller
{
    private array $rules = [
        'name'           => 'required|string|max:255',
        'name_en'        => 'nullable|string|max:255',
        'description'    => 'nullable|string',
        'description_en' => 'nullable|string',
        'price'          => 'required|numeric|min:0',
        'period'         => 'nullable|string|max:100',
        'period_en'      => 'nullable|string|max:100',
        'badge'          => 'nullable|string|max:100',
        'badge_en'       => 'nullable|string|max:100',
        'is_featured'    => 'boolean',
        'is_active'      => 'boolean',
        'cta_label'      => 'nullable|string|max:100',
        'cta_label_en'   => 'nullable|string|max:100',
        'cta_url'        => 'nullable|string|max:255',
        'sort'           => 'nullable|integer|min:0',
        'color'          => 'nullable|string|max:50',
        'features'                 => 'nullable|array',
        'features.*.title'         => 'nullable|string',
        'features.*.title_en'      => 'nullable|string',
        'features.*.items'         => 'nullable|array',
        'features.*.items.*.text'     => 'nullable|string',
        'features.*.items.*.text_en'  => 'nullable|string',
        'features.*.items.*.included' => 'boolean',
    ];

    /** GET /api/dashboard/pricing-plans */
    public function index()
    {
        return response()->json(PricingPlan::orderBy('sort')->orderBy('id')->get());
    }

    /** POST /api/dashboard/pricing-plans */
    public function store(Request $request)
    {
        $plan = PricingPlan::create($request->validate($this->rules));

        return response()->json($plan, 201);
    }

    /** PUT /api/dashboard/pricing-plans/{plan} */
    public function update(Request $request, PricingPlan $plan)
    {
        $plan->update($request->validate($this->rules));

        return response()->json($plan);
    }

    /** DELETE /api/dashboard/pricing-plans/{plan} */
    public function destroy(PricingPlan $plan)
    {
        $plan->delete();

        return response()->json(['message' => 'Plan deleted']);
    }
}
