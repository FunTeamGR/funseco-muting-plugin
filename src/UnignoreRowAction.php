<?php
namespace Funseco\Muting;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Muting\Commands\IgnoreListCommand;

/** Click on a row of the global ignore list: runs /admin unignore for that login, then refreshes the list. */
class UnignoreRowAction
{
    public function handle(Player $admin, string $action): void
    {
        $login = IgnoreListCommand::loginAt($admin->login, (int) substr($action, strlen('muting_unignore_')));
        if ($login !== null) {
            Chat::run($admin, 'admin unignore', [$login]);
            Chat::run($admin, 'admin ignorelist');
        }
    }
}
