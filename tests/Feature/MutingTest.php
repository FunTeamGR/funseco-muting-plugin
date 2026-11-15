<?php
namespace Funseco\Muting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Funseco\Core\Testing\FakesServer;
use Funseco\Muting\Tests\TestCase;
use Funseco\Core\Models\Player;
use Funseco\Core\Services\Cache\CurrentPlayers;
use Funseco\Core\Services\Chat\Facades\Chat;
use Funseco\Core\Services\ManiaLink\Facades\ManiaLink;
use Funseco\Core\SharedKernel\Events\PlayerChatted;
use Funseco\Core\SharedKernel\Events\PlayerDisconnected;
use Funseco\Core\SharedKernel\Events\ServerChatted;
use Funseco\Core\SharedKernel\MuteList;

class MutingTest extends TestCase
{
    use FakesServer;
    use RefreshDatabase;

    private Player $alice;
    private Player $bob;
    private Player $carol;
    private Player $boss;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeServer();
        CurrentPlayers::clear();
        config(['adminops.masteradmins' => ['boss']]);
        foreach (['alice' => 'Alice', 'bob' => '$f00Bob', 'carol' => 'Carol', 'boss' => 'Boss'] as $login => $nick) {
            $this->$login = Player::factory()->create(['login' => $login, 'nickname' => $nick]);
            CurrentPlayers::add($this->$login);
        }
    }

    private function toLogin(string $login): array
    {
        return array_values(array_map(fn ($a) => $a[0], array_filter($this->sent('ChatSendServerMessageToLogin'), fn ($a) => $a[1] === $login)));
    }

    // --- personal mutes ---------------------------------------------------

    public function test_mute_and_unmute_a_player()
    {
        Chat::resolve('/mute bob', 'alice');
        $this->assertTrue(app(MuteList::class)->isMuted($this->alice, $this->bob));
        $this->assertFalse(app(MuteList::class)->isMuted($this->carol, $this->bob), 'personal');
        $this->assertSame([format_colors('{#server}> Player {#highlite}$f00Bob$z$s{#server} is muted!')], $this->toLogin('alice'));

        Chat::resolve('/mute bob', 'alice');
        $this->assertSame(format_colors('{#server}> {#error}Player {#highlite}$i Bob$z$s{#error} is already in your mute list!'), $this->toLogin('alice')[1]);

        Chat::resolve('/unmute bob', 'alice');
        $this->assertFalse(app(MuteList::class)->isMuted($this->alice, $this->bob));
        $this->assertSame(format_colors('{#server}> Player {#highlite}$f00Bob$z$s{#server} is unmuted!'), $this->toLogin('alice')[2]);

        Chat::resolve('/unmute bob', 'alice');
        $this->assertSame(format_colors('{#server}> {#error}Player {#highlite}$i Bob$z$s{#error} is not in your mute list!'), $this->toLogin('alice')[3]);
    }

    public function test_staff_cannot_be_muted()
    {
        Chat::resolve('/mute boss', 'alice');

        $this->assertFalse(app(MuteList::class)->isMuted($this->alice, $this->boss));
        $this->assertSame([format_colors('{#server}> {#error}Cannot mute {#logina}$i God {#highlite}Boss$z$s{#error} !')], $this->toLogin('alice'));
    }

    public function test_mutelist_window_and_empty_message()
    {
        Chat::resolve('/mutelist', 'alice');
        $this->assertSame([format_colors('{#server}> {#error}No muted players found!')], $this->toLogin('alice'));

        Chat::resolve('/mute bob', 'alice');
        Chat::resolve('/mutelist', 'alice');
        $this->assertStringContainsString('Currently Muted Players:', $this->window());
        $this->assertStringContainsString('bob', $this->window());
    }

    // --- scroll-away (XAseco mechanism) -----------------------------------

    public function test_muted_line_is_scrolled_away_with_the_listeners_buffer()
    {
        Chat::resolve('/mute bob', 'alice');
        $this->calls = [];

        event(new PlayerChatted($this->carol, 'hi all'));
        event(new ServerChatted(format_colors('{#server}>> Map restarted')));
        event(new ServerChatted('some private-looking line'));   // not a global message: not buffered
        event(new PlayerChatted($this->bob, 'spam'));

        $resent = $this->toLogin('alice');
        $this->assertCount(1, $resent, 'only alice, who muted bob, gets her buffer re-sent');
        $this->assertSame("\n".'$z$z$s$z$s[Carol$z$s] hi all'."\n".'$z$z$s'.format_colors('{#server}>> Map restarted'), $resent[0]);
        $this->assertSame([], $this->toLogin('carol'));
    }

    public function test_buffer_keeps_the_last_lines_and_refresh_resends_it()
    {
        config(['muting.buffer_lines' => 3]);
        foreach (['one', 'two', 'three', 'four'] as $line) {
            event(new PlayerChatted($this->carol, $line));
        }

        Chat::resolve('/refresh', 'alice');

        $this->assertSame(["\n".'$z$z$s$z$s[Carol$z$s] two'."\n".'$z$z$s$z$s[Carol$z$s] three'."\n".'$z$z$s$z$s[Carol$z$s] four'], $this->sent('ChatSendServerMessageToLogin') ? array_column($this->sent('ChatSendServerMessageToLogin'), 0) : []);
    }

    public function test_leaving_clears_the_players_mutes_and_buffer()
    {
        Chat::resolve('/mute bob', 'alice');
        event(new PlayerChatted($this->carol, 'hello'));

        event(new PlayerDisconnected($this->alice));

        $this->assertFalse(app(MuteList::class)->isMuted($this->alice, $this->bob));
        $this->calls = [];
        Chat::resolve('/refresh', 'alice');
        $this->assertSame([], $this->sent('ChatSendServerMessageToLogin'));
    }

    // --- global (server ignore list) --------------------------------------

    public function test_admin_ignore_and_mute_use_the_server_ignore_list()
    {
        Chat::resolve('/admin mute bob', 'boss');

        $this->assertSame([['bob']], $this->sent('Ignore'));
        $this->assertTrue(app(MuteList::class)->isGloballyMuted($this->bob));
        $this->assertContains(format_colors('{#server}>> {#admin}God$z$s {#highlite}Boss$z$s{#admin} ignores {#highlite}$f00Bob$z$s{#admin} !'), $this->messages('ChatSendServerMessage'));

        Chat::resolve('/admin unignore bob', 'boss');
        $this->assertSame([['bob']], $this->sent('UnIgnore'));
        $this->assertFalse(app(MuteList::class)->isGloballyMuted($this->bob));
        $this->assertSame([format_colors('{#server}> {#admin}God$z$s {#highlite}Boss$z$s{#admin} un-ignores {#highlite}$f00Bob')], $this->toLogin('boss'));
    }

    public function test_unignore_of_a_player_the_server_does_not_ignore()
    {
        $this->responses['UnIgnore'] = false;

        Chat::resolve('/admin unmute bob', 'boss');

        $this->assertSame([format_colors('{#server}> {#highlite}bob{#error} is not an ignored player!')], $this->toLogin('boss'));
    }

    public function test_players_cannot_use_admin_mute_commands()
    {
        Chat::resolve('/admin ignore bob', 'alice');

        $this->assertSame([], $this->sent('Ignore'));
        $this->assertStringContainsString('You have to be in admin list to do that!', $this->toLogin('alice')[0]);
    }

    public function test_ignore_list_window_rows_unignore_on_click()
    {
        $this->responses['GetIgnoreList'] = [['Login' => 'bob'], ['Login' => 'carol']];

        Chat::resolve('/admin listignores', 'boss');
        $this->assertStringContainsString('Globally Muted/Ignored Players:', $this->window());
        $this->assertStringContainsString('(click to UnIgnore)', $this->window());

        ManiaLink::dispatchAnswer($this->boss, ManiaLink::getAction('muting_unignore_2'));
        $this->assertSame([['carol']], $this->sent('UnIgnore'));
    }

    public function test_clean_ignores_is_masteradmin_only_per_adminops()
    {
        Chat::resolve('/admin cleanignores', 'boss');

        $this->assertSame([[]], $this->sent('CleanIgnoreList'));
        $this->assertSame([format_colors('{#server}> {#admin}Cleaned global mute/ignore list!')], $this->toLogin('boss'));
    }

    // --- [RF 4] restart ---------------------------------------------------

    public function test_global_list_is_restored_from_the_server_at_startup()
    {
        $this->assertFalse(app(MuteList::class)->isGloballyMuted($this->bob), 'fresh process: nothing muted');
        $this->responses['GetIgnoreList'] = [['Login' => 'bob']];

        app(\Funseco\Muting\PlayerMuteList::class)->loadFromServer();   // what the StartupEvent listener runs

        $this->assertTrue(app(MuteList::class)->isGloballyMuted($this->bob));
        $this->assertFalse(app(MuteList::class)->isMuted($this->alice, $this->carol));
    }

    public function test_passed_ignore_vote_joins_the_global_list()
    {
        event(new \Funseco\Core\SharedKernel\Events\VotePassed('ignore', $this->alice, ['target' => 'bob']));

        $this->assertTrue(app(MuteList::class)->isGloballyMuted($this->bob));
    }
}
