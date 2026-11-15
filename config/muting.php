<?php

return [
    // Chat lines kept per player and re-sent to scroll a muted player's line away
    // (the height of the in-game chat window).
    'buffer_lines' => 28,

    // Rows of the global ignore list window that get an "un-ignore" click.
    'clickable_rows' => 200,

    'messages' => [
        'muted'           => '{#server}> Player {#highlite}{1}$z$s{#server} is muted!',
        'unmuted'         => '{#server}> Player {#highlite}{1}$z$s{#server} is unmuted!',
        'already_muted'   => '{#server}> {#error}Player {#highlite}$i {1}$z$s{#error} is already in your mute list!',
        'not_muted'       => '{#server}> {#error}Player {#highlite}$i {1}$z$s{#error} is not in your mute list!',
        'cannot_mute'     => '{#server}> {#error}Cannot mute {#logina}$i {1} {#highlite}{2}$z$s{#error} !',
        'no_muted'        => '{#server}> {#error}No muted players found!',
        'ignores'         => '{#server}>> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} ignores {#highlite}{3}$z$s{#admin} !',
        'unignores'       => '{#server}> {#admin}{1}$z$s {#highlite}{2}$z$s{#admin} un-ignores {#highlite}{3}',
        'not_ignored'     => '{#server}> {#highlite}{1}{#error} is not an ignored player!',
        'no_ignored'      => '{#server}> {#error}No muted/ignored players found!',
        'cleaned'         => '{#server}> {#admin}Cleaned global mute/ignore list!',
        'id_not_found'    => '{#server}> {#error}Player_ID not found! Type {#highlite}$i/players {#error}to see all players.',
        'login_not_found' => '{#server}> {#highlite}{1} {#error}is not a valid player! Use {#highlite}$i/players {#error}to find the correct login or Player_ID.',
    ],
];
