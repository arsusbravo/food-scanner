<?php

test('the home page explains the product in plain language', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('See what food waste costs your kitchen')
        ->assertSee('How it works')
        ->assertSee('<meta property="og:description" content="Snap a photo of your food waste.', false)
        ->assertDontSee('offline-first')
        ->assertDontSee('HORECA');
});

test('every locale defines the same welcome keys', function () {
    $expected = array_keys(require lang_path('en/welcome.php'));

    foreach (['nl', 'de', 'fr', 'es', 'zh-TW', 'zh-CN', 'tr', 'id'] as $locale) {
        expect(array_keys(require lang_path("{$locale}/welcome.php")))
            ->toEqual($expected, "Locale {$locale} is missing or has extra welcome keys");
    }
});

test('the home page renders in another language without raw keys', function () {
    $this->withUnencryptedCookie('locale', 'nl')->get('/')
        ->assertOk()
        ->assertSee('Zie wat voedselverspilling uw keuken kost')
        ->assertDontSee('welcome.headline');
});

test('the home page embeds the intro video', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('https://www.youtube-nocookie.com/embed/lwP7ihOz3GA', false);
});

test('Indonesian is supported: pages render and the public translations are complete', function () {
    $this->withUnencryptedCookie('locale', 'id')->get('/')
        ->assertOk()
        ->assertSee('Lihat berapa biaya limbah makanan di dapur Anda')
        ->assertDontSee('welcome.headline');

    foreach (['docs', 'faq', 'prices'] as $file) {
        expect(array_keys(require lang_path("id/{$file}.php")))
            ->toEqual(array_keys(require lang_path("en/{$file}.php")), "id/{$file}.php differs from English");
    }
});
