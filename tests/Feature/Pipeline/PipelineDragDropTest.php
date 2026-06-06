<?php

declare(strict_types=1);

use App\Enums\PipelineStage;
use App\Models\Patient;
use App\Models\PipelineCard;
use App\Models\User;

test('the board renders the drag-and-drop wiring', function () {
    $this->actingAs(User::factory()->admin()->create());
    $card = PipelineCard::factory()->for(Patient::factory())->create(['stage' => PipelineStage::Negociando]);

    $this->get(route('pipeline'))
        ->assertOk()
        ->assertSee('x-data="kanban()"', false)
        ->assertSee('data-stage-list', false)
        ->assertSee('data-stage="'.PipelineStage::Negociando->value.'"', false)
        ->assertSee('data-card-id="'.$card->id.'"', false);
});
