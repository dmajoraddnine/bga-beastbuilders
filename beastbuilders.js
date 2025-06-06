/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * beastbuilders implementation : © Sunwolf Studios, Inc. info@beastbuildersgame.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * beastbuilders.js
 *
 * beastbuilders user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo",
    "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.beastbuilders", ebg.core.gamegui, {
        constructor: function(){
            console.log('beastbuilders constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;

            this.handleBuildAnimalClick = this.onSelectAnimalFromHand.bind(this);
            this.handleSelectCharacterClick = this.onSelectCharacter.bind(this);

            this.notifqueue.setIgnoreNotificationCheck('cardsDrawn', (notif) => (notif.args.player_id === this.player_id));
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function(gamedatas) {
            console.log("Starting game setup");

            console.log('gamedatas: ', gamedatas);

            // setting up play field
            this.getGameAreaElement().insertAdjacentHTML('beforeend', `
                <div id="board">
                    <div id="biome-deck"></div>
                    <div id="unassigned-characters"></div>
                    <div id="player-tables"></div>
                    <div id="current-player-hand">
                        <strong class="hand-label">Your Hand</strong>
                        <div class="content"></div>
                    </div>
                </div>
            `);
            
            // Setting up player areas
            const emptyCardPath = g_gamethemeurl + 'img/empty-card-frame-300w.png';
            Object.values(gamedatas.players).forEach(player => {
                this.getPlayerPanelElement(player.id).insertAdjacentHTML('beforeend', `
                    <div id="player-counter-${player.id}">A player counter</div>
                `);

                document.getElementById('player-tables').insertAdjacentHTML('beforeend', `
                    <div id="player-table-${player.id}" class="player-table">
                        <div class="player-beast">
                            <strong>${player.name}'s Beast</strong>
                            <div class="content">
                                <div class="beast-sections">
                                    <div class="section speed-section">
                                        <div class="empty-background image-wrapper">
                                            <strong>Speed</strong>
                                            <img src="${emptyCardPath}" />
                                        </div>
                                    </div>
                                    <div class="section threat-section">
                                        <div class="empty-background image-wrapper">
                                            <strong>Threat</strong>
                                            <img src="${emptyCardPath}" />
                                        </div>
                                    </div>
                                    <div class="section defense-section">
                                        <div class="empty-background image-wrapper">
                                            <strong>Defense</strong>
                                            <img src="${emptyCardPath}" />
                                        </div>
                                    </div>
                                </div>
                                <div id="player-mat-wrapper-${player.id}" class="player-mat-wrapper">
                                    <div class="empty-background image-wrapper">
                                        <strong>Player Character</strong>
                                        <img src="${g_gamethemeurl}img/empty-mat-frame-1000w.png" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `);

                if (player.selected_character_id) {
                    const character = Object.values(gamedatas.characters).find((c) => (c.id === player.selected_character_id));
                    this.insertPlayerMat(player.id, character.id, character.slug);
                }
            });

            // Setting up current player's hand (clickable)
            const playerHandNode = document.getElementById('current-player-hand').getElementsByClassName('content')[0];
            Object.values(gamedatas.hand || {}).forEach(card => {
                this.insertHandCard(playerHandNode, card.type_arg);
            });
            // document.querySelectorAll('#current-player-hand .animal-wrapper').forEach(
            //     element => element.addEventListener('click', this.handleBuildAnimalClick)
            // );

            // Setting up unassigned characters (clickable)
            Object.values(gamedatas.characters).forEach(character => {
                // @TODO: ADJUST NOMENCLATURE?
                document.getElementById('unassigned-characters').insertAdjacentHTML('beforeend', `
                    <div id="player-mat-${character.id}"
                        class="player-mat ${character.slug} unassigned-player-mat image-wrapper hidden"
                        data-characterid="${character.id}"
                    >
                        <img src="${g_gamethemeurl}img/player-mats/${character.slug}-1000w.png" />
                    </div>
                `);
            });
            document.querySelectorAll('#unassigned-characters .player-mat').forEach(
                element => element.addEventListener('click', this.handleSelectCharacterClick)
            );

            // Setting up biome deck
            Object.values(gamedatas.biomeDeck).forEach((biome, i) => {
                const roundID = i + 1;
                let biomeURI = `${g_gamethemeurl}img/biomes/`;
                if (biome.id) {
                    // @TODO: robustify this
                    const biomeSlug = biome.displayName.toLowerCase().replace(/\s/g, '-');
                    biomeURI += `${biomeSlug}-420w.png`;
                } else {
                    biomeURI += 'biome-back-420w.png';
                }
                document.getElementById('biome-deck').insertAdjacentHTML('beforeend', `
                    <div class="biome-wrapper">
                        <div class="biome-label">
                            Round ${roundID} Biome
                        </div>
                        <div
                            id="biome-card-${roundID}"
                            class="biome-card card-${roundID} image-wrapper"
                            data-biomeid="${biome.id}"
                        >
                            <img src="${biomeURI}" />
                        </div>
                    </div>
                `);
            });

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            this.gamedatas = gamedatas;

            console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function(stateName, args) {
            console.log('Entering state: ' + stateName, args);
            
            // const currentPlayerIsActive = this.isCurrentPlayerActive();

            switch(stateName) {
                case 'assignCharacters':
                    // if (currentPlayerIsActive) {
                        this.showUnassignedCharacters(Object.values(args.args.unassignedCharacters || {}));
                    // }
                    break;

                case 'dealCards':
                    // hide the remaining characters from assign-step (better place to do this?)
                    this.showUnassignedCharacters([]);
                    break;
                case 'playerBuildStep':
                    break;
                case 'chooseBuildAnimal':
                    this.attachBuildAnimalHandlers();
                    break;
                case 'chooseBuildSection':
                    break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function(stateName) {
            console.log('Leaving state: ' + stateName);
            
            switch(stateName) {
                /*
                    Example:
                    case 'myGameState':
                        // Hide the HTML block we are displaying only during this game state
                        dojo.style( 'my_html_block_id', 'display', 'none' );
                        break;
                */
            case 'chooseBuildAnimal':
                this.detachBuildAnimalHandlers();
                break;
            case 'dummy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function(stateName, args) {
            console.log('onUpdateActionButtons: ' + stateName, args);
                      
            if (this.isCurrentPlayerActive()) {
                switch(stateName) {
                    case 'assignCharacters':

                        // @TODO: DISPLAY CLICKABLE BUTTONS TO ACTIVE PLAYER

                        // const playableCardsIds = args.playableCardsIds; // returned by the argPlayerTurn

                        // // Add test action buttons in the action status bar, simulating a card click:
                        // playableCardsIds.forEach(
                        //     cardId => this.statusBar.addActionButton(
                        //         _('Play card with id ${card_id}').replace('${card_id}', cardId),
                        //         () => this.onCardClick(cardId)
                        //     )
                        // );

                        // this.statusBar.addActionButton(_('Pass'), () => this.bgaPerformAction("actPass"), { color: 'secondary' });
                        break;
                    case 'playerBuildStep':
                        this.statusBar.addActionButton(
                            _('Build Animal from hand'),
                            () => this.bgaPerformAction('actSelectBuild')
                        );
                        break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        insertPlayerMat: function(playerID, characterID, characterSlug) {
            const playerBeastNode = document.getElementById(`player-table-${playerID}`).getElementsByClassName('player-mat-wrapper')[0];
            playerBeastNode.insertAdjacentHTML('beforeend',
                `<div id="beast-player-mat-${playerID}"
                    class="player-mat ${characterSlug} image-wrapper"
                    data-playerid="${playerID}"
                    data-characterid="${characterID}"
                >
                    <img src="${g_gamethemeurl}img/player-mats/${characterSlug}-1000w.png" />
                </div>`
            );
        },

        insertHandCard: function(parentNode, animalID) {
            const cardAnimal = Object.values(this.gamedatas.animals).find((a) => (a.id === animalID));
            const animalFamily = Object.values(this.gamedatas.families).find((f) => (f.id === cardAnimal.family_id));

            // @TODO: robustify this
            const animalSlug = cardAnimal.display_name.toLowerCase().replace(/\s/g, '-');
            const familySlug = animalFamily.display_name.toLowerCase().replace(/\s/g, '-');

            parentNode.insertAdjacentHTML('beforeend', `
                <div id="animal-card-${animalID}" class="animal-wrapper image-wrapper" data-animalid="${animalID}">
                    <img src="${g_gamethemeurl}img/animals/${familySlug}/300w/${animalSlug}-300w.png" />
                </div>
            `);
        },

        attachBuildAnimalHandlers: function() {
            document.querySelectorAll('#current-player-hand .animal-wrapper').forEach(
                element => element.addEventListener('click', this.handleBuildAnimalClick)
            );
        },

        detachBuildAnimalHandlers: function() {
            document.querySelectorAll('#current-player-hand .animal-wrapper').forEach(
                element => element.removeEventListener('click', this.handleBuildAnimalClick)
            );
        },

        // addDiscOnBoard: async function(x, y, player) {
            //     const color = this.gamedatas.players[player].color;

            //     document.getElementById('discs').insertAdjacentHTML(
            //         'beforeend',
            //         `<div class="disc" data-color="${color}" id="disc_${x}${y}"></div>`
            //     );

            //     this.placeOnObject(`disc_${x}${y}`, 'overall_player_board_' + player);

            //     const anim = this.slideToObject(`disc_${x}${y}`, 'square_' + x + '_' + y );
            //     await this.bgaPlayDojoAnimation(anim);
        // },

        showUnassignedCharacters: function(unassignedCharacters) {
            const charactersToShow = unassignedCharacters.map((c) => c.id);

            document.querySelectorAll('#unassigned-characters .unassigned-player-mat').forEach(element => {
                if (charactersToShow.includes(element.dataset.characterid)) {
                    element.classList.remove('hidden');
                } else {
                    element.classList.add('hidden');
                }
            });

            this.addTooltipToClass('unassigned-player-mat', '', _('Select this character'));
        },


        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
            Here, you are defining methods to handle player's action (ex: results of mouse click on game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        */
        
        // Example:
        // onPlayDisc: function(evt) {
            //     // Stop this event propagation
            //     evt.preventDefault();
            //     evt.stopPropagation();

            //     // Get the clicked square x and y
            //     // Note: square id format is "square_X_Y"
            //     var coords = evt.currentTarget.id.split('_');
            //     var x = coords[1];
            //     var y = coords[2];

            //     if (!document.getElementById(`square_${x}_${y}`).classList.contains('possibleMove')) {
            //         // This is not a possible move => the click does nothing
            //         return;
            //     }

            //     this.bgaPerformAction('actPlayDisc', {
            //         x : x,
            //         y : y
            //     });
            // },
        onSelectCharacter: function (e) {
            // Stop this event propagation
            e.preventDefault();
            e.stopPropagation();

            const selectedCharacterID = e.currentTarget.dataset.characterid;
            // if (!document.getElementById(`square_${x}_${y}`).classList.contains('possibleMove')) {
            //     // This is not a possible move => the click does nothing
            //     return;
            // }

            this.bgaPerformAction('actSelectCharacter', {
                selectedCharacterID : selectedCharacterID
            });
        },

        onSelectAnimalFromHand: function (e) {
            // Stop this event propagation
            e.preventDefault();
            e.stopPropagation();

            const selectedAnimalID = e.currentTarget.dataset.animalid;

            this.bgaPerformAction('actChooseBuildAnimal', {
                selectedAnimalID : selectedAnimalID
            });
        },

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in your Game.php file.
        */
        setupNotifications: function() {
            console.log( 'notifications subscriptions setup' );

            // automatically listen to the notifications, based on the `notif_xxx` function on this class.
            this.bgaSetupPromiseNotifications();

            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
        },

        /**
         * A player just selected a character (could be any player)
         */
        notif_selectCharacter: async function(args) {
            // const unassignedCharacterNodes = document.querySelectorAll('#unassigned-characters .unassigned-player-mat');
            const character = Object.values(this.gamedatas.characters).find((c) => c.id === args.character_id);

            // do not animate if the animations aren't activated (fast replay mode)
            // if (!this.bgaAnimationsActive()) {
            //     // @TODO: TEST THIS
            //     return Promise.resolve();
            // }

            const playerMatID = `player-mat-${args.character_id}`;
            const matWrapperID = `player-mat-wrapper-${args.player_id}`;
            const playerMatNode = document.getElementById(playerMatID);

            // remove old click handler & slide to new position
            playerMatNode.removeEventListener('click', this.handleSelectCharacterClick);
            playerMatNode.classList.remove('unassigned-player-mat');
            const anim = this.slideToObject(playerMatID, matWrapperID);
            await this.bgaPlayDojoAnimation(anim);

            // move clicked element to new DOM parent & clear animation styles
            document.getElementById(matWrapperID).appendChild(playerMatNode);
            playerMatNode.style.top = 0;
            playerMatNode.style.left = 0;
        },

        notif_newHand: async function(args) {
            const currentPlayerID = this.gamedatas.currentPlayerID;

            // do not animate if the animations aren't activated (fast replay mode)
            // if (!this.bgaAnimationsActive()) {
            //     // @TODO: TEST THIS
            //     return Promise.resolve();
            // }

            const playerBoardNode = document.getElementById(`overall_player_board_${currentPlayerID}`);
            const playerHandNode = document.getElementById('current-player-hand').getElementsByClassName('content')[0];

            args.newCards.forEach(async (c, i) => {
                // insert new card and slide to new position
                const animalCardID = `animal-card-${c.type_arg}`;
                this.insertHandCard(playerBoardNode, c.type_arg);

                const anim = this.slideToObject(animalCardID, 'current-player-hand', 500, i * 250);
                await this.bgaPlayDojoAnimation(anim);

                // move clicked element to new DOM parent & clear animation styles
                const animalCardNode = document.getElementById(animalCardID);
                playerHandNode.appendChild(animalCardNode);
                animalCardNode.style.top = 0;
                animalCardNode.style.left = 0;
            });
        },

        // notif_playDisc: async function(args) {
        //     // Remove current possible moves (makes the board more clear)
        //     document.querySelectorAll('.possibleMove').forEach(div => div.classList.remove('possibleMove'));

        //     await this.addDiscOnBoard(args.x, args.y, args.player_id);
        // },

        // notif_turnOverDiscs: async function(args) {
        //     // Get the color of the player who is returning the discs
        //     const targetColor = this.gamedatas.players[args.player_id].color;

        //     // wait for the animations of all turned discs to be over before considering the notif done
        //     await Promise.all(
        //         args.turnedOver.map(disc => this.animateTurnOverDisc(disc, targetColor))
        //     );
        // },

        // animateTurnOverDisc: async function(disc, targetColor) {
        //     const discDiv = document.getElementById(`disc_${disc.x}${disc.y}`);

        //     if (!this.bgaAnimationsActive()) {
        //         // do not play animations if the animations aren't activated (fast replay mode)
        //         discDiv.dataset.color = targetColor;
        //         return Promise.resolve();
        //     }

        //     // Make the disc blink 2 times
        //     const anim = dojo.fx.chain( [
        //         dojo.fadeOut( {
        //                         node: discDiv,
        //                         onEnd: () => discDiv.dataset.color = targetColor,
        //                     } ),
        //         dojo.fadeIn( { node: discDiv } )
        //     ] ); // end of dojo.fx.chain

        //     await this.bgaPlayDojoAnimation(anim);
        // },

        notif_newScores: async function(args) {
            for (const player_id in args.scores) {
                const newScore = args.scores[ player_id ];
                this.scoreCtrl[player_id].toValue(newScore);
            }
        }
   });             
});
