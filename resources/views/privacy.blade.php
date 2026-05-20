@extends('layouts.public')

@section('page_title', __('privacy.page_title'))
@section('meta_description', __('privacy.meta_description'))

@section('hero')
    <img src="/images/doc/logo-dark.png" alt="KitchenLog" style="width: 56px; height: 56px; object-fit: contain; display: block; margin: 0 auto 16px;" />
    <p style="color: rgba(167,243,208,0.85); font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; margin: 0 0 8px;">{{ __('privacy.hero_eyebrow') }}</p>
    <h1 style="color: white; font-size: 32px; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 10px;">{{ __('privacy.hero_title') }}</h1>
    <p style="color: rgba(209,250,229,0.9); font-size: 14px; margin: 0;">{{ __('privacy.last_updated', ['date' => date('d F Y')]) }}</p>
@endsection

@section('content')
<div style="max-width: 680px; margin: 0 auto; padding: 40px 24px 64px;">

    @foreach (range(1, 10) as $i)
    <div style="margin-bottom: 32px;" class="legal-content">
        <h2 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0 0 10px;">{{ __('privacy.s' . $i . '_title') }}</h2>
        <div style="font-size: 14px; color: #374151; line-height: 1.75;">
            {!! __('privacy.s' . $i . '_body') !!}
        </div>
    </div>
    @endforeach

    <div style="border-top: 1px solid #e2e8f0; padding-top: 24px; display: flex; gap: 16px; flex-wrap: wrap;">
        <a href="{{ route('terms') }}" style="font-size: 13px; font-weight: 600; color: #059669;">{{ __('privacy.nav_terms') }}</a>
        <a href="{{ route('cookies') }}" style="font-size: 13px; font-weight: 600; color: #059669;">{{ __('privacy.nav_cookies') }}</a>
        <a href="{{ route('home') }}" style="font-size: 13px; font-weight: 600; color: #64748b;">{{ __('privacy.nav_home') }}</a>
    </div>

</div>

<style>
    .legal-content ul { padding-left: 20px; margin: 8px 0; }
    .legal-content li { margin-bottom: 6px; }
    .legal-content p  { margin: 0 0 10px; }
    .legal-content a  { color: #059669; }
</style>
@endsection
