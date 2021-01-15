import m from "mithril";
import { appState } from "../AppState";
import API from "../API";

export function binds(data) {
    return {
        onchange: function(e) {
            console.log(e.target.name + ' = ' + e.target.value);
            data[e.target.name] = e.target.value;
        }
    }
}

let newClue = {numClue: 1};

export class ClueForm {
    constructor() {
    }
    oninit() {
    }
    view() {
        if (appState.turn != appState.myId || appState.status == 'waiting') {
            return m('h3', 'Waiting on other player to ' + appState.status);
        }
        if (appState.status != 'clue') {
            if (appState.status == 'guess') {
                return [m('h3', 'Make your guesses by clicking on the words in the grid below'),
                    m('button', {onclick: this.endTurn}, "End Turn")];
            } else if (appState.status == 'done') {
                return m('h3', 'This game has finished');
            }
        }
        return m("form.form-inline", binds(newClue), [
            m(".form-group.mb-2", [
                m('label.sr-only[for=txtClue]'),
                m('input.form-control', {
                    name: 'txtClue',
                    type: 'text',
                    placeholder: 'Enter Clue Here'
                }),
                m('select.form-control', {
                    name: 'numClue',
                }, [1, 2, 3, 4, 5].map((val) => { return m('option', { value: val }, val); }))
            ]),
            m('button.btn.btn-primary.mb2[type=submit]', {
                onclick: this.submit
            }, 'Submit Clue')
        ]);
    }
    submit(e) {
        e.preventDefault();
        API.sendRequest('api/saveClue/' + appState.game.id, {clue: newClue})
        .then((response) => {
            if (response.status == 200) {
                let tmp = appState.turn;
                appState.turn = appState.other;
                appState.other = tmp;
                appState.status = 'guess';
                newClue = {};
                appState.clues[appState.myId].push(response.clue);
                appState.autoRefresh()
            }
        });
    }
    // Need to figure out why this doesn't colorize the board when the state changes.
    endTurn(e) {
        e.preventDefault();
        // TODO: send new status to server
        // appState.status = 'clue';
        API.sendRequest('api/endTurn/' + appState.game.id)
        .then((response) => {
            console.log(response);
            appState.status = response.status;
        })
        m.redraw();
    }
}
