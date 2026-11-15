<?php
namespace Funseco\Muting\Support;

/** XAseco text helpers for this plugin's messages. */
final class Text
{
    /** config('muting.messages.<key>') with {1}, {2}, ... filled in, colours applied. */
    public static function message(string $key, string ...$values): string
    {
        return self::format(format_colors((string) config("muting.messages.$key", '')), ...$values);
    }

    public static function format(string $template, string ...$values): string
    {
        $map = [];
        foreach ($values as $i => $value) {
            $map['{'.($i + 1).'}'] = (string) $value;
        }

        return strtr($template, $map);
    }

    /** XAseco stripColors(): drop $-codes, keep a literal '$$' as '$'. */
    public static function stripColors(?string $text): string
    {
        $text = str_replace('$$', "\0", (string) $text);
        $text = preg_replace('/\$(?:[0-9a-f][^$]{0,2}|[lh]\[[^\]]*\]|[^$]?)/i', '', $text);

        return str_replace("\0", '$', $text);
    }

    /** Nickname as XAseco shows it in admin messages: without $w (wide). */
    public static function nick(?string $nickname): string
    {
        return str_ireplace('$w', '', (string) $nickname);
    }

    public static function tell(string $login, string $message): void
    {
        \Server::query()->ChatSendServerMessageToLogin($message, $login);
    }
}
