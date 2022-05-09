<?php

declare(strict_types=1);

namespace JonasPardon\LaravelEventVisualizer\Tests\Unit;

use Illuminate\Support\Facades\Config;
use JonasPardon\LaravelEventVisualizer\EventVisualizer;
use JonasPardon\LaravelEventVisualizer\Models\Event;
use JonasPardon\LaravelEventVisualizer\Models\Listener;
use JonasPardon\LaravelEventVisualizer\Services\CodeParser;
use JonasPardon\LaravelEventVisualizer\Tests\TestCase;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;

final class EventVisualizerTest extends TestCase
{
    private CodeParser $codeParser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codeParser = new CodeParser(
            new NodeTraverser(),
            new NodeFinder(),
        );
    }

    /** @test */
    public function it_parses_basic_events(): void
    {
        $visualizer = new EventVisualizer($this->codeParser);

        $event = new Event('App\\Events\\Event1');
        $listener1 = new Listener('App\\Listeners\\Listener1');
        $listener2 = new Listener('App\\Listeners\\Listener2');

        $output = $visualizer->buildMermaidString([
            $event->getClassName() => [
                $listener1->getClassName(),
                $listener2->getClassName(),
            ],
        ]);

        $this->assertStringContainsString(
            "{$event->toString()} --> {$listener1->toString()}",
            $output,
        );
        $this->assertStringContainsString(
            "{$event->toString()} --> {$listener2->toString()}",
            $output,
        );
    }

    /** @test */
    public function it_defines_theme_colors(): void
    {
        $visualizer = new EventVisualizer($this->codeParser);
        $output = $visualizer->buildMermaidString([]);

        $eventColor = config('event-visualizer.theme.colors.event');
        $listenerColor = config('event-visualizer.theme.colors.listener');
        $jobColor = config('event-visualizer.theme.colors.job');

        $this->assertStringContainsString(
            "classDef event fill:{$eventColor};",
            $output,
        );
        $this->assertStringContainsString(
            "classDef listener fill:{$listenerColor};",
            $output,
        );
        $this->assertStringContainsString(
            "classDef job fill:{$jobColor};",
            $output,
        );
    }

    /** @test */
    public function it_includes_laravel_events_if_so_configured(): void
    {
        Config::set('event-visualizer.show_laravel_events', true);

        $visualizer = new EventVisualizer($this->codeParser);

        $appEvent = new Event('App\\Events\\Event');
        $laravelEvent = new Event('Illuminate\\Auth\\Events\\Login');
        $listener = new Listener('App\\Listeners\\Listener');

        $output = $visualizer->buildMermaidString([
            $appEvent->getClassName() => [
                $listener->getClassName(),
            ],
            $laravelEvent->getClassName() => [
                $listener->getClassName(),
            ],
        ]);

        $this->assertStringContainsString(
            "{$appEvent->toString()} --> {$listener->toString()}",
            $output,
        );
        $this->assertStringContainsString(
            "{$laravelEvent->toString()} --> {$listener->toString()}",
            $output,
        );
    }

    /** @test */
    public function it_excludes_laravel_events_if_so_configured(): void
    {
        Config::set('event-visualizer.show_laravel_events', false);

        $visualizer = new EventVisualizer($this->codeParser);

        $appEvent = new Event('App\\Events\\Event');
        $laravelEvent = new Event('Illuminate\\Auth\\Events\\Login');
        $listener = new Listener('App\\Listeners\\Listener');

        $output = $visualizer->buildMermaidString([
            $appEvent->getClassName() => [
                $listener->getClassName(),
            ],
            $laravelEvent->getClassName() => [
                $listener->getClassName(),
            ],
        ]);

        $this->assertStringContainsString(
            "{$appEvent->toString()} --> {$listener->toString()}",
            $output,
        );
        $this->assertStringNotContainsString(
            "{$laravelEvent->toString()} --> {$listener->toString()}",
            $output,
        );
    }

    /** @test */
    public function it_ignores_configured_listeners(): void
    {
        Config::set('event-visualizer.classes_to_ignore', [
            'ListenerToIgnore',
        ]);

        $visualizer = new EventVisualizer($this->codeParser);

        $event = new Event('App\\Events\\Event');
        $listenerToInclude = new Listener('App\\Listeners\\Listener');
        $listenerToIgnore = new Listener('App\\Listeners\\ListenerToIgnore');

        $output = $visualizer->buildMermaidString([
            $event->getClassName() => [
                $listenerToInclude->getClassName(),
                $listenerToIgnore->getClassName(),
            ],
        ]);

        $this->assertStringContainsString(
            "{$event->toString()} --> {$listenerToInclude->toString()}",
            $output,
        );
        $this->assertStringNotContainsString(
            "{$event->toString()} --> {$listenerToIgnore->toString()}",
            $output,
        );
    }

    /** @test */
    public function it_ignores_configured_events(): void
    {
        $this->markTestIncomplete('Not implemented yet');

        Config::set('event-visualizer.classes_to_ignore', [
            'EventToIgnore',
        ]);

        $visualizer = new EventVisualizer($this->codeParser);

        $eventToInclude = new Event('App\\Events\\Event');
        $eventToIgnore = new Event('App\\Events\\EventToIgnore');
        $listener = new Listener('App\\Listeners\\Listener');

        $output = $visualizer->buildMermaidString([
            $eventToInclude->getClassName() => [
                $listener->getClassName(),
            ],
            $eventToIgnore->getClassName() => [
                $listener->getClassName(),
            ],
        ]);

        $this->assertStringContainsString(
            "{$eventToInclude->toString()} --> {$listener->toString()}",
            $output,
        );
        $this->assertStringNotContainsString(
            "{$eventToIgnore->toString()} --> {$listener->toString()}",
            $output,
        );
    }
}
