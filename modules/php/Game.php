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
 * Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 */
declare(strict_types=1);

namespace Bga\Games\beastbuilders;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");
require_once(APP_GAMEMODULE_PATH . "module/common/deck.game.php");


class Game extends \Table {
    // private static array $CARD_TYPES;
    protected $animalCards;

    /**
     * Your global variables labels:
     *
     * Here, you can assign labels to global variables you are using for this game. You can use any number of global
     * variables with IDs between 10 and 99. If your game has options (variants), you also have to associate here a
     * label to the corresponding ID in `gameoptions.inc.php`.
     *
     * NOTE: afterward, you can get/set the global variables with `getGameStateValue`, `setGameStateInitialValue` or
     * `setGameStateValue` functions.
     */
    public function __construct() {
        parent::__construct();

        $this->initGameStateLabels([
            "current_round" => 10,
            "final_round" => 11
            // "characters" => []
            // "my_first_game_variant" => 100,
            // "my_second_game_variant" => 101,
        ]);        

        $this->animalCards = self::getNew("module.common.deck");
        $this->animalCards->init("animal_deck");

        $this->biomeCards = self::getNew("module.common.deck");
        $this->biomeCards->init("biome_deck");

        $this->dump('[constructor] $this->animalCards: ', $this->animalCards);
        $this->dump('[constructor] $this->biomeCards: ', $this->biomeCards);

        // self::$CARD_TYPES = [
            //     1 => [
            //         "card_name" => clienttranslate('Troll'), // ...
            //     ],
            //     2 => [
            //         "card_name" => clienttranslate('Goblin'), // ...
            //     ],
            //     // ...
            // ];

        /* example of notification decorator.
        // automatically complete notification args when needed
        $this->notify->addDecorator(function(string $message, array $args) {
            if (isset($args['player_id']) && !isset($args['player_name']) && str_contains($message, '${player_name}')) {
                $args['player_name'] = $this->getPlayerNameById($args['player_id']);
            }
        
            if (isset($args['card_id']) && !isset($args['card_name']) && str_contains($message, '${card_name}')) {
                $args['card_name'] = self::$CARD_TYPES[$args['card_id']]['card_name'];
                $args['i18n'][] = ['card_name'];
            }
            
            return $args;
        });*/
    }

