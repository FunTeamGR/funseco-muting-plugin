<?php
namespace Funseco\Muting\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\Services\Auth\Permissions;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Core\SharedKernel\Commands\Guarded;
use Funseco\Core\SharedKernel\Enums\Role;

/** Base for the /admin global mute commands: staff only, ability from adminops. */
abstract class GlobalMuteCommand implements Command, Guarded, Described
{
    use DeclaresCommand;

    protected ?Role $role = Role::Operator;

    protected function title(Player $admin): string
    {
        return app(Permissions::class)->title($admin);
    }
}
