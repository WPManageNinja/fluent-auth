<template>
    <div class="dashboard box_wrapper">
        <div class="box dashboard_box box_narrow">
            <div v-loading="loading" class="box_header" style="padding: 20px 15px;font-size: 16px;">
                Social Login/Signup Settings
                <div class="box_actions">

                </div>
            </div>
            <div v-if="settings" class="box_body">
                <el-form :data="settings" label-position="top">

                    <el-form-item class="fls_switch">
                        <el-switch v-model="settings.enabled" active-value="yes" inactive-value="no"/>
                        Enable Social Login / Signup
                    </el-form-item>

                    <div v-if="settings.enabled == 'yes'" class="fls_login_settings">
                        <h3>Login with Google Settings</h3>
                        <el-form-item class="fls_switch">
                            <el-switch v-model="settings.enable_google" active-value="yes" inactive-value="no"/>
                            Enable Login with Google
                        </el-form-item>
                        <template v-if="settings.enable_google == 'yes'">
                            <el-form-item label="Credential Storage Method">
                                <el-radio-group v-model="settings.google_key_method">
                                    <el-radio-button label="db">Database</el-radio-button>
                                    <el-radio-button label="wp_config">wp-config file (recommended)</el-radio-button>
                                </el-radio-group>
                            </el-form-item>
                            <div class="fls_code_instruction" v-if="settings.google_key_method == 'wp_config'">
                                <h3>Please add the following code in your wp-config.php file (please replace the ***
                                    with your app values)</h3>
                                <textarea readonly>define('FLUENT_AUTH_GOOGLE_CLIENT_ID', '******');
define('FLUENT_AUTH_GOOGLE_CLIENT_SECRET', '******');
                                </textarea>
                            </div>
                            <template v-else>
                                <el-form-item label="Google Client ID">
                                    <el-input v-model="settings.google_client_id" type="text"
                                              placeholder="Google Client ID"/>
                                </el-form-item>
                                <el-form-item label="Google Client Secret">
                                    <el-input v-model="settings.google_client_secret" type="password"
                                              placeholder="Google Client Secret"/>
                                </el-form-item>
                            </template>
                        </template>
                    </div>

                    <div v-if="settings.enabled == 'yes'" class="fls_login_settings">
                        <h3>Login with Github Settings</h3>
                        <el-form-item class="fls_switch">
                            <el-switch v-model="settings.enable_github" active-value="yes" inactive-value="no"/>
                            Enable Login with Github
                        </el-form-item>
                        <template v-if="settings.enable_github == 'yes'">
                            <el-form-item label="Credential Storage Method">
                                <el-radio-group v-model="settings.github_key_method">
                                    <el-radio-button label="db">Database</el-radio-button>
                                    <el-radio-button label="wp_config">wp-config file (recommended)</el-radio-button>
                                </el-radio-group>
                            </el-form-item>
                            <div class="fls_code_instruction" v-if="settings.github_key_method == 'wp_config'">
                                <h3>Please add the following code in your wp-config.php file (please replace the ***
                                    with your app values)</h3>
                                <textarea readonly>define('FLUENT_AUTH_GITHUB_CLIENT_ID', '******');
define('FLUENT_AUTH_GITHUB_CLIENT_SECRET', '******');
                                </textarea>
                            </div>
                            <template v-else>
                                <el-form-item label="Github Client ID">
                                    <el-input v-model="settings.github_client_id" type="text"
                                              placeholder="Github Client ID"/>
                                </el-form-item>
                                <el-form-item label="Google Client Secret">
                                    <el-input v-model="settings.github_client_secret" type="password"
                                              placeholder="Github Client Secret"/>
                                </el-form-item>
                            </template>
                        </template>
                    </div>

                    <el-form-item>
                        <el-button v-loading="saving" :disabled="saving" @click="saveSettings()" type="success">Save Settings</el-button>
                    </el-form-item>

                    <div class="fls_errors" v-if="errors">
                        <ul>
                            <li v-for="(error, errorKey) in errors" :key="errorKey" v-html="convertToText(error)"></li>
                        </ul>
                    </div>
                </el-form>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'SocialAuthSettings',
    data() {
        return {
            loading: false,
            settings: false,
            saving: false,
            errors: false
        }
    },
    methods: {
        saveSettings() {
            this.errors = false;
            this.saving = false;
            this.$post('social-auth-settings', {
                settings: this.settings
            })
                .then(response => {
                    this.$notify.success(response.message);
                })
                .catch((errors) => {
                    this.$handleError(errors);
                    this.errors = errors.data;
                })
                .finally(() => {
                    this.saving = false;
                });
        },
        getSettings() {
            this.loading = true;
            this.$get('social-auth-settings')
                .then(response => {
                    this.settings = response.settings
                })
                .catch((errors) => {
                    this.$handleError(errors)
                })
                .finally(() => {
                    this.loading = false;
                });
        }
    },
    mounted() {
        this.getSettings();
    }
}
</script>
