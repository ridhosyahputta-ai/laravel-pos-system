<?php

use App\Models\User;

test('public registration is disabled', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', ['name' => 'Blocked', 'email' => 'blocked@example.test', 'password' => 'password', 'password_confirmation' => 'password'])->assertNotFound();
    expect(User::count())->toBe(0);
    $this->get('/')->assertDontSee('href="'.url('/register').'"', false);
});
