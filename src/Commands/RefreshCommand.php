<?php
namespace Funseco\Muting\Commands;

use Funseco\Core\Models\Player;
use Funseco\Core\SharedKernel\Commands\Command;
use Funseco\Core\SharedKernel\Commands\DeclaresCommand;
use Funseco\Core\SharedKernel\Commands\Described;
use Funseco\Muting\ChatScroller;

class RefreshCommand implements Command, Described
{
    use DeclaresCommand;

    protected string $name = 'refresh';
    protected string $description = 'Refresh chat window';

    public function handle(Player $player, array $args): void
    {
        app(ChatScroller::class)->resend($player->login);
    }
}
