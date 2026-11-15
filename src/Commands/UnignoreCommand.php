<?php
namespace Funseco\Muting\Commands;

use Funseco\Core\Models\Player;
use Funseco\Muting\PlayerMuteList;
use Funseco\Muting\Support\Target;
use Funseco\Muting\Support\Text;

class UnignoreCommand extends GlobalMuteCommand
{
    protected string $name = 'admin unmute';
    protected ?string $ability = 'unmute';
    protected string $description = 'Removes a player from global mute/ignore list';
    protected string $usage = '<login/id>';
    protected array $aliases = ['admin unignore'];

    public function handle(Player $player, array $args): void
    {
        $arg = (string) ($args[0] ?? '');
        if (!$target = Target::resolve($player, $arg, offline: true)) {
            return;
        }

        \Server::query()->UnIgnore($target->login, function ($result) use ($player, $target, $arg) {
            if (empty($result[0])) {
                Text::tell($player->login, Text::message('not_ignored', $arg));
                return;
            }
            app(PlayerMuteList::class)->removeGlobal($target->login);
            Text::tell($player->login, Text::message('unignores', $this->title($player), Text::nick($player->nickname), Text::nick($target->nickname)));
        });
    }
}
