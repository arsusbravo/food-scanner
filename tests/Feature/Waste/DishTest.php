<?php

use App\Models\DishDescription;
use App\Models\User;
use App\Support\AiVisionClient;
use App\Support\DishWriter;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['services.ai.provider' => 'anthropic', 'services.anthropic.key' => 'test-key']);
});

function dishPhoto(): string
{
    $image = imagecreatetruecolor(40, 40);
    ob_start();
    imagejpeg($image);

    return base64_encode((string) ob_get_clean());
}

function fakeDishAnswer(array $overrides = []): void
{
    Http::fake([
        'api.anthropic.com/*' => Http::response(['content' => [['text' => json_encode($overrides + [
            'dish_name' => 'Pad Thai',
            'origin' => 'Thailand',
            'title' => 'Street-Style Pad Thai',
            'description' => "Rice noodles wok-fried with tamarind and peanuts.\n\nA Thai street classic.",
            'confidence' => 'high',
        ])]]]),
    ]);
}

test('a dish photo becomes a saved title and description', function () {
    fakeDishAnswer();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('waste.dishes.store'), ['photo' => dishPhoto(), 'locale' => 'nl'])
        ->assertOk()
        ->assertJsonPath('title', 'Street-Style Pad Thai')
        ->assertJsonPath('origin', 'Thailand');

    $this->assertDatabaseHas('dish_descriptions', [
        'user_id' => $user->id,
        'dish_name' => 'Pad Thai',
        'locale' => 'nl',
    ]);

    Http::assertSent(fn ($request) => str_contains($request['system'], 'Dutch')
        && $request['model'] === config('services.anthropic.dish_model'));
});

test('the owner hints reach the model', function () {
    fakeDishAnswer();

    $this->actingAs(User::factory()->create())
        ->postJson(route('waste.dishes.store'), ['photo' => dishPhoto(), 'name' => 'Rendang', 'origin' => 'Padang'])
        ->assertOk();

    Http::assertSent(function ($request) {
        $text = collect($request['messages'][0]['content'])->firstWhere('type', 'text')['text'];

        return str_contains($text, 'Rendang') && str_contains($text, 'Padang');
    });
});

test('an unusable file or answer is rejected without using quota', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('waste.dishes.store'), ['photo' => base64_encode(str_repeat('x', 200))])
        ->assertStatus(422);

    Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['text' => 'sorry, no']]])]);

    $this->actingAs($user)
        ->postJson(route('waste.dishes.store'), ['photo' => dishPhoto()])
        ->assertStatus(422);

    expect($user->dishDescriptions()->count())->toBe(0);
});

test('the monthly limit blocks new dishes before calling the model', function () {
    Http::fake();
    config(['plans.free.dishes' => 1]);
    $user = User::factory()->create();
    DishDescription::factory()->for($user)->create();

    $this->actingAs($user)
        ->postJson(route('waste.dishes.store'), ['photo' => dishPhoto()])
        ->assertForbidden();

    Http::assertNothingSent();
});

test('only the owner can edit or delete a dish text', function () {
    $dish = DishDescription::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($other)->patchJson(route('waste.dishes.update', $dish), ['title' => 'x', 'description' => 'y'])->assertNotFound();
    $this->actingAs($other)->deleteJson(route('waste.dishes.destroy', $dish))->assertNotFound();

    $this->actingAs($dish->user)
        ->patchJson(route('waste.dishes.update', $dish), ['title' => 'New title', 'description' => 'New text'])
        ->assertOk();

    expect($dish->fresh()->title)->toBe('New title');

    $this->actingAs($dish->user)->deleteJson(route('waste.dishes.destroy', $dish))->assertOk();

    $this->assertModelMissing($dish);
});

test('the dishes page lists only the own recent dishes', function () {
    $this->withoutVite();
    $user = User::factory()->create();
    DishDescription::factory()->for($user)->create(['title' => 'Mine']);
    DishDescription::factory()->create(['title' => 'Not mine']);

    $this->actingAs($user)
        ->get(route('waste.dishes.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('waste/Dishes')
            ->has('recent', 1)
            ->where('recent.0.title', 'Mine')
            ->where('quota.used', 1));

    auth()->logout();
    $this->get(route('waste.dishes.index'))->assertRedirect();
});

test('the model answer is cleaned up', function (string $raw, ?array $expected) {
    $writer = new DishWriter(Mockery::mock(AiVisionClient::class));

    expect($writer->normalise($raw))->toEqual($expected);
})->with([
    'code fence and unknown origin' => [
        "```json\n".json_encode(['dish_name' => 'Burger', 'origin' => 'unknown', 'title' => 'House Burger', 'description' => 'Juicy.', 'confidence' => 'sure'])."\n```",
        ['dish_name' => 'Burger', 'origin' => null, 'title' => 'House Burger', 'description' => 'Juicy.', 'confidence' => 'medium'],
    ],
    'more than two paragraphs are cut' => [
        json_encode(['dish_name' => 'A', 'origin' => 'Italy', 'title' => 'T', 'description' => "One.\n\nTwo.\n\nThree.", 'confidence' => 'high']),
        ['dish_name' => 'A', 'origin' => 'Italy', 'title' => 'T', 'description' => "One.\n\nTwo.", 'confidence' => 'high'],
    ],
    'no title' => [json_encode(['description' => 'x']), null],
    'not json' => ['hello', null],
]);
