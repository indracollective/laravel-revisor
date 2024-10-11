<?php

declare(strict_types=1);

namespace Indra\Revisor\Filament;

use Filament\Tables\Columns\TextColumn;
use Indra\Revisor\Contracts\HasRevisor;

class PublishStatusTableColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Status')
            ->badge()
            ->getStateUsing(function (HasRevisor $record) {
                if (! $record->isPublished()) {
                    return 'draft';
                }

                return $record->isRevised() ? 'published,revised' : 'published';
            })
            ->separator(',')
            ->color(fn (string $state): string => match ($state) {
                'revised' => 'warning',
                'published' => 'success',
                'draft' => 'gray',
            });
    }
}
