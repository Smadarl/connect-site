import m from "mithril";

export class Card {
    constructor(word, status, turn) {
        this.word = word;
        this.status = status;
        this.turn = turn
    }
    view() {
        // TODO: Only show word state if game state is clue && turn is mine
        return m("div.card.text-center.col-sm", { style: { width: "10rem" } },
            m("div.card-body", {class: this.getClass(this.word.state[this.status])},
                m("span.card-title", { class: this.getClass(this.word.state[this.status]), id: "idx-" + this.word.id }, this.word.word)
            )
        );
    }
    getClass(wordState) {
        return wordState;
        if ((wordState == 'green') || (wordState == 'red')) {
            if (this.status == 'clue' && this.turn) {
                return wordState
            }
            return ''
        }
        return wordState
    }
}
