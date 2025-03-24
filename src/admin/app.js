import {createApp} from 'vue'
import {createRouter, createWebHashHistory} from 'vue-router';
import {routes} from './routes';
import Rest from './Bits/Rest.js';
import {ElNotification, ElLoading, ElMessageBox} from 'element-plus'
import Storage from '@/Bits/Storage';
import App from './App.vue';

import {FolderOpened, Document, MoreFilled, DocumentAdd, DocumentDelete, Warning, MuteNotification} from '@element-plus/icons-vue';

require('./app.scss');

function convertToText(obj) {
    const string = [];
    if (typeof (obj) === 'object' && (obj.join === undefined)) {
        for (const prop in obj) {
            string.push(convertToText(obj[prop]));
        }
    } else if (typeof (obj) === 'object' && !(obj.join === undefined)) {
        for (const prop in obj) {
            string.push(convertToText(obj[prop]));
        }
    } else if (typeof (obj) === 'function') {

    } else if (typeof (obj) === 'string') {
        string.push(obj)
    }

    return string.join('<br />')
}

const app = createApp(App);
app.use(ElLoading);

app.component(FolderOpened.name, FolderOpened);
app.component(Document.name, Document);
app.component(DocumentAdd.name, DocumentAdd);
app.component(DocumentDelete.name, DocumentDelete);
app.component(Warning.name, Warning);
app.component(MuteNotification.name, MuteNotification);
app.component(MoreFilled.name, MoreFilled);

app.config.globalProperties.appVars = window.fluentAuthAdmin;

app.mixin({
    data() {
        return {
            Storage
        }
    },
    methods: {
        $get: Rest.get,
        $post: Rest.post,
        $put: Rest.put,
        $del: Rest.delete,
        changeTitle(title) {
            jQuery('head title').text(title + ' - Fluent Auth');
        },
        $handleError(response) {
            let errorMessage = '';
            if (typeof response === 'string') {
                errorMessage = response;
            } else if (response && response.message) {
                errorMessage = response.message;
            } else {
                errorMessage = convertToText(response);
            }
            if (!errorMessage) {
                errorMessage = 'Something is wrong!';
            }
            this.$notify({
                type: 'error',
                title: 'Error',
                message: errorMessage,
                dangerouslyUseHTMLString: true
            });
        },
        convertToText,
        $t(string) {
            return window.fluentAuthAdmin.i18n[string] || string;
        }
    }
});

app.config.globalProperties.$notify = ElNotification;
app.config.globalProperties.$confirm = ElMessageBox.confirm;

const router = createRouter({
    routes,
    history: createWebHashHistory()
});

window.fluentFrameworkApp = app.use(router).mount(
    '#fluent_auth_app'
);

jQuery('.toplevel_page_fluent-security a').on('click', function () {
    jQuery('.toplevel_page_fluent-security li').removeClass('current');
    jQuery(this).parent().addClass('current');
    window.scrollTo({top: 0, behavior: 'smooth'});
});
