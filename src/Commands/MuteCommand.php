<?php
namespace Funseco\Muting\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Core\SharedKernel\Enums\Role;
use Funseco\Muting\PlayerMuteList;
use Funseco\Muting\Support\Target;
use Funseco\Muting\Support\Text;

class MuteCommand implements Command, Described
{
    use DeclaresCommand;

    protected string $name = 'mute';
    protected string $description = 'Mute another player\'s chat messages';
    protected string $usage = '<login/id>';

    public function handle(Player $player, array $args): void
    {
        if (!$target = Target::resolve($player, (string) ($args[0] ?? ''))) {
            return;
        }

        if (($role = $target->effectiveRole()) !== Role::Player) {
            $title = config("adminops.titles.{$role->value}", ucfirst($role->value));
            Text::tell($player->login, Text::message('cannot_mute', $title, Text::stripColors($target->nickname)));
            return;
        }

        $message = app(PlayerMuteList::class)->mute($player->login, $target->login)
            ? Text::message('muted', (string) $target->nickname)
            : Text::message('already_muted', Text::stripColors($target->nickname));
        Text::tell($player->login, $message);
    }
}
