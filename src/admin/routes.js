import Dashboard from './Components/Dashboard.vue';
import Logs from './Components/Logs.vue';
import Settings from './Components/Setttings.vue';
import SocialAuthSettings from './Components/SocialAuthSettings.vue';

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
        path: '/social-login-settings',
        name: 'social_auth_settings',
        component: SocialAuthSettings,
        meta: {
            active: 'social_auth_settings'
        }
    }
];
