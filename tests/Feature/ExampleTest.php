<?php

it('redirects unauthenticated users to welcome page', function () {
    $response = $this->get('/');

    $response->assertStatus(302);
    $response->assertRedirect('/welcome');
});
