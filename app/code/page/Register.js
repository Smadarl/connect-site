import m from 'mithril';
import API from '../API';

var Register = {
    view: function() {
        return m('.reg-form', [
            m('form.register', [
                m('input[type=text]', {name: 'name', placeholder: 'Name'}),
                m('input[type=text]', {name: 'email', placeholder: 'Email'}),
                m('input[type=text]', {name: 'password', placeholder: 'Password'}),
                m('input[type=submit]', {value: 'New User', onclick: this.submit}, 'New User')
            ])
        ]);
    },
    submit: function(e) {
        e.preventDefault();
        API.sendRequest('api/register', $('form.register').serialize())
        .then((resp) => {
            console.log(resp);
        })
    }
}

export default Register;