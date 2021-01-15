import m from "mithril";
import { appState } from "../AppState";
import Auth from "../Auth";
import API from "../API";

export class Nav {
    constructor() {
        if (Auth.in() && (Object.keys(appState.player).length === 0 && appState.player.constructor === Object)) {
            API.sendRequest('api/me')
            .then((p) => {
                appState.player = p.player;
            })
        }
    }
    view() {
        return m("nav.navbar.navbar-expand-lg.navbar-light.bg-light", [
            m("a.navbar-brand", "Connect"),
            m("ul.navbar-nav.mr-auto", [
                m("li.nav-item", m("a.nav-link[href='/']", {oncreate: m.route.link}, "Home")),
                this.gamesLink(),
            ])
            ,m('span', [
                this.userName(),
                this.authLink(),
                '(' + appState.status + ')'
            ])
        ]);
    }

    gamesLink() {
        if (Auth.in()) {
            return m("li.nav-item", m("a.nav-link[href='/games']", {oncreate: m.route.link}, "Games"))
        }
        return;
    }

    userName() {
        if(Auth.in()) {
            let username = 'Unknown User';
            if (Object.keys(appState.player).length === 0 && appState.player.constructor === Object) {

                // return m('span.name', 'Unknown User');
            } else {
                username = appState.player.name;
                // return m('span.name', appState.player.name)
            }
            return m('span.loggedin', [
                m('span.name', username),
                m('span', ' logged in')
            ])
        }
        return m('a.register', {oncreate: m.route.link, href: '/register'}, 'Create Account');
    }

    authLink() {
        if (Auth.in()) {
            return m('a[href=#]', {
                // oncreate: m.route.link,
                onclick: Auth.logout
            }, 'Logout');
        }
        return m('a', {
            href: '/login',
            oncreate: m.route.link
        }, 'Login')
    }
}
