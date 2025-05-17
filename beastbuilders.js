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
                    <div id="unassigned-characters"></div>
                    <div id="player-tables"></div>
                    <div id="current-player-hand"></div>
                </div>
            `);
            
            // Setting up player areas
            Object.values(gamedatas.players).forEach(player => {
                this.getPlayerPanelElement(player.id).insertAdjacentHTML('beforeend', `
                    <div id="player-counter-${player.id}">A player counter</div>
                `);

                document.getElementById('player-tables').insertAdjacentHTML('beforeend', `
                    <div id="player-table-${player.id}">
                        <div class="player-beast">
                            <strong class="label">${player.display_name}'s Beast</strong>
                            <div class="content"></div>
                        </div>
                    </div>
                `);
            });

            Object.values(gamedatas.hand || {}).forEach(card => {
                const cardAnimal = Object.values(gamedatas.animals).find((a) => (a.id === card.type_arg));

                // @TODO: robustify this
                const animalSlug = cardAnimal.display_name.toLowerCase().replace(/\s/g, '');

                document.getElementById('current-player-hand').insertAdjacentHTML('beforeend', `
                    <div class="card animal-card ${animalSlug}" data-animalid="${card.type_arg}"></div>
                `);
            });

            // Setting up unassigned characters
            Object.values(gamedatas.characters).forEach(character => {
                document.getElementById('unassigned-characters').insertAdjacentHTML('beforeend', `
                    <div id="player-mat-${character.id}" class="player-mat ${character.slug} unassigned-player-mat hidden" data-characterid="${character.id}"></div>
                `);
            });
            document.querySelectorAll('#unassigned-characters .player-mat').forEach(
                element => element.addEventListener('click', e => this.onSelectCharacter(e))
            );

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function(stateName, args) {
            console.log('Entering state: ' + stateName, args);
            
            const currentPlayerIsActive = this.isCurrentPlayerActive();

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
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName ) {
            console.log('Leaving state: ' + stateName);
            
            switch(stateName) {
                /*
                    Example:
                    case 'myGameState':
                        // Hide the HTML block we are displaying only during this game state
                        dojo.style( 'my_html_block_id', 'display', 'none' );
                        break;
                */
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
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        addDiscOnBoard: async function(x, y, player) {
            const color = this.gamedatas.players[player].color;

            document.getElementById('discs').insertAdjacentHTML(
                'beforeend',
                `<div class="disc" data-color="${color}" id="disc_${x}${y}"></div>`
            );

            this.placeOnObject(`disc_${x}${y}`, 'overall_player_board_' + player);

            const anim = this.slideToObject(`disc_${x}${y}`, 'square_' + x + '_' + y );
            await this.bgaPlayDojoAnimation(anim);
        },

        // updatePossibleMoves: function(possibleMoves) {
        //     // Remove current possible moves
        //     document.querySelectorAll('.possibleMove').forEach(div => div.classList.remove('possibleMove'));

        //     for (var x in possibleMoves) {
        //         for (var y in possibleMoves[x]) {
        //             // x,y is a possible move
        //             document.getElementById(`square_${x}_${y}`).classList.add('possibleMove');
        //         }
        //     }

        //     this.addTooltipToClass('possibleMove', '', _('Place a disc here'));
        // },
        showUnassignedCharacters: function(unassignedCharacters) {
            const charactersToShow = unassignedCharacters.map((c) => c.id);

            console.log('charactersToShow: ', charactersToShow);

            document.querySelectorAll('#unassigned-characters .unassigned-player-mat').forEach(element => {

                console.log('element: ', element);

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
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
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
        onSelectCharacter: function(evt) {
            // Stop this event propagation
            evt.preventDefault();
            evt.stopPropagation();

            const selectedCharacterID = evt.currentTarget.dataset.characterid;
            // if (!document.getElementById(`square_${x}_${y}`).classList.contains('possibleMove')) {
            //     // This is not a possible move => the click does nothing
            //     return;
            // }

            this.bgaPerformAction('actSelectCharacter', {
                selectedCharacterID : selectedCharacterID
            });
        },


        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in your beastbuilders.game.php file.
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
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        notif_selectCharacter: async function(args) {
            const characterID = args.character_id;

            // hide this character's player mat
            document.querySelectorAll('#unassigned-characters .unassigned-player-mat').forEach(element => {
                if (element.dataset.characterid === characterID) {
                    element.classList.add('hidden');
                }
            });
        },

        // notif_playDisc: async function(args) {
        //     // Remove current possible moves (makes the board more clear)
        //     document.querySelectorAll('.possibleMove').forEach(div => div.classList.remove('possibleMove'));

        //     await this.addDiscOnBoard(args.x, args.y, args.player_id);
        // },

        notif_turnOverDiscs: async function(args) {
            // Get the color of the player who is returning the discs
            const targetColor = this.gamedatas.players[args.player_id].color;

            // wait for the animations of all turned discs to be over before considering the notif done
            await Promise.all(
                args.turnedOver.map(disc => this.animateTurnOverDisc(disc, targetColor))
            );
        },

        animateTurnOverDisc: async function(disc, targetColor) {
            const discDiv = document.getElementById(`disc_${disc.x}${disc.y}`);

            if (!this.bgaAnimationsActive()) {
                // do not play animations if the animations aren't activated (fast replay mode)
                discDiv.dataset.color = targetColor;
                return Promise.resolve();
            }

            // Make the disc blink 2 times
            const anim = dojo.fx.chain( [
                dojo.fadeOut( {
                                node: discDiv,
                                onEnd: () => discDiv.dataset.color = targetColor,
                            } ),
                dojo.fadeIn( { node: discDiv } )
            ] ); // end of dojo.fx.chain

            await this.bgaPlayDojoAnimation(anim);
        },

        notif_newScores: async function(args) {
            for (const player_id in args.scores) {
                const newScore = args.scores[ player_id ];
                this.scoreCtrl[player_id].toValue(newScore);
            }
        }
   });             
});
