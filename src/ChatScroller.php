<?php
namespace Funseco\Muting;

use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\SharedKernel\Events\PlayerChatted;
use Funseco\Core\SharedKernel\Events\ServerChatted;

/**
 * XAseco's muting: the server can't hide a line from one player, so each player keeps a buffer
 * of the chat they want to see; when someone they muted speaks, the buffer is re-sent, which
 * scrolls the muted line out of their chat window.
 */
class ChatScroller
{
    public function __construct(private PlayerMuteList $mutes)
    {
    }

    public function playerChatted(PlayerChatted $event): void
    {
        $line = '$z$s['.$event->player->nickname.'$z$s] '.$event->text;

        foreach (CurrentPlayers::all() as $listener) {
            if ($this->mutes->isMuted($listener, $event->player) || $this->mutes->isGloballyMuted($event->player)) {
                $this->resend($listener->login);
            } else {
                $this->remember($listener->login, $line);
            }
        }
    }

    /** Global server messages (">> ..." announcements, round lines) stay in everyone's buffer. */
    public function serverChatted(ServerChatted $event): void
    {
        if (!preg_match(self::globalPattern(), $event->text)) {
            return;
        }
        foreach (CurrentPlayers::all() as $listener) {
            $this->remember($listener->login, $event->text);
        }
    }

    public function resend(string $login): void
    {
        $buffer = state()->get(self::key($login), []);
        if ($buffer) {
            \Server::query()->ChatSendServerMessageToLogin(implode('', array_map(fn ($line) => "\n".'$z$z$s'.$line, $buffer)), $login);
        }
    }

    public function forget(string $login): void
    {
        state()->forget(self::key($login));
    }

    private function remember(string $login, string $line): void
    {
        $buffer = [...state()->get(self::key($login), []), $line];
        state()->forever(self::key($login), array_slice($buffer, -max(1, (int) config('muting.buffer_lines', 28))));
    }

    /** XAseco init_globalpat(): round lines, '$z$s{#server}>> ' and '{#server}>> ' at the start. */
    private static function globalPattern(): string
    {
        $round = preg_quote(format_colors('$n{#message}R{#highlite}'), '/').'\d+'.preg_quote(format_colors('{#message}> '), '/');

        return '/^(?:'.$round.'|'.preg_quote(format_colors('$z$s{#server}>> '), '/').'|'.preg_quote(format_colors('{#server}>> '), '/').')/';
    }

    private static function key(string $login): string
    {
        return 'muting:buffer:'.$login;
    }
}
