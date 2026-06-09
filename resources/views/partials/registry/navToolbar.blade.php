@php
    $prefix = 'admin.' . $registry;
    $certificationEnabled = ! in_array($registry, ['confirmation', 'burial'], true)
        && ! empty($showCertification ?? true);
    $sections = [
        'schedule' => [
            'label' => 'Schedule Request',
            'icon' => 'fa-calendar-days',
            'route' => $prefix . '.schedule',
            'style' => 'outline',
        ],
        'certification' => [
            'label' => 'Certification',
            'icon' => 'fa-certificate',
            'route' => $prefix . '.certification',
            'style' => 'outline',
            'hidden' => ! $certificationEnabled,
        ],
        'payment' => [
            'label' => 'Payment Fee',
            'icon' => 'fa-money-bill-wave',
            'route' => $prefix . '.payment',
            'style' => 'outline',
        ],
        'application' => [
            'label' => 'Application Form',
            'icon' => 'fa-file-lines',
            'route' => $prefix . '.application',
            'style' => 'outline',
        ],
    ];
@endphp

<div class="sappc-registry-toolbar" role="toolbar" aria-label="{{ ucfirst($registry) }} navigation">
    <span class="sappc-registry-toolbar_record">RECORD</span>
    <div class="sappc-registry-toolbar_actions">
        <button type="button"
            class="sappc-registry-toolbar_btn sappc-registry-toolbar_btn--reload"
            id="{{ $registry }}ReloadBtn" title="Reload" aria-label="Reload table">
            <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
            Reload
        </button>

        @foreach ($sections as $key => $section)
            @continue(!empty($section['hidden']))
            @php
                $isActive = $activeSection === $key;
                $btnClass = 'sappc-registry-toolbar_btn sappc-registry-toolbar_btn--' . $section['style'];
                if ($isActive) {
                    $btnClass .= ' is-active';
                }
            @endphp
            <a href="{{ route($section['route']) }}"
                class="{{ $btnClass }}"
                data-workflow-step="{{ $key }}"
                @if ($isActive) aria-current="page" @endif>
                <i class="fa-solid {{ $section['icon'] }}" aria-hidden="true"></i>
                {{ $section['label'] }}
            </a>
        @endforeach
    </div>
</div>
