<template>
    <div class="dashboard box_wrapper">
        <div class="box dashboard_box box_narrow">
            <div v-loading="loading" class="box_header" style="padding: 20px 15px;font-size: 16px;">
                Settings
                <div class="box_actions">
                    <el-button @click="applyRecommended()" size="small">Apply recommended settings</el-button>
                </div>
            </div>
            <div v-if="settings" class="box_body">
                <el-form :data="settings" label-position="top">
                    <div class="fls_login_settings">
                        <h3>Core Security Settings</h3>
                        <el-form-item class="fls_switch">
                            <template #label>
                                Most of the sites don't need XMLRPC. You can disable this for enhance security. Recommended:
                                Disable
                            </template>
                            <el-radio-group v-model="settings.disable_xmlrpc" size="medium">
                                <el-radio label="yes">Disable XML-RPC</el-radio>
                                <el-radio label="no">Enable XML-RPC</el-radio>
                            </el-radio-group>
                        </el-form-item>
                        <el-form-item class="fls_switch">
                            <template #label>
                                Disable App Login (Rest API) for Remote Access. Recommended: Disable
                            </template>
                            <el-radio-group v-model="settings.disable_app_login" size="medium">
                                <el-radio label="yes">Disable Remote App Login</el-radio>
                                <el-radio label="no">Enable Remote App Login</el-radio>
                            </el-radio-group>
                        </el-form-item>
                        <el-form-item class="fls_switch">
                            <template #label>
                                REST Endpoint wp users query for public [Recommended: Disable]
                            </template>
                            <el-radio-group v-model="settings.disable_users_rest" size="medium">
                                <el-radio label="yes">Disable</el-radio>
                                <el-radio label="no">Enable</el-radio>
                            </el-radio-group>
                        </el-form-item>
                    </div>

                    <div class="fls_login_settings">
                        <h3>Login Security Settings</h3>
                        <el-form-item class="fls_switch">
                            <el-switch v-model="settings.enable_auth_logs" active-value="yes" inactive-value="no"/>
                            Enable Login Security and Login Limit (recommended)
                        </el-form-item>
                        <p v-if="settings.enable_auth_logs !== 'yes'" style="color: red;">We recommend to enable login logs
                            as well as set login try limit</p>

                        <template v-else>
                            <el-form-item label="Login Try Limit per IP address in certain defined minutes">
                                <el-input type="number" v-model="settings.login_try_limit"/>
                                <p>How many times user can try login in {{ settings.login_try_timing }} minutes</p>
                            </el-form-item>

                            <el-form-item label="Time limit for login try in minutes">
                                <el-input type="number" v-model="settings.login_try_timing"/>
                                <p>If you user do fail login {{ settings.login_try_limit }} times in
                                    {{ settings.login_try_timing }} minutes then system will block the user for
                                    {{ settings.login_try_timing }} minutes</p>
                            </el-form-item>
                        </template>
                    </div>

                    <div class="fls_login_settings">
                        <h3>Extended Login Security</h3>
                        <el-form-item>
                            <template #label>
                                Extended Login Security [Only enable it if you do not have other wp users than your close
                                circle]
                            </template>
                            <el-radio-group v-model="settings.extended_auth_security_type" size="medium">
                                <el-radio label="none">None</el-radio>
                                <el-radio label="pass_code">With Login Security Code</el-radio>
                            </el-radio-group>
                        </el-form-item>

                        <el-form-item v-if="settings.extended_auth_security_type == 'pass_code'">
                            <template #label>
                                Provide Login Security Pass that users need to provide when login
                            </template>
                            <el-input type="text" placeholder="Global Auth Security Code"
                                      v-model="settings.global_auth_code"/>
                            <p style="display: block; width: 100%;">
                                A new field will be shown to provide this code to login. Users can also set their own code from profile page.</p>
                        </el-form-item>
                    </div>

                    <div class="fls_login_settings">
                        <h3>Other Settings</h3>
                        <el-form-item>
                            <template #label>
                                Automatically delete logs older than (in days)
                            </template>
                            <el-input v-model="settings.auto_delete_logs_day" type="number" :min="0" />
                            <p style="display: block; width: 100%;">Use 0 if you do not delete the logs</p>
                        </el-form-item>
                        <el-form-item>
                            <template #label>
                                Send Email notification if any of the following user roles login
                            </template>
                            <el-checkbox-group v-model="settings.notification_user_roles">
                                <el-checkbox v-for="role in user_roles" :label="role.id" :key="role.id">{{role.title}}</el-checkbox>
                            </el-checkbox-group>
                        </el-form-item>

                        <el-form-item v-if="settings.notification_user_roles.length">
                            <template #label>
                                Notification Send to Email Address
                            </template>
                            <el-input type="text" v-model="settings.notification_email"></el-input>
                        </el-form-item>

                    </div>

                    <el-form-item>
                        <el-button size="large" @click="saveSettings()" :disabled="saving" v-loading="saving"
                                   type="success">Save Settings
                        </el-button>
                    </el-form-item>

                    <div class="fls_errors" v-if="errors">
                        <ul>
                            <li v-for="(error, errorKey) in errors" :key="errorKey">{{convertToText(error)}}</li>
                        </ul>
                    </div>

                </el-form>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'Settings',
    data() {
        return {
            settings: false,
            plugins: [],
            loading: false,
            activated: false,
            saving: false,
            errors: false,
            user_roles: []
        }
    },
    methods: {
        fetchSettings() {
            this.loading = true;
            this.$get('settings')
                .then(response => {
                    this.settings = response.settings;
                    this.user_roles = response.user_roles;
                })
                .catch((errors) => {
                    this.$handleError(errors)
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        saveSettings() {
            this.errors = false;
            this.saving = false;
            this.$post('settings', {
                settings: this.settings
            })
                .then(response => {
                    this.$notify.success(response.message);
                    this.settings = response.settings;
                    this.appVars.auth_settings = response.settings;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                    this.errors = errors.data;
                })
                .finally(() => {
                    this.saving = false;
                });
        },
        applyRecommended() {
            this.settings = {
                extended_auth_security_type: 'none',
                global_auth_code: '',
                disable_xmlrpc: 'yes',
                disable_app_login: 'yes',
                enable_auth_logs: 'yes',
                login_try_limit: 5,
                login_try_timing: 30,
                disable_users_rest: 'yes',
                auto_delete_logs_day: 30,
                notification_user_roles: ['administrator', 'editor'],
                notification_email: '{admin_email}'
            }
            this.$notify.success('Recommened settings has been applied. Please review and save the settings');
        }
    },
    mounted() {
        this.fetchSettings();
    }
};
</script>
