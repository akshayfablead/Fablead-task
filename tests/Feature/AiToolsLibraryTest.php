<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiToolsLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_api_library_requires_authentication(): void
    {
        $this->get(route('ai-tools.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_browse_the_ai_api_library(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('ai-tools.index'))
            ->assertOk()
            ->assertSee('AI API Library')
            ->assertSee('Gemini API')
            ->assertSee('Cloud Vision')
            ->assertSee('Document Intelligence')
            ->assertSee('Tesseract OCR')
            ->assertSee('OpenAI API')
            ->assertSee('Claude API')
            ->assertSee('Inference Providers')
            ->assertSee(route('ai-tools.index'))
            ->assertDontSee('type="password"');
    }
}
