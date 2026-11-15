<?php
namespace Funseco\Muting\Support;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentPlayers;

/**
 * XAseco getPlayerParam(): a login or the number shown in /players; with $offline, a known
 * login of a player who is not online. Tells the caller and returns null when nothing matches.
 */
final class Target
{
    public static function resolve(Player $caller, string $arg, bool $offline = false): ?Player
    {
        if ($arg !== '' && ctype_digit($arg) && (int) $arg < 300) {
            $target = CurrentPlayers::findByLoginOrIndex($arg);
            if (!$target) {
                Text::tell($caller->login, Text::message('id_not_found'));
            }
            return $target;
        }

        $target = CurrentPlayers::get($arg) ?? ($offline && $arg !== '' ? Player::where('login', $arg)->first() : null);
        if (!$target) {
            Text::tell($caller->login, Text::message('login_not_found', $arg));
        }

        return $target;
    }
}
