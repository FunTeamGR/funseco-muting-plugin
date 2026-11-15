<?php
namespace Funseco\Muting\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Core\SharedKernel\UI\Window;
use Funseco\Muting\PlayerMuteList;
use Funseco\Muting\Support\Text;

class MuteListCommand implements Command, Described
{
    use DeclaresCommand;

    protected string $name = 'mutelist';
    protected string $description = 'Display list of muted players';

    public function handle(Player $player, array $args): void
    {
        $logins = app(PlayerMuteList::class)->mutedBy($player->login);
        if (!$logins) {
            Text::tell($player->login, Text::message('no_muted'));
            return;
        }

        Window::show($player, 'Currently Muted Players:', self::rows($logins), [0.9, 0.1, 0.8],
            ['Icons128x128_1', 'Padlock', 0.01], columns: ['Id', '{#nick}Nick $g/{#login} Login']);
    }

    /** "01." + nick / login rows, nicknames from the database where known. */
    public static function rows(array $logins, ?callable $button = null): array
    {
        $nicks = Player::whereIn('login', $logins)->pluck('nickname', 'login');
        $rows  = [];
        foreach (array_values($logins) as $i => $login) {
            $label = '{#black}'.Text::nick($nicks[$login] ?? $login).'$z / {#login}'.$login;
            $rows[] = [str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT).'.', $button ? $button($label, $i + 1) : $label];
        }

        return $rows;
    }
}
