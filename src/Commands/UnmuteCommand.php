<?php
namespace Funseco\Muting\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Muting\PlayerMuteList;
use Funseco\Muting\Support\Target;
use Funseco\Muting\Support\Text;

class UnmuteCommand implements Command, Described
{
    use DeclaresCommand;

    protected string $name = 'unmute';
    protected string $description = 'UnMute another player\'s chat messages';
    protected string $usage = '<login/id>';

    public function handle(Player $player, array $args): void
    {
        if (!$target = Target::resolve($player, (string) ($args[0] ?? ''), offline: true)) {
            return;
        }

        $message = app(PlayerMuteList::class)->unmute($player->login, $target->login)
            ? Text::message('unmuted', (string) $target->nickname)
            : Text::message('not_muted', Text::stripColors($target->nickname));
        Text::tell($player->login, $message);
    }
}
