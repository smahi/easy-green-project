<?php

use App\Filament\Widgets\ReportsMap;
use Livewire\Livewire;

it('can render reports map widget', function () {
    Livewire::test(ReportsMap::class)
        ->assertStatus(200);
});
