import m from 'mithril';
import Auth from '../Auth';

export class Login {
    view() {
        return m('.login-wrapper', [
            m('div', {id: 'loginForm'}, [
                m('form', {onsubmit: this.submit, id: 'frmLogin'}, [
                    m('input[type=text]', {
                        name: 'email',
                        id: 'email',
                        placeholder: 'email',
                    }),
                    m('input[type=password]', {
                        name: 'password',
                        id: 'password',
                        placeholder: 'password'
                    }),
                    m('input[type=submit]', {
                        value: 'Log In'
                    })
                ])
            ])
        ]);
    }

    submit(e) {
        e.preventDefault();
        // let data = $('#frmLogin').serialize();
        // let data = new FormData(document.querySelector('form'));
        let data = $('#frmLogin').serializeArray().reduce(function(obj, item) {
            obj[item.name] = item.value;
            return obj;
        }, {});
        Auth.login(data.email, data.password)
        .then((data) => {
            if (data !== true) {
                // TODO: Alert invalid login
            } else {
                m.route.set('/');
            }
        })
    }
}