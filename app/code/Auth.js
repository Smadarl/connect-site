// Authentication
import m from 'mithril';
import { appState } from './AppState';

var Auth = {
    _token_: localStorage.token,

    get token() {
        return this._token_;
    },

    set token(token) {
        this._token_ = token;
        localStorage.token = this._token_;
    },

    in: function() {
        if (this._token_) {
            return this._token_.length > 0;
        }
        return false;
    },

    login: async function(email, pass) {
        try {
            const response = await m.request({
                url: 'api/login',
                method: 'POST',
                data: { email: email, pass: pass },
            });
            this.token = response.token;
            appState.player = response.player;
            return true;
        }
        catch (err) {
            return err.message;
        }
    },

    register: async function(name, email, pass) {
        try {
            const response = await m.request({
                url: 'api/register',
                method: 'POST',
                data: {name,email, pass}
            });
            console.log('Success');
            console.log(response);
        }
        catch (err) {
            console.log('Caught error');
            console.log(err);
        }
    },

    logout: function() {
        Auth.token = '';
        appState.clear();
        console.log('logout');
        m.redraw();
    }
}

export default Auth;