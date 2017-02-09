<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExampleTest extends TestCase
{
    /**
     * Test redirect from / to /login.
     *
     * @return void
     */
    public function testRedirect()
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    /**
     * Test /login status 200.
     *
     * @return void
     */
    public function testLogin()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }
}
