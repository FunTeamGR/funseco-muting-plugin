<?php
namespace Funseco\Muting;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\MuteList;

/**
 * Personal mute lists (per listener, gone when they leave) and the global list, which mirrors
 * the server's ignore list (XAseco on TMF: /admin mute = server Ignore).
 */
class PlayerMuteList implements MuteList
{
    private const GLOBAL = 'muting:global';

    public function isMuted(Player $listener, Player $speaker): bool
    {
        return in_array($speaker->login, $this->mutedBy($listener->login), true);
    }

    public function isGloballyMuted(Player $speaker): bool
    {
        return in_array($speaker->login, $this->global(), true);
    }

    /** @return string[] logins $listener has muted */
    public function mutedBy(string $listener): array
    {
        return state()->get(self::key($listener), []);
    }

    /** False when already muted. */
    public function mute(string $listener, string $speaker): bool
    {
        $list = $this->mutedBy($listener);
        if (in_array($speaker, $list, true)) {
            return false;
        }
        state()->forever(self::key($listener), [...$list, $speaker]);

        return true;
    }

    /** False when not muted. */
    public function unmute(string $listener, string $speaker): bool
    {
        $list = $this->mutedBy($listener);
        if (!in_array($speaker, $list, true)) {
            return false;
        }
        state()->forever(self::key($listener), array_values(array_diff($list, [$speaker])));

        return true;
    }

    public function forget(string $listener): void
    {
        state()->forget(self::key($listener));
    }

    /** @return string[] */
    public function global(): array
    {
        return state()->get(self::GLOBAL, []);
    }

    /** The global list is the server's ignore list, which outlives a controller restart. */
    public function loadFromServer(): void
    {
        \Server::query()->GetIgnoreList(1000, 0, fn ($result) => $this->setGlobal(array_column($result[0] ?? [], 'Login')));
    }

    public function setGlobal(array $logins): void
    {
        state()->forever(self::GLOBAL, array_values(array_unique($logins)));
    }

    public function addGlobal(string $login): void
    {
        $this->setGlobal([...$this->global(), $login]);
    }

    public function removeGlobal(string $login): void
    {
        $this->setGlobal(array_diff($this->global(), [$login]));
    }

    private static function key(string $listener): string
    {
        return 'muting:personal:'.$listener;
    }
}
