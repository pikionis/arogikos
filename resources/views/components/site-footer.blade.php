@php
    // Same editable labels as the header (shared.nav) — one source of truth,
    // so renaming a page in the admin updates both.
    $labels = content_items('shared.nav');

    $footerNav = collect(['home', 'about', 'services', 'blog.index', 'events', 'gallery'])
        ->map(fn (string $route, int $i) => [
            'route' => $route,
            'label' => $labels[$i]['label'] ?? '',
        ])->all();
@endphp

<footer id="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="footer-assembly footer-logo-assemble" id="footerLogo">
                    <x-logo-mark animated />
                    <span class="brand" style="margin-bottom:0">{{ setting('org_name', 'Αρωγή') }}</span>
                </div>
                <p style="margin-top:16px">{{ setting('footer_tagline') }}</p>
                <div class="socials">
                    @if (filled(setting('instagram')))
                        <a href="{{ setting('instagram') }}" aria-label="Instagram" target="_blank" rel="noopener">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1.1" fill="currentColor" stroke="none"/></svg>
                        </a>
                    @endif
                    @if (filled(setting('facebook')))
                        <a href="{{ setting('facebook') }}" aria-label="Facebook" target="_blank" rel="noopener">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-7h2.3l.4-2.8h-2.7V9.4c0-.8.2-1.4 1.4-1.4h1.5V5.6c-.3 0-1.1-.1-2.1-.1-2.1 0-3.6 1.3-3.6 3.7v2H8.2V14h2.5v7z"/></svg>
                        </a>
                    @endif
                </div>
            </div>

            <div class="footer-col">
                <h4 role="heading" aria-level="2">{{ content('shared.footer_nav_heading') }}</h4>
                @foreach ($footerNav as $item)
                    <a href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
                @endforeach
            </div>

            <div class="footer-col">
                <h4 role="heading" aria-level="2">{{ content('shared.footer_services_heading') }}</h4>
                @foreach (content_items('shared.services') as $service)
                    <a href="{{ route('services') }}">{{ $service['name'] ?? '' }}</a>
                @endforeach
            </div>

            <div class="footer-col">
                <h4 role="heading" aria-level="2">{{ content('shared.footer_contact_heading') }}</h4>
                @if (filled(setting('phone')))
                    <a href="tel:{{ setting('phone_href', setting('phone')) }}">{{ setting('phone') }}</a>
                @endif
                @if (filled(setting('email')))
                    <a href="mailto:{{ setting('email') }}">{{ setting('email') }}</a>
                @endif
                @if (filled(setting('address')))
                    <p><x-site-address /></p>
                @endif
                @if (filled(setting('hours')))
                    <p>{{ setting('hours') }}</p>
                @endif
            </div>
        </div>

        <div class="footer-bottom">
            <span>© {{ date('Y') }} {{ setting('org_name', 'Αρωγή') }} · {{ content('shared.footer_copyright_suffix') }}</span>
            <span>{{ content('shared.footer_credit') }}</span>
        </div>
    </div>
</footer>
