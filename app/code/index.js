import m from "mithril";
import { Nav } from "./page/Nav";
import { MainBody } from "./page/MainBody";
import { Home } from "./page/Home";
import { Login } from "./page/Login";
import List from "./page/List";
import Register from './page/Register';

m.mount(document.getElementById('header'), Nav);

m.route(document.getElementById('mainBody'), '/', {
    '/': Home,
    '/login': Login,
    '/games': List,
    '/game/:gameid': MainBody,
    '/register': Register
});
// m.mount(document.getElementById('mainBody'), MainBody);
