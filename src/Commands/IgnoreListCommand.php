<?php
namespace Funseco\Muting\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\UI\Window;
use Funseco\Muting\Support\Text;

/** The server's ignore list; a click on a row un-ignores that player (XAseco clickable list). */
class IgnoreListCommand extends GlobalMuteCommand
{
    protected string $name = 'admin mutelist';
    protected ?string $ability = 'mutelist';
    protected string $description = 'Displays global mute/ignore list';
    protected array $aliases = ['admin listmutes', 'admin ignorelist', 'admin listignores'];

    public function handle(Player $player, array $args): void
    {
        \Server::query()->GetIgnoreList(1000, 0, function ($result) use ($player) {
            $logins = array_column($result[0] ?? [], 'Login');
            if (!$logins) {
                Text::tell($player->login, Text::message('no_ignored'));
                return;
            }

            state()->forever(self::key($player->login), $logins);
            $max = (int) config('muting.clickable_rows', 200);
            $rows = MuteListCommand::rows($logins, fn ($label, $n) => $n <= $max ? [$label, "muting_unignore_$n"] : $label);

            Window::show($player, 'Globally Muted/Ignored Players:', $rows, [0.9, 0.1, 0.8],
                ['Icons128x128_1', 'Padlock', 0.01], columns: ['Id', '{#nick}Nick $g/{#login} Login$g (click to UnIgnore)']);
        });
    }

    /** Login on row $n of the admin's last list. */
    public static function loginAt(string $admin, int $n): ?string
    {
        return state()->get(self::key($admin), [])[$n - 1] ?? null;
    }

    private static function key(string $admin): string
    {
        return 'muting:ignorelist:'.$admin;
    }
}
