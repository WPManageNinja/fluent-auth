import Dashboard from './Components/Dashboard.vue';
import Logs from './Components/Logs.vue';
import Settings from './Components/Setttings.vue';
import SocialAuthSettings from './Components/SocialAuthSettings.vue';
import AuthShortcodes from './Components/AuthShortcodes.vue';
import LoginRedirects from './Components/LoginRedirects.vue';
import ReCaptcha from './Components/ReCaptcha.vue';

export var routes = [
    {
        path: '/',
        name: 'dashboard',
        component: Dashboard,
        meta: {
            active: 'dashboard'
        }
    },
    {
        path: '/logs',
        name: 'logs',
        component: Logs,
        meta: {
            active: 'logs'
        }
    },
    {
        path: '/settings',
        name: 'settings',
        component: Settings,
        meta: {
            active: 'settings'
        }
    },
    {
        path: '/recaptcha',
        name: 'recaptcha',
        component: ReCaptcha,
        meta: {
            active: 'recaptcha'
        }
    },
    {
        path: '/social-login-settings',
        name: 'social_auth_settings',
        component: SocialAuthSettings,
        meta: {
            active: 'social_auth_settings'
        }
    },
    {
        path: '/auth-shortcodes',
        name: 'auth_shortcodes',
        component: AuthShortcodes,
        meta: {
            active: 'auth_shortcodes'
        }
    },
    {
        path: '/login-redirects',
        name: 'login_redirects',
        component: LoginRedirects,
        meta: {
            active: 'login_redirects'
        }
    }
];
