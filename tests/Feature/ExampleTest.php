<?php

it('returns a successful response for the homepage', function () {
    $this->get('/')->assertOk();
});
