import m from 'mithril';
import Auth from './Auth';

var API = {
    sendRequest: function(route, data) {
        let options = {
            method: 'POST',
            url: route,
            data: data ? data : {}
        }
        if (Auth.token) {
            options.config = function(xhr) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + Auth.token);
            }
        }
        const prom = m.request(options);
        return prom;
    }
}

export default API;
