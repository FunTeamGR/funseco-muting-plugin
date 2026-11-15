<?php
namespace Funseco\Muting;

use Funseco\Core\SharedKernel\PublishesPluginConfig;
use Illuminate\Support\Facades\Event;
use Funseco\Core\SharedKernel\Settings\Setting;
use Funseco\Core\SharedKernel\Settings\SettingsRegistry;
use Illuminate\Support\ServiceProvider;
use Funseco\Core\Services\ManiaLink\ManiaLinkService;
use Funseco\Core\SharedKernel\Events\PlayerChatted;
use Funseco\Core\SharedKernel\Events\PlayerDisconnected;
use Funseco\Core\SharedKernel\Events\ServerChatted;
use Funseco\Core\SharedKernel\Events\StartupEvent;
use Funseco\Core\SharedKernel\Events\VotePassed;
use Funseco\Core\SharedKernel\MuteList;
use Funseco\Muting\Commands as C;

class MutingServiceProvider extends ServiceProvider
{
    use PublishesPluginConfig;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/muting.php', 'muting');
        $this->publishPluginConfig(__DIR__.'/..');
        $this->app->make(SettingsRegistry::class)->declare('muting', 'muting', [
            Setting::int('buffer_lines', 'Chat lines buffered per player', min: 1),
            // action ids for the unignore buttons are registered in boot()
            Setting::int('clickable_rows', 'Clickable rows in ignore list', min: 1, restart: true),
        ]);

        $this->app->singleton(PlayerMuteList::class);
        $this->app->singleton(MuteList::class, fn ($app) => $app->make(PlayerMuteList::class));
        $this->app->singleton(ChatScroller::class);
    }

    public function boot(): void
    {
        foreach ([C\MuteCommand::class, C\UnmuteCommand::class, C\MuteListCommand::class, C\RefreshCommand::class,
                  C\IgnoreCommand::class, C\UnignoreCommand::class, C\IgnoreListCommand::class, C\CleanIgnoresCommand::class] as $command) {
            \Chat::register($command);
        }

        $actions = array_map(fn ($n) => "muting_unignore_$n", range(1, (int) config('muting.clickable_rows', 200)));
        app(ManiaLinkService::class)->addActions($actions, UnignoreRowAction::class);

        Event::listen(PlayerChatted::class, [ChatScroller::class, 'playerChatted']);
        Event::listen(ServerChatted::class, [ChatScroller::class, 'serverChatted']);
        Event::listen(PlayerDisconnected::class, function (PlayerDisconnected $event) {
            app(PlayerMuteList::class)->forget($event->player->login);
            app(ChatScroller::class)->forget($event->player->login);
        });
        Event::listen(StartupEvent::class, fn () => app(PlayerMuteList::class)->loadFromServer());
        // A passed /ignore vote put the player on the server's ignore list.
        Event::listen(VotePassed::class, function (VotePassed $event) {
            if ($event->type === 'ignore' && !empty($event->payload['target'])) {
                app(PlayerMuteList::class)->addGlobal($event->payload['target']);
            }
        });
    }
}
