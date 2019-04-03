import m from 'mithril';
import API from '../API';
import Auth from '../Auth';

export class Home {
    constructor() {
        this.otherPlayers = [];
    }
    oninit() {
        if (Auth.in()) {
            API.sendRequest('api/opponents')
            .then((response) => {
                console.log(response);
                this.otherPlayers = response.list;
            });
        }
    }
    view() {
        if(Auth.in()) {
            return [
                m('div', 'Start a New Game'),
                m('form', {name: 'newGame'}, [
                    m('select', {id: 'newGameOppId'}, this.otherPlayers.map((player) => {
                        return m('option', {value: player.id, text: player.name});
                    })),
                    m('input[type=submit]', {onclick: this.submit})
                ])
            ];
        } else {
            return m('div', [
                m('h3', 'Login to play')
            ]);
        }
    }
    submit() {
        console.log($('#newGameOppId').val());
        API.sendRequest('api/newGame', {oppId: $('#newGameOppId').val()})
        .then((response) => {
            console.log(response);
            if (response.code && response.code == 200) {
                m.route.set('/game/' + response.gameId);
            }
        });
    }
}