<?php
namespace Funseco\Muting\Commands;

use Funseco\Core\Models\Player;
use Funseco\Muting\PlayerMuteList;
use Funseco\Muting\Support\Text;

class CleanIgnoresCommand extends GlobalMuteCommand
{
    protected string $name = 'admin cleanmutes';
    protected ?string $ability = 'cleanmutes';
    protected string $description = 'Cleans global mute/ignore list';
    protected array $aliases = ['admin cleanignores'];

    public function handle(Player $player, array $args): void
    {
        \Server::query()->CleanIgnoreList();
        app(PlayerMuteList::class)->setGlobal([]);
        Text::tell($player->login, Text::message('cleaned'));
    }
}
