import m from "mithril";
import { appState } from "../AppState";
// import { SlowBuffer } from "buffer";

// TODO: Somehow, show both sets of clues at the same time.

export class Clues {
    view() {
        if (appState.other == 0) {
            return;
        }
        let otherId = appState.turn
        if (appState.myId == appState.turn) {
            otherId = appState.other
        }
        return m(".accordian", {id: 'clue-accordian'}, [
            m('.card', [
                m('.card-header', {id: 'my-clues-heading'}, [
                    m('h5.mb-0', [
                        m('button.btn.btn-link', {
                            type: 'button',
                            'data-toggle': 'collapse',
                            'data-target': '#my-clues',
                            'aria-expanded': 'true',
                            'aria-controls': 'my-clues'
                        }, 'My Clues')
                    ])
                ]),
                m('div.collapse.show', {
                    id: 'my-clues',
                    'aria-labelledby': 'my-clues-heading',
                    'data-parent': '#clue-accordian'
                }, [
                    m('.card-body',
                        appState.clues[appState.myId].map((clue) => {
                            return m(new Clue(clue))
                        })
                    )
                ])
            ]),
            m('.card', [
                m('.card-header', {id: 'other-clues-heading'}, [
                    m('h5.mb-0', [
                        m('button.btn.btn-link', {
                            type: 'button',
                            'data-toggle': 'collapse',
                            'data-target': '#other-clues',
                            'aria-expanded': 'true',
                            'aria-controls': 'other-clues'
                        }, 'Other Clues')
                    ])
                ]),
                m('div.collapse', {
                    id: 'other-clues',
                    'aria-labelledby': 'other-clues-heading',
                    'data-parent': '#clue-accordian'
                }, [
                    m('.card-body',
                        appState.clues[otherId].map((clue) => {
                            return m(new Clue(clue))
                        })
                    )
                ])
            ])
        ])
    }
}

export class Clues2 {
    view() {
        if (appState.other == 0) {
            return;
        }
        let thePlayerId = appState.turn;
        if (appState.status == 'guess') {
            thePlayerId = appState.other;
        }
        return m("div", appState.clues[thePlayerId].map((clue) => {
            return m(new Clue(clue))
        }));
    }
}

class Clue {
    constructor(clue) {
        this.clue = clue
    }
    view() {
        return [
            m('a.btn.btn-primary', {
                "data-toggle": 'collapse',
                id: 'clue-' + this.clue.id,
                href: '#guesses-' + this.clue.id
            }, this.clue.word + ' (' + this.clue.number + ')'),
            m('ul.collapse', {
                id: 'guesses-' + this.clue.id
            }, this.clue.guesses.map((guess) => {
                return m(new Guess(guess));
            }))
        ]
    }
}

class Guess {
    constructor(guess) {
        this.guess = guess
    }
    view() {
        return m("li",{class: this.guess.result},this.guess.word);
    }
}