    /**
     * This method is called only once, when a new game is launched. In this method, you must setup the game
     *  according to the game rules, so that the game is ready to be played.
     */
    protected function setupNewGame($players, $options = []) {
        // Set the colors of the players with HTML color code. The default below is red/green/blue/orange/brown. The
        // number of colors defined here must correspond to the maximum number of players allowed for the gams.
        $gameinfos = $this->getGameinfos();
        $default_colors = ["ff0000", "008000", "0000ff", "ffa500", "773300", "6a329f"];

        $player_ids = array_keys($players);
        shuffle($player_ids);
        foreach ($player_ids as $player_id) {
            $player = $players[$player_id];
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        // Create players based on generic information.
        //
        // NOTE: You can add extra field on player table in the database (see dbmodel.sql) and initialize
        // additional fields directly here.
        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        // $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        // Init global values with their initial values.
        $this->setGameStateInitialValue("current_round", 0); // this is incremented in stDealCards state
        $this->setGameStateInitialValue("final_round", 5);

        // create the characters
        // @TODO: READ THIS FROM JSON FILE
        $character_sql = [];
        $character_sql[] = "('Vortek Pulsaron', 'vortek', 'A sentient A.I. that illegally accessed the Beast Builders\' software system. After being discovered, it was invited to participate in the game, causing controversy.')";
        $character_sql[] = "('Ko Minna', 'ko', 'Ko wants to be crystal clear about one thing: She\'s here to become the galaxy\'s next top Beast Builder, and there\'s nothing you can do about it!')";
        $character_sql[] = "('Zychro', 'zychro', 'A sprightly upstart with a lot to prove. Some Beast Builders spend time studying strategy or \"the meta\", but Zychro plays from pure instinct.')";
        $character_sql[] = "('E.T.H. 1', 'eth1', 'An automaton crafted by a tiny, cute architect who rides in its center. Together, they run millions of quantum calculations to decide the best possible move.')";
        $character_sql[] = "('Ta\'aq', 'taaq', 'A deadly warrior with a mysterious background, who doesn\'t talk much. Rumor has it he\'s in exile from his home world.')";
        $character_sql[] = "('Atomizer Gamma', 'atomizer', 'The third offspring in a line of talented yet ruthless Beast Builders. Atomizer\'s only priority is winning, making them effective in battle, but less so at making friends.')";
        static::DbQuery(
            sprintf(
                "INSERT INTO `character` (`display_name`, `slug`, `flavor_text`) VALUES %s",
                implode(",", $character_sql)
            )
        );

        // create the families
        $family_sql = ["(1, 'Bird')", "(2, 'Fish')", "(3, 'Herptile')", "(4, 'Invertebrate')", "(5, 'Mammal')"];
        static::DbQuery(
            sprintf(
                "INSERT INTO `family` (`id`, `display_name`) VALUES %s",
                implode(",", $family_sql)
            )
        );

        // create the biomes
        // @TODO: READ THIS FROM JSON FILE
        $biome_sql = array();
        $biome_sql[] = "(1, 'Plateau', '1')";
        $biome_sql[] = "(2, 'Open Ocean', '2')";
        $biome_sql[] = "(3, 'Lake', '3')";
        $biome_sql[] = "(4, 'Cave', '4')";
        $biome_sql[] = "(5, 'Jungle', '5')";
        static::DbQuery(
            sprintf(
                "INSERT INTO `biome` (`id`, `display_name`, `basic_buff_family_id`) VALUES %s",
                implode(",", $biome_sql)
            )
        );

        // create the animals
        // @TODO: READ THIS FROM JSON FILE
        $animal_sql = array();
        $animal_sql[] = "(53, 'Giant Panda', 5)";
        $animal_sql[] = "(36, 'Poison Dart Frog', 3)";
        static::DbQuery(
            sprintf(
                "INSERT INTO `animal` (`id`, `display_name`, `family_id`) VALUES %s",
                implode(",", $animal_sql)
            )
        );


        // Create card objects
        $animal_cards = [];
        foreach ($this->getAnimals() as $animal_id => $animal) {
            $animal_cards[] = ['type' => 'animal', 'type_arg' => $animal_id, 'nbr' => 1];
        }
        $this->animalCards->createCards($animal_cards, 'deck');

        // $biome_cards = [];
        // foreach ($this->getBiomes() as $biome_id => $biome) {
        //     $biome_cards[] = ['type' => 'biome', 'type_arg' => $biome_id, 'nbr' => 1];
        // }
        // $this->biomeCards->createCards($biome_cards, 'deck');
        // $this->biomeCards->shuffle('deck');

        $this->dump('[setupNewGame] $this->animalCards: ', $this->animalCards);
        // $this->dump('[setupNewGame] $this->biomeCards: ', $this->biomeCards);

        // Activate first player once everything has been initialized and ready.
        $this->gamestate->changeActivePlayer($player_ids[0]);
    }

    /**
     * Game state arguments
     * -- This method returns some additional information that is very specific to the `assignCharacters` game state.
     *
     * @return array
     * @see ./states.inc.php
     */
    public function argAssignCharacters(): array {
        // Get some values from the current game situation from the database.
        return [
            // 'possibleMoves' => $this->getPossibleMoves( intval($this->getActivePlayerId()) )
            'unassignedCharacters' => $this->getUnassignedCharacters()
        ];
    }

    public function argBuildPhase(): array {
        // Get some values from the current game situation from the database.
        return [
            // 'beasts' => $this->getBeasts()
        ];
    }

    /**
     * Player action, example content.
     *
     * In this scenario, each time a player plays a card, this method will be called. This method is called directly
     * by the action trigger on the front side with `bgaPerformAction`.
     *
     * @throws BgaUserException
     */
    // function actPlayDisc(int $x, int $y) {
        //     $playerID = intval($this->getActivePlayerId());

        //     // Now, check if this is a possible move
        //     $board = $this->getBoardPieces();
        //     $turnedOverDiscs = $this->getTurnedOverDiscs($board, $x, $y, $playerID);

        //     if (count($turnedOverDiscs) > 0) {
        //         // valid move
        //         // Let's place a disc at x,y and return all "$returned" discs to the active player
        //         $sql = "UPDATE board SET board_player='$playerID'
        //                 WHERE ( board_x, board_y) IN ( ";

        //         foreach( $turnedOverDiscs as $turnedOver ) {
        //             $sql .= "('".$turnedOver['x']."','".$turnedOver['y']."'),";
        //         }
        //         $sql .= "('$x','$y') ) ";

        //         $this->DbQuery($sql);

        //         // Update scores according to the number of disc on board
        //         $scoreSql = "UPDATE player
        //                 SET player_score = (
        //                 SELECT COUNT( board_x ) FROM board WHERE board_player=player_id
        //                 )";
        //         $this->DbQuery($scoreSql);

        //         // Statistics
        //         $this->incStat(count($turnedOverDiscs), "turnedOver", $playerID);
        //         if (($x==1 && $y==1) || ($x==8 && $y==1) || ($x==1 && $y==8) || ($x==8 && $y==8) ) {
        //             $this->incStat(1, 'discPlayedOnCorner', $playerID);
        //         } else if ($x==1 || $x==8 || $y==1 || $y==8) {
        //             $this->incStat(1, 'discPlayedOnBorder', $playerID);
        //         } else if ($x>=3 && $x<=6 && $y>=3 && $y<=6) {
        //             $this->incStat(1, 'discPlayedOnCenter', $playerID);
        //         }

        //         // Notify
        //         $this->notify->all("playDisc", clienttranslate( '${player_name} plays a disc and turns over ${returned_nbr} disc(s)' ), array(
        //             'player_id' => $playerID,
        //             'player_name' => $this->getActivePlayerName(),
        //             'returned_nbr' => count($turnedOverDiscs),
        //             'x' => $x,
        //             'y' => $y
        //         ));

        //         $this->notify->all("turnOverDiscs", '', array(
        //             'player_id' => $playerID,
        //             'turnedOver' => $turnedOverDiscs
        //         ));

        //         $newScores = $this->getCollectionFromDb( "SELECT player_id, player_score FROM player", true );
        //         $this->notify->all("newScores", "", array(
        //             "scores" => $newScores
        //         ));

        //         // Then, go to the next state
        //         $this->gamestate->nextState('nextPlayer');
        //     } else {
        //         throw new \BgaSystemException("Impossible move");
        //     }
    // }

    function actSelectCharacter(string $selectedCharacterID) {
        // framework already determined if the player that sent this action is an active player
        $playerID = intval($this->getActivePlayerId());

        $unassignedCharacters = $this->getUnassignedCharacters();

        $this->dump('$selectedCharacterID', $selectedCharacterID);
        $this->dump('$unassignedCharacters', $unassignedCharacters);

        $unassignedIDs = array_map(
            function ($c) { return $c['id']; },
            $unassignedCharacters
        );

        $this->dump('$unassignedIDs', $unassignedIDs);

        if (in_array($selectedCharacterID, $unassignedIDs)) {
            // valid move

            // update player table to have character ID
            $player_write = "UPDATE player SET selected_character_id='$selectedCharacterID' WHERE (player_id) IN ('$playerID');";
            $this->DbQuery($player_write);

            // notify
            $selectedChar = array_filter(
                $unassignedCharacters,
                function ($c) use ($selectedCharacterID) { return $c['id'] === $selectedCharacterID; }
            );

            $this->dump('$selectedChar', $selectedChar);

            $this->notify->all('selectCharacter', clienttranslate('${player_name} chooses ${character_display_name}'), array(
                'player_name'  => $this->getActivePlayerName(),
                'player_id'    => $playerID,
                'character_id' => $selectedCharacterID,
                'character_display_name' => array_shift($selectedChar)['display_name']
            ));

            // $this->notify->all("turnOverDiscs", '', array(
            //     'player_id' => $playerID,
            //     'turnedOver' => $turnedOverDiscs
            // ));

            $this->gamestate->nextState('characterSelected');
        } else {
            throw new \BgaSystemException("Impossible move");
        }
    }

    /**
     * 1: speed-behavior
     * 2: threat
     * 3: defense
     */
    function actBuildCardFromHand(int $selectedCardID, int $cardNewSection) {
        // framework already determined if the player that sent this action is an active player
        $playerID = intval($this->getCurrentPlayerId());

        // update DB for this player to indicate which location & location-arg for this card

        // notify current player

        // notify all players

        // function actPlayKeep($cardId) {
        //    $this->checkAction('actPlayKeep');
        //    $player_id = $this->getCurrentPlayerId(); // CURRENT!!! not active
        //    ... // some logic here
        //    $this->gamestate->setPlayerNonMultiactive($player_id, 'next'); // deactivate player; if none left, transition to 'next' state
        // }


    }

    // public function actPass(): void
    // {
    //     // Retrieve the active player ID.
    //     $player_id = (int)$this->getActivePlayerId();

    //     // Notify all players about the choice to pass.
    //     $this->notify->all("pass", clienttranslate('${player_name} passes'), [
    //         "player_id" => $player_id,
    //         "player_name" => $this->getActivePlayerName(), // remove this line if you uncomment notification decorator
    //     ]);

    //     // at the end of the action, move to the next state
    //     $this->gamestate->nextState("pass");
    // }

    // return array of disc coords. that would be turned over if a new disc placed at (x, y) location for given player
    public function getTurnedOverDiscs($board, $x, $y, $playerID): array {
        $MOVE_DIRECTIONS = [
            [-1, 0],
            [-1, 1],
            [0, 1],
            [1, 1],
            [1, 0],
            [1, -1],
            [0, -1],
            [-1, -1]
        ];

        // does this space have a disc already?
        if (
            array_key_exists($x, $board) &&
            array_key_exists($y, $board[$x]) &&
            $board[$x][$y]
        ) {
            return [];
        }

        $results = [];

        // count the number of opposing discs between this disc and the next allied disc (check all 8 directions)
        foreach ($MOVE_DIRECTIONS as $vector) {
            $xDelta = $vector[0];
            $yDelta = $vector[1];

            $flipped = [];
            $foundAllied = false;

            if ($yDelta == 0) {
                // single loop for $yDelta=0 edge case
                for ($i = $x + $xDelta; 1 <= $i && $i <= 8; $i += $xDelta) {
                    // try
                    $playerIDToCheck = (
                        array_key_exists($i, $board) && array_key_exists($y, $board[$i])
                            ? $board[$i][$y]
                            : null
                    );
                    if ($playerIDToCheck) {
                        if ($playerIDToCheck == $playerID) {
                            $foundAllied = true;
                            break;
                        } else {
                            $flipped[] = ["x" => $i, "y" => $y];
                        }
                    } else {
                        // no piece on this square - invalid move
                        break;
                    }
                    // catch
                }
            } else if ($xDelta == 0) {
                for ($j = $y + $yDelta; 1 <= $j && $j <= 8; $j += $yDelta) {
                    // try
                    $playerIDToCheck = (
                        array_key_exists($x, $board) && array_key_exists($j, $board[$x])
                            ? $board[$x][$j]
                            : null
                    );
                    if ($playerIDToCheck) {
                        if ($playerIDToCheck == $playerID) {
                            $foundAllied = true;
                            break;
                        } else {
                            $flipped[] = ["x" => $x, "y" => $j];
                        }
                    } else {
                        // no piece on this square - invalid move
                        break;
                    }
                    // catch
                }
            } else {
                $i = $x + $xDelta;
                $j = $y + $yDelta;
                do {
                    // try
                    $playerIDToCheck = (
                        array_key_exists($i, $board) && array_key_exists($j, $board[$i])
                            ? $board[$i][$j]
                            : null
                    );
                    if ($playerIDToCheck) {
                        if ($playerIDToCheck == $playerID) {
                            $foundAllied = true;
                            break;
                        } else {
                            $flipped[] = ["x" => $i, "y" => $j];
                        }
                    } else {
                        // no piece on this square - invalid move
                        break;
                    }
                    // catch

                    $i += $xDelta;
                    $j += $yDelta;
                } while ((1 <= $i && $i <= 8 && 1 <= $j && $j <= 8) && !$foundAllied);
            }

            if ($foundAllied && count($flipped) > 0) {
                $results = array_merge($results, $flipped);
            }
        }

        return $results;
    }

    /*
        [
            x1 => [
                y1 => flipCount1,
                y2 => flipCount2
            ],
            x2 => [
                y1 => flipCount4,
                y2 => flipCount5
            ],
            x3 => [
                y3 => flipCount3
            ],
            ...
        ]
    */
    // public function getPossibleMoves($playerID): array {
        //     $MOVE_DIRECTIONS = [
        //         [-1, 0],
        //         [-1, 1],
        //         [0, 1],
        //         [1, 1],
        //         [1, 0],
        //         [1, -1],
        //         [0, -1],
        //         [-1, -1]
        //     ];
        //     $resultMoves = [];

        //     $board = $this->getBoardPieces();

        //     for ($x = 1; $x < 9; $x++) {
        //         for ($y = 1; $y < 9; $y++) {
        //             $flippedDiscs = $this->getTurnedOverDiscs($board, $x, $y, $playerID);
        //             if (count($flippedDiscs) > 0) {
        //                 if (!array_key_exists($x, $resultMoves)) {
        //                     $resultMoves[$x] = array();
        //                 }
        //                 // add possible move for specified player to result-moves set
        //                 $resultMoves[$x][$y] = $flippedDiscs;
        //             }
        //         }
        //     }

        //     return $resultMoves;
    // }






    /**
     * Game state action, example content.
     *
     * The action method of state `nextPlayer` is called everytime the current game state is set to `nextPlayer`.
     */
    /*
    function stNextPlayer(): void {
        // Make next player in the order the active player
        $next_player_id = intval($this->activeNextPlayer());

        $this->debug("[stNextPlayer] entered nextPlayer state");
        $this->dump('new active player ID: ', $next_player_id);

        // Check if both player has at least 1 discs, and if there are free squares to play
        $player_to_discs = $this->getCollectionFromDb( "SELECT board_player, COUNT( board_x )
                                                       FROM board
                                                       GROUP BY board_player", true );

        if (!isset($player_to_discs[''])) {
            // empty-string index not present => there's no more free place on the board !
            // => end of the game
            $this->gamestate->nextState('endGame');
            return;
        } else if (!isset($player_to_discs[$next_player_id])) {
            // Active player has no more disc on the board => he loses immediately
            $this->gamestate->nextState('endGame');
            return;
        }

        // Can this player play?
        $possibleMoves = $this->getPossibleMoves($next_player_id);

        if (count($possibleMoves) == 0) {
            // This player can't play
            // Can his opponent play ?
            $opponent_id = (int)$this->getUniqueValueFromDb("SELECT player_id FROM player WHERE player_id != '$next_player_id' ");
            $opponentPossibleMoves = $this->getPossibleMoves($opponent_id);
            // var_dump('$opponentPossibleMoves: ', $opponentPossibleMoves);
            if (count($opponentPossibleMoves) == 0) {
                // Nobody can move => end of the game
                $this->gamestate->nextState('endGame');
            } else {
                // => pass his turn
                $next_player_id = intval($this->activeNextPlayer());
                $this->gamestate->nextState('playerTurn');
            }
        } else {
            // This player can play. Give him some extra time
            $this->giveExtraTime($next_player_id);
            $this->gamestate->nextState('playerTurn');
        }
    }
    */

    function stAssignmentCheck(): void {
        // check if all the players have selected characters
        $player_read = "SELECT player_id FROM player WHERE selected_character_id IS NULL";
        $players = $this->getObjectListFromDB($player_read);

        if (!count($players)) {
            $this->gamestate->nextState('allPlayersChosen');
        } else {
            // Make next player in the order the active player
            $next_player_id = intval($this->activeNextPlayer());
            $this->gamestate->nextState('charactersRemaining');
        }
    }

    function stDealCards(): void {
        // shuffle the Animal Deck, and deal 6 Animal Cards to each player
        $this->animalCards->shuffle('deck');
        $qty = 1;

        $players = $this->getPlayers();
        foreach ($players as $player_id => $player) {
            $this->animalCards->pickCards($qty, 'deck', $player_id);

            // Notify player about their cards
            // $this->notify->all($player_id, 'cardsDrawn', '', ['qty' => 1]);

            // notify all players about new cards drawn
            $player_name = $player["player_name"];
            $this->notify->all('cardsDrawn', clienttranslate('${player_name} draws ${qty} Animal Card' . ($qty > 1 ? 's' : '') . ' from the deck'), array(
                "player_id" => $player_id,
                "player_name" => $player_name,
                "qty" => 1
            ));
        }

        // advance the round counter
        $this->setGameStateValue('current_round', (int)$this->getGameStateValue('current_round') + 1);

        $this->gamestate->nextState('handsDealt');
    }

    function stActivateBiome(): void {
        $currentRound = $this->getGameStateValue('current_round');

        // $this->dump('[stActivateBiome] $currentRound: ', $currentRound);

        if ($currentRound === 1) {
            $biome_cards = [];
            foreach ($this->getBiomes() as $biome_id => $biome) {
                $biome_cards[] = ['type' => 'biome', 'type_arg' => $biome_id, 'nbr' => 1];
            }
            $this->biomeCards->createCards($biome_cards, 'deck');
            $this->biomeCards->shuffle('deck');
        }

        // notify all players about new biome
        $newBiomeDeck = $this->getCurrentBiomeDeck();

        // $this->dump('[stActivateBiome] $newBiomeDeck: ', $newBiomeDeck);

        $activeBiomeCard = $newBiomeDeck[$currentRound - 1];
        $this->notify->all("biomeActivated", clienttranslate('The new Active Biome is ${biomeName}'), array(
            'biomeName'    => $activeBiomeCard['displayName']
        ));


        // @TODO: PROVIDE NEW BIOME DECK IN STATE ARGUMENT?


        $this->gamestate->nextState('biomeActivated');
    }

    function stBuildPhase(): void {
        $this->gamestate->setAllPlayersMultiactive();
    }

    /**
     * Migrate database.
     *
     * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
     * method is called everytime the system detects a game running with your old database scheme. In this case, if you
     * change your database scheme, you just have to apply the needed changes in order to update the game database and
     * allow the game to continue to run with your new version.
     *
     * @param int $from_version
     * @return void
     */
    public function upgradeTableDb($from_version) {
        //       if ($from_version <= 1404301345)
        //       {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //       }
        //
        //       if ($from_version <= 1405061421)
        //       {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //       }
    }

    /**
     * Compute and return the current game progression.
     *
     * The number returned must be an integer between 0 and 100.
     *
     * This method is called each time we are in a game state with the "updateGameProgression" property set to true.
     *
     * @return int
     * @see ./states.inc.php
     */
    public function getGameProgression() {
        return 0;
    }

    public function getUnassignedCharacters() {
        $result = [];
        $characters = $this->getCharacters();
        $players = $this->getPlayers();

        foreach ($players as $player_id => $player) {
            if ($player) {
                $selected_char = $player['selected_character_id'];
                if ($selected_char) {
                    unset($characters[$selected_char]);
                }
            }
        }

        return $characters;
    }

    public function getCurrentBiomeDeck() {
        $biomeDeck = [];
        $allBiomes = $this->getBiomes();

        $visibleBiomeCards = $this->biomeCards->getCardsOnTop($this->getGameStateValue('current_round'), 'deck');
        // $this->dump('[getCurrentBiomeDeck] $visibleBiomeCards: ', $visibleBiomeCards);

        for ($i = 0; $i < $this->getGameStateValue('final_round'); $i++) {
            if (array_key_exists($i, $visibleBiomeCards)) {
                $bc = $visibleBiomeCards[$i];
                array_push($biomeDeck, [
                    'id'                => $bc['type_arg'],
                    'displayName'       => $allBiomes[$bc['type_arg']]['display_name'],
                    'basicBuffFamilyID' => $allBiomes[$bc['type_arg']]['basic_buff_family_id']
                ]);
            } else {
                // push a dummy card
                array_push($biomeDeck, [
                    'id'                => null,
                    'displayName'       => null,
                    'basicBuffFamilyID' => null
                ]);
            }
        }

        $this->dump('[getCurrentBiomeDeck] $biomeDeck: ', $biomeDeck);

        return $biomeDeck;
    }

    public function getCharacters() {
        // @TODO: avoid extra DB calls and cache the result somewhere?
        $characters = $this->getCollectionFromDb(
            "SELECT `id`, `display_name`, `slug`, `flavor_text` FROM `character`"
        );

        return $characters;
    }

    public function getPlayers() {
        // this could change more often than characters/animals
        $players = $this->getCollectionFromDb(
            "SELECT `player_id` `id`, `player_score` `score`, `player_color` `color`, `selected_character_id` `selected_character_id`, `player_name` `player_name` FROM `player`"
        );

        $this->dump('$players: ', $players);

        return $players;
    }

    public function getAnimals() {
        $animals = $this->getCollectionFromDb(
            "SELECT `id`, `display_name`, `family_id` FROM `animal`"
        );

        $this->dump('$animals: ', $animals);

        return $animals;
    }

    public function getBiomes() {
        $biomes = $this->getCollectionFromDb(
            "SELECT `id`, `display_name`, `basic_buff_family_id` FROM `biome`"
        );

        // $this->dump('$biomes: ', $biomes);

        return $biomes;
    }

    public function getFamilies() {
        $families = $this->getCollectionFromDb(
            "SELECT `id`, `display_name` FROM `family`"
        );

        $this->dump('$families: ', $families);

        return $families;
    }

    /*
     * Gather all information about current game situation (visible by the current player).
     *
     * The method is called each time the game interface is displayed to a player, i.e.:
     *
     * - when the game starts
     * - when a player refreshes the game page (F5)
     */
    protected function getAllDatas(): array {
        $result = [];

        // WARNING: We must only return information visible by the current player.
        $current_player_id = (int) $this->getCurrentPlayerId();

        $result["players"] = $this->getPlayers();
        $result["characters"] = $this->getCharacters();
        $result["animals"] = $this->getAnimals();
        // $result["biomes"] = $this->getBiomes();

        // $this->dump('[getAllDatas] $this->animalCards: ', $this->animalCards);
        // $this->dump('[getAllDatas] $current_player_id: ', $current_player_id);

        // Cards in current player hand
        $result["hand"] = $this->animalCards->getCardsInLocation('hand', $current_player_id);

        // Currently-visible biome deck
        $result["biomeDeck"] = $this->getCurrentBiomeDeck();

        // Cards played on beasts
        // $result["cardsontable"] = $this->cards->getCardsInLocation( 'cardsontable' );

        $this->dump('[getAllDatas] $result: ', $result);


        // $cards = $this->getCollectionFromDb(
        //     "SELECT card_id, card_type, card_type_arg, card_location, card_location_arg FROM biome_deck"
        // );
        // $this->dump('$cards: ', $cards);


        return $result;
    }

    /**
     * Returns the game name.
     *
     * IMPORTANT: Please do not modify.
     */
    protected function getGameName() {
        return "beastbuilders";
    }

    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: pass).
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`, otherwise it will fail with a
     * "Not logged" error message.
     *
     * @param array{ type: string, name: string } $state
     * @param int $active_player
     * @return void
     * @throws feException if the zombie mode is not supported at this game state.
     */
    protected function zombieTurn(array $state, int $active_player): void
    {
        $state_name = $state["name"];

        if ($state["type"] === "activeplayer") {
            switch ($state_name) {
                default:
                {
                    $this->gamestate->nextState("zombiePass");
                    break;
                }
            }

            return;
        }

        // Make sure player is in a non-blocking status for role turn.
        if ($state["type"] === "multipleactiveplayer") {
            $this->gamestate->setPlayerNonMultiactive($active_player, '');
            return;
        }

        throw new \feException("Zombie mode not supported at this game state: \"{$state_name}\".");
    }
}
