<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Farmer\Concerns\InteractsWithAccessibleFarmerBusiness;
use App\Models\AnimalCertificate;
use App\Models\AnimalCertificateTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnimalCertificateTemplateController extends Controller
{
    use InteractsWithAccessibleFarmerBusiness;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AnimalCertificateTemplate::class);

        $records = AnimalCertificateTemplate::query()
            ->whereIn('business_id', $this->accessibleBusinessIds($request))
            ->latest()
            ->paginate(20);

        return view('farmer.animal-certificates.templates.index', compact('records'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', AnimalCertificateTemplate::class);

        return view('farmer.animal-certificates.templates.create', [
            'businesses' => $this->accessibleBusinessIds($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', AnimalCertificateTemplate::class);

        $data = $request->validate([
            'business_id' => ['required', 'integer', 'exists:businesses,id'],
            'template_name' => ['required', 'string', 'max:255'],
            'certificate_type' => ['required', 'string', Rule::in(AnimalCertificate::TYPES)],
            'title_template' => ['required', 'string', 'max:255'],
            'header_note' => ['nullable', 'string', 'max:5000'],
            'footer_note' => ['nullable', 'string', 'max:5000'],
            'watermark_text' => ['nullable', 'string', 'max:120'],
            'is_default' => ['sometimes', 'boolean'],
            'status' => ['required', 'string', Rule::in(AnimalCertificateTemplate::STATUSES)],
        ]);

        abort_unless($this->accessibleBusinessIds($request)->contains((int) $data['business_id']), 403);
        $data['is_default'] = $request->boolean('is_default');
        $data['created_by'] = $request->user()->id;

        $template = AnimalCertificateTemplate::query()->create($data);

        return redirect()->route('farmer.certificates.templates.index')->with('status', __('Template saved.'));
    }
}
