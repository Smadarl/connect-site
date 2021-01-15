import m from "mithril";
import { Card } from "./Card";
import { appState } from "../AppState";
import API from "../API";

export class Board {
    constructor() {
        this.timeout = null
    }
    // oninit() {
    //     appState.update()
    // }
    view() {
        let self = this;
        return m('.container', {
            onclick: function(e) {
                e.stopPropagation();
                let id = e.target.id;
                let played = false;
                if (e.target.tagName != 'SPAN') {
                    let span = $(e.target).find('span').get(0);
                    id = span.id;
                    if ($(span).hasClass('agent') || $(span).hasClass('bystander'))
                        played = true;
                } else {
                    if ((e.target.className.indexOf('agent') >= 0) || (e.target.className.indexOf('bystander') >= 0))
                        played = true;
                }
                if (played)
                    return false;
                let parts = id.split('-');
                let idx = parts[1];
                self.sendGuess(idx);
            }
        }, appState.wordList.map(function (row) {
            return m(".row", row.map(function (word) {
                return m(new Card(word, appState.curIdx(), appState.turn == appState.myId));
            }));
        }));
    }
    sendGuess(idx) {
        // TODO: Don't send guesses on previously guessed words
        if (appState.turn == appState.myId && appState.status == 'guess') {
            API.sendRequest('api/saveGuess/' + appState.game.id, {idx: idx})
            .then((response) => {
                // This doesn't need to change turn because after someone guesses, it is their turn to give the clue
                // TODO: End game when all agents found, or assassin is hit
                appState.status = response.state;

                if (response.state == 'done') {
                    if (response.guess.result == 'agent') {
                        appState.result = 'won'
                    } else if (response.guess.result == 'assassin') {
                        appState.result = 'lost'
                    }
                }
                let clueIdx = appState.clues[appState.other].length - 1;
                appState.clues[appState.other][clueIdx].guesses.push(response.guess);
                this.updateWordList(idx, response.guess.result);
            });
        }
    }
    updateWordList(idx, status) {
        appState.wordList[this.getRowIdx(idx)][this.getColIdx(idx)].state.guess = status;
        if (status != 'bystander') {
            appState.wordList[this.getRowIdx(idx)][this.getColIdx(idx)].state.clue = status;
        }
    }
    getRowIdx(idx) {
        return Math.floor(idx / 5);
    }
    getColIdx(idx) {
        return idx % 5;
    }
}
