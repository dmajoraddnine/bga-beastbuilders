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
            "biomeActivated" => ST_BEGIN_BUILD_PHASE
        ]
    ],

    ST_BEGIN_BUILD_PHASE => [
        "name" => "beginBuildPhase",
        "type" => "multipleactiveplayer",
        "description" => clienttranslate('Other players must build their Beast'),
        "descriptionmyturn" => "N/A",
        "initialprivate" => ST_PLAYER_BUILD_STEP,
        // "args" => "argBuildPhase",
        "action" => "stBeginBuildPhase",
        "possibleactions" => [
            // @TODO: allow player to change their mind after passing?
        ],
        "transitions" => [
            // "chooseSectionToBuild" => ST_CHOOSE_SECTION_TO_BUILD,
            // "confirmBeast" => ST_END_BUILD_PHASE,
            "allBeastsBuilt" => ST_PLAYER_TURN
            // "zombiePass" => ST_NEXT_PLAYER
        ]
    ],

    ST_PLAYER_BUILD_STEP => [
        "name" => "playerBuildStep",
        "type" => "private",
        "description" => "N/A",
        "descriptionmyturn" => clienttranslate('${you} must build your Beast'),
        // "args" => "argPlayerBuildStep",
        "action" => "stPlayerBuildStep",
        "possibleactions" => [
            "actSelectBuild"
            // "actSelectSwap",
            // "actFinalizeBeast"
        ],
        "transitions" => [
            "chooseBuildAnimal" => ST_CHOOSE_BUILD_ANIMAL,
            // "chooseSwapAnimals" => ST_CHOOSE_SWAP_ANIMALS
            "finalizeBeast" => ST_END_BUILD_PHASE
            // "zombiePass" => ST_NEXT_PLAYER
        ]
    ],

    ST_CHOOSE_BUILD_ANIMAL => [
        "name" => "chooseBuildAnimal",
        "descriptionmyturn" => clienttranslate('Choose an Animal from your hand'),
        "type" => "private",
        // "args" => "argChooseBuildAnimal",
        // "action" => "stChooseBuildAnimal",
        "possibleactions" => [
            "actChooseBuildAnimal"
            // "actBack"
        ],
        "transitions" => [
            "chooseBuildSection" => ST_CHOOSE_BUILD_SECTION
        ]
    ],

    ST_CHOOSE_BUILD_SECTION => [
        "name" => "chooseBuildSection",
        "descriptionmyturn" => clienttranslate('Choose a section of your Beast to attach new Animal'),
        "type" => "private",
        "args" => "argChooseBuildSection",
        // "action" => "stChooseBuildSection",
        "possibleactions" => [
            "actChooseBuildSection"
            // "actBack"
        ],
        "transitions" => [
            "playerBuildStep" => ST_PLAYER_BUILD_STEP
        ]
    ],

    ST_END_BUILD_PHASE => [

    ],

    ST_PLAYER_TURN => [

    ],

    // Final state. Please do not modify (and do not overload action/args methods).
    ST_END_GAME => [
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    ],

];



