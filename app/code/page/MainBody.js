import m from "mithril";
import { Board } from "./Board";
import { Clues } from "./Clues";
import { ClueForm } from "./ClueForm";
import { appState } from "../AppState";

export class MainBody {
    oninit() {
        if (!appState.game.id) {
            const parts = m.route.get().split('/');
            appState.game.id = parts[parts.length - 1];
        }
        appState.update();
    }
    view() {
        return m('div', {id: 'game'}, [
            m('.container', [
                m('.row', [
                    m('.col-xl-12.text-center', {id: 'ClueForm'}, m(ClueForm))
                ]),
                m('.row', [
                    m('.col-xl-2#clues', m(Clues)),
                    m('.col-xl-10#board', m(Board))
                ])
            ])
        ]);
    }
}
