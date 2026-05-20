@extends('layouts.public')

@section('page_title')KitchenLog — {{ __('faq.title') }}@endsection
@section('meta_description', __('faq.subtitle'))

@php
    $categories = [
        ['key' => 'cat_app',  'qs' => range(1, 5)],
        ['key' => 'cat_lang', 'qs' => range(6, 8)],
        ['key' => 'cat_sub',  'qs' => range(9, 15)],
    ];
@endphp

@push('styles')
<style>
    .faq-answer {
        display: none;
        padding: 0 20px 16px;
        font-size: 14px;
        color: #4b5563;
        line-height: 1.65;
    }
    .faq-answer.open { display: block; }
    .faq-item { border-bottom: 1px solid #f1f5f9; }
    .faq-item:last-child { border-bottom: none; }
    .faq-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 16px 20px;
        background: transparent;
        border: none;
        text-align: left;
        font-size: 14px;
        font-weight: 600;
        color: #0f172a;
        line-height: 1.4;
    }
    .faq-btn:hover { background: #f8fafc; }
    .faq-icon {
        flex-shrink: 0;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ecfdf5;
        color: #059669;
        font-size: 16px;
        font-weight: 700;
        line-height: 1;
        transition: background 0.15s;
    }
    .faq-btn[aria-expanded='true'] .faq-icon {
        background: #059669;
        color: white;
    }
</style>
@endpush

@section('hero')
    <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; background: rgba(255,255,255,0.15); border-radius: 20px; margin-bottom: 20px; border: 1.5px solid rgba(255,255,255,0.25);">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
            <path d="M12 17h.01"/>
        </svg>
    </div>

    <p style="color: rgba(167,243,208,0.85); font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; margin: 0 0 8px;">
        KitchenLog
    </p>
    <h1 style="color: white; font-size: 32px; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 10px;">
        {{ __('faq.title') }}
    </h1>
    <p style="color: rgba(209,250,229,0.9); font-size: 16px; line-height: 1.5; margin: 0;">
        {{ __('faq.subtitle') }}
    </p>
@endsection

@section('content')

    <!-- FAQ sections -->
    <div style="max-width: 480px; margin: -28px auto 0; padding: 0 24px; position: relative; z-index: 10;">

        @foreach($categories as $cat)
        <div style="margin-bottom: 20px;">
            <p style="font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase; letter-spacing: 0.12em; margin: 0 0 10px; padding: 0 4px;">
                {{ __('faq.' . $cat['key']) }}
            </p>
            <div style="background: white; border-radius: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; overflow: hidden;">
                @foreach($cat['qs'] as $i)
                <div class="faq-item">
                    <button class="faq-btn" aria-expanded="false" onclick="toggleFaq(this)">
                        <span>{{ __('faq.q' . $i) }}</span>
                        <span class="faq-icon" aria-hidden="true">+</span>
                    </button>
                    <div class="faq-answer">
                        {{ __('faq.a' . $i) }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

    </div>

    <!-- Footer CTA -->
    <div style="max-width: 480px; margin: 12px auto 0; padding: 0 24px 56px;">
        <div style="background: linear-gradient(135deg, #064e3b, #059669); border-radius: 20px; padding: 28px 24px; text-align: center;">
            <h2 style="color: white; font-size: 20px; font-weight: 800; margin: 0 0 6px; letter-spacing: -0.01em;">
                {{ __('faq.cta_title') }}
            </h2>
            <p style="color: rgba(209,250,229,0.85); font-size: 14px; margin: 0 0 20px; line-height: 1.5;">
                {{ __('faq.cta_sub') }}
            </p>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="{{ route('login') }}" style="display: flex; align-items: center; justify-content: center; height: 48px; border-radius: 14px; background: rgba(255,255,255,0.15); color: white; font-size: 15px; font-weight: 700; border: 1px solid rgba(255,255,255,0.25);">
                    {{ __('faq.cta_login') }}
                </a>
            </div>
        </div>

        <div style="display: flex; gap: 16px; justify-content: center; margin-top: 24px;">
            <a href="{{ route('home') }}" style="font-size: 13px; color: #94a3b8; font-weight: 600;">← Home</a>
            <a href="{{ route('docs') }}" style="font-size: 13px; color: #94a3b8; font-weight: 600;">Docs</a>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function toggleFaq(btn) {
        const isOpen = btn.getAttribute('aria-expanded') === 'true';
        document.querySelectorAll('.faq-btn').forEach(function(b) {
            b.setAttribute('aria-expanded', 'false');
            b.querySelector('.faq-icon').textContent = '+';
            b.nextElementSibling.classList.remove('open');
        });
        if (!isOpen) {
            btn.setAttribute('aria-expanded', 'true');
            btn.querySelector('.faq-icon').textContent = '−';
            btn.nextElementSibling.classList.add('open');
        }
    }
</script>
@endpush
