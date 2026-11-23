<?php

test('the root route opens the administrative login', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect('/admin/login');
});
