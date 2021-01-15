import API from './API';

class AppState {
    constructor() {
        this.gameList = [];
        this.game = {};
        this.player = {};
        this.wordList = [];
        this.clues = {};
        this.turn = 0;
        this.other = 0;
        this.myId = 0;
        this.status = 'waiting';
        this.result = 'playing';
        this.auto_refresh = null;
    }
    curIdx() {
        if (this.turn == this.myId) {
            if (this.status == 'clue') {
                return 'clue'
            }
            return 'guess'
        }
        return this.status == 'clue' ? 'guess' : 'clue';
    }
    gameList() {
        API.sendRequest('api/gameList');
    }
    update() {
        let self = this;
        API.sendRequest('api/gameSetup/' + self.game.id)
        .then((result) => {
            for (let x = 0; x <= 4; x++) {
                self.wordList[x] = result.board.slice(x * 5, (x * 5) + 5);
            }
            self.clues = result.clues;
            self.turn = result.turn;
            self.other = result.other;
            self.myId = result.myId;
            self.status = result.state;
            if (result.turn != result.myId) {
                self.autoRefresh();
            }
        })
        .catch((err) => {
        });
    }
    autoRefresh() {
        if (!this.auto_refresh) {
            this.auto_refresh = this.setTimeout(this.update.bind(this), 30000);
        }
    }
    cancelRefresh() {
        if (this.auto_refresh) {
            clearTimeout(this.auto_refresh);
            this.auto_refresh = nil;
        }
    }
    clear() {
        this.cancelRefresh();
        appState.player = {};
        appState.game = {};
        appState.clues = [];
        appState.turn = appState.other = appState.myId = 0;
        appState.status = '';
        appState.wordList = [];
        appState.gameList = [];
    }
}

export let appState = new AppState();
