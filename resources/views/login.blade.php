@if($settings['login_welcome'])
    <aside class="aether-welcome" aria-label="Aether UI">
        <span class="aether-orbit" aria-hidden="true">{{ $settings['brand_name'] !== '' ? mb_substr($settings['brand_name'], 0, 1) : 'A' }}</span>
        <h2>{{ \Aether\UI\Localization::welcome($settings, 'title') }}</h2>
        <p>{{ \Aether\UI\Localization::welcome($settings, 'message') }}</p>
    </aside>
@endif
