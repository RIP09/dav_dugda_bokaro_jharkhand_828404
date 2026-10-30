<section class="cta-band">
    <div class="wrap cta-in" data-reveal="zoom">
        <div class="cta-shapes" aria-hidden="true"><span></span><span></span><span></span></div>
        <div>
            <span class="eyebrow eyebrow-light">{{ !empty($s['admission_banner']) ? $s['admission_banner'] : 'Admissions' }}</span>
            <h2 class="h2">Give your child the DAV start in life</h2>
            <p>Send an admission enquiry online — it goes straight to the school office.</p>
        </div>
        <div class="cta-actions">
            <a class="btn btn-accent btn-lg" href="{{ route('website.admissions') }}#enquiry">Admission enquiry @icon('arrow-right','ic',18)</a>
            @if(!empty($s['phone']))<a class="btn btn-ghost-light btn-lg" href="tel:{{ preg_replace('/[^0-9+]/', '', $s['phone']) }}">@icon('phone','ic',18) Call the office</a>@endif
        </div>
    </div>
</section>
