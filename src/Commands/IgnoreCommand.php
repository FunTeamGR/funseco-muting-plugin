<?php
namespace Funseco\Muting\Commands;

use Funseco\Core\Models\Player;
use Funseco\Muting\PlayerMuteList;
use Funseco\Muting\Support\Target;
use Funseco\Muting\Support\Text;

/** XAseco on TMF: global mute = the server's ignore list (the player's chat isn't broadcast). */
class IgnoreCommand extends GlobalMuteCommand
{
    protected string $name = 'admin mute';
    protected ?string $ability = 'mute';
    protected string $description = 'Adds a player to global mute/ignore list';
    protected string $usage = '<login/id>';
    protected array $aliases = ['admin ignore'];

    public function handle(Player $player, array $args): void
    {
        if (!$target = Target::resolve($player, (string) ($args[0] ?? ''))) {
            return;
        }

        \Server::query()->Ignore($target->login);
        app(PlayerMuteList::class)->addGlobal($target->login);
        \Server::query()->ChatSendServerMessage(Text::message('ignores', $this->title($player), Text::nick($player->nickname), Text::nick($target->nickname)));
    }
}
