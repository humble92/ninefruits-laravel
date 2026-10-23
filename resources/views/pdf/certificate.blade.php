<x-pdf-layout :title="$content->title">
    <style>
        .cert-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16pt; }
        .cert-serial { font-size: 9pt; color: #444; }
        .cert-title { text-align: center; font-size: 22pt; letter-spacing: 0.2em; margin: 10pt 0 18pt; }
        .cert-facts { width: auto; margin: 0 auto 16pt; }
        .cert-facts th { width: 110pt; background: #f4f4f5; }
        .cert-body p { line-height: 1.7; margin: 0 0 10pt; text-align: justify; }
        .cert-photo { width: 85pt; height: 110pt; object-fit: cover; border: 1px solid #999; }
        .cert-grades td, .cert-grades th { font-size: 9pt; padding: 2pt 4pt; }
        .cert-term { margin: 10pt 0 2pt; font-weight: 600; font-size: 10pt; }
        .cert-sign { margin-top: 28pt; text-align: center; }
        .cert-sign .signer { font-size: 13pt; margin-top: 6pt; }
        .cert-footer { margin-top: 22pt; display: flex; justify-content: space-between; align-items: flex-end; font-size: 8.5pt; color: #444; }
        .cert-footer svg { width: 70pt; height: 70pt; }
    </style>
    <div class="cert-header">
        <div>
            <div style="font-size: 14pt; font-weight: 600;">{{ $content->institution }}</div>
            <div class="cert-serial">{{ __('certificate.pdf.serial') }}: {{ $content->serialNumber }}</div>
        </div>
        @if ($content->photoDataUri)
            <img class="cert-photo" src="{{ $content->photoDataUri }}" alt="">
        @endif
    </div>

    <h1 class="cert-title">{{ $content->title }}</h1>

    <table class="cert-facts">
        @foreach ($content->facts as $label => $value)
            <tr><th>{{ $label }}</th><td>{{ $value }}</td></tr>
        @endforeach
    </table>

    <div class="cert-body">
        @foreach ($content->paragraphs as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach
    </div>

    @if ($content->terms !== null)
        <table class="cert-grades">
            <thead>
                <tr>
                    <th>{{ __('curriculum.fields.code') }}</th>
                    <th>{{ __('curriculum.fields.title') }}</th>
                    <th>{{ __('curriculum.fields.credits') }}</th>
                    <th>{{ __('grading.fields.grade') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($content->terms as $term)
                    <tr><td colspan="4" class="cert-term">{{ $term['term']->name }}
                        @if ($term['record']) · {{ __('grading.grades.term_summary', ['gpa' => $term['record']->term_gpa ?? '-', 'earned' => (float) $term['record']->earned_credits, 'attempted' => (float) $term['record']->attempted_credits]) }}@endif
                    </td></tr>
                    @foreach ($term['enrollments'] as $enrollment)
                        <tr>
                            <td>{{ $enrollment->course->code }}</td>
                            <td>{{ $enrollment->version->title }}</td>
                            <td>{{ (float) $enrollment->credits }}</td>
                            <td>{{ $enrollment->displayGrade() }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
        <p style="margin-top: 8pt;">
            <strong>{{ __('grading.fields.cumulative_gpa') }}</strong> {{ $content->cumulativeGpa ?? '-' }}
            · <strong>{{ __('grading.fields.earned_credits') }}</strong> {{ $content->earnedCredits ?? '0' }}
        </p>
    @endif

    <div class="cert-sign">
        <div>{{ $content->issuedOn }}</div>
        @if ($content->signer)
            <div class="signer">{{ $content->signer }}</div>
        @endif
    </div>

    <div class="cert-footer">
        <div>
            @if ($content->footer)<div>{{ $content->footer }}</div>@endif
            <div>{{ __('certificate.pdf.verify', ['url' => $content->verificationUrl]) }}</div>
        </div>
        <div>{!! $content->qrSvg !!}</div>
    </div>
</x-pdf-layout>
