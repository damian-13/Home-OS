<?php

namespace App\Notifications\Application\Service;

use App\Notifications\Application\Dto\NotificationDigestView;

final readonly class DailyNotificationDigestRenderer
{
    public function renderText(NotificationDigestView $digest): string
    {
        $lines = [
            'Home OS daily digest',
            sprintf('Household: %s', $digest->householdId),
            sprintf('Generated: %s', $digest->generatedAt),
            sprintf('Total items: %d', $digest->totalItems),
            '',
        ];

        foreach ($digest->sections as $section) {
            $lines[] = sprintf('%s (%d)', $section->title, $section->count);

            if ($section->items === []) {
                $lines[] = '  - Nothing for this section.';
                $lines[] = '';
                continue;
            }

            foreach ($section->items as $item) {
                $due = $item->dueAt === null ? '' : sprintf(' · due %s', $item->dueAt);
                $lines[] = sprintf('  - [%s] %s%s', $item->severity, $item->title, $due);
                $lines[] = sprintf('    %s', $item->detail);
                $lines[] = sprintf('    Open: %s', $item->targetUrl);
            }

            $lines[] = '';
        }

        return implode(PHP_EOL, $lines);
    }
}
