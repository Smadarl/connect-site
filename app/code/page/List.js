import m from 'mithril';
import { appState } from '../AppState';
import API from '../API';

var List = {
    oninit: (vnode) => {
        const prom = API.sendRequest('api/gameList');
        prom.then((resp) => {
            if (resp.list) {
                appState.gameList = resp.list;
            }
        });
    },
    view: (vnode) => {
        return m('div', [
            m('h3', 'Game List'),
            m('ul', appState.gameList.map((game) => {
                return m(GameInList(game));
            }))
        ]);
    }
}

function GameInList(game) {
    return {
        game: game,
        view: function(vnode) {
            return m('li', [
                m('a',
                    {href: '/game/' + game.id, oncreate: m.route.link, onclick: () => {appState.game.id = game.id;}},
                    this.game.other.name + '(' + this.formatDate(this.game.other.last_seen) + '): ' + this.game.state)
            ]);
        },
        formatDate: function(date) {
            return date;
        }
    }
}

export default List;
