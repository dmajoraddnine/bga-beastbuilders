<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * beastbuilders implementation : © Sunwolf Studios, Inc. info@beastbuildersgame.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * states.inc.php
 *
 * beastbuilders game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: $this->checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

require_once('modules/php/constants.inc.php');

$machinestates = [

    // The initial state. Please do not modify.
    ST_BGA_GAME_SETUP => array(
        "name" => "gameSetup",
        "description" => clienttranslate('Game setup'),
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => [
            "" => ST_ASSIGN_CHARACTERS
        ]
    ),

    ST_ASSIGN_CHARACTERS => [
        "name" => "assignCharacters",
        "description" => clienttranslate('${actplayer} must choose a player character'),
        "descriptionmyturn" => clienttranslate('${you} must choose a player character'),
        "type" => "activeplayer",
        "args" => "argAssignCharacters",
        "possibleactions" => [
            // these actions are called from the front with bgaPerformAction, and matched to the function on the game.php file
            "actSelectCharacter"
        ],
        "transitions" => [
            "characterSelected" => ST_CHARACTER_CHECK
        ]
    ],

    ST_CHARACTER_CHECK => [
        "name" => "assignmentCheck",
        "type" => "game",
        "action" => "stAssignmentCheck",
        // "updateGameProgression" => true,
        "transitions" => [
            "charactersRemaining" => ST_ASSIGN_CHARACTERS,
            "allPlayersChosen" => ST_DEAL_CARDS
        ]
    ],

    ST_DEAL_CARDS => [
        "name" => "dealCards",
        "type" => "game",
        "action" => "stDealCards",
        "updateGameProgression" => true,
        "transitions" => [
            // "playerSelection" => ST_CHOOSE_CARDS,
            "handsDealt" => ST_ACTIVATE_BIOME
        ]
    ],

    ST_ACTIVATE_BIOME => [
        "name" => "activateBiome",
        "type" => "game",
        "action" => "stActivateBiome",
        "updateGameProgression" => true,
        "transitions" => [
            // "playerSelection" => ST_CHOOSE_BIOME,
            "biomeActivated" => ST_BUILD_PHASE
        ]
    ],

    ST_BUILD_PHASE => [
        "name" => "buildPhase",
        "type" => "multipleactiveplayer",
        "action" => "stBuildPhase",
        "args" => "argBuildPhase",
        "description" => clienttranslate('Other players must build their Beast'),
        "descriptionmyturn" => clienttranslate('${you} must build your Beast'),
        "possibleactions" => [
            // these actions are called from the front with bgaPerformAction, and matched to the function on the game.php file
            "actBuildCardFromHand",
            // "actSwapCardsInBeast",
            // "actFinalizeBeast"
        ],
        "transitions" => [
            "beastsRemaining" => ST_BUILD_PHASE,
            // "allBeastsBuilt" => ST_PLAYER_TURN,
            // "zombiePass" => ST_NEXT_PLAYER
        ]
    ],

    // ST_PLAYER_TURN => [
    //     "name" => "playerTurn",
    //     "description" => clienttranslate('${actplayer} must play a disc'),
    //     "descriptionmyturn" => clienttranslate('${you} must play a disc'),
    //     "type" => "activeplayer",
    //     "args" => "argPlayerTurn",
    //     "possibleactions" => [
    //         // these actions are called from the front with bgaPerformAction, and matched to the function on the game.php file
    //         "actPlayDisc"
    //     ],
    //     "transitions" => [
    //         "nextPlayer" => ST_NEXT_PLAYER,
    //         "zombiePass" => ST_NEXT_PLAYER
    //     ]
    // ],

    // Final state. Please do not modify (and do not overload action/args methods).
    ST_END_GAME => [
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    ],

];



