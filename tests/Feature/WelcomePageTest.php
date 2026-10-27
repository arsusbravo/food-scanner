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

    foreach (['nl', 'de', 'fr', 'es', 'zh-TW', 'zh-CN', 'tr'] as $locale) {
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
