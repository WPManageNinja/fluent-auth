<template>
    <div class="dashboard box_wrapper">
        <div class="box dashboard_box box_narrow">
            <div v-loading="loading" class="box_header" style="padding: 20px 15px;font-size: 16px;">
                {{$t('auth_short_heading')}}
                <div class="box_actions">
                    <el-button size="small" v-loading="saving" :disabled="saving" @click="saveSettings()" type="success">
                        {{$t('Save Settings')}}
                    </el-button>
                </div>
            </div>
            <div v-if="settings" class="box_body">
                <el-form :data="settings" label-position="top">

                    <el-form-item class="fls_switch">
                        <el-switch v-model="settings.enabled" active-value="yes" inactive-value="no"/>
                        {{$t('enable_short_check')}}
                    </el-form-item>

                    <div v-if="settings.enabled == 'yes'" class="fls_login_settings">
                        <div class="fls_shortcode_section">
                            <h3>{{$t('full_auth_short')}}</h3>
                            <textarea readonly>[fluent_auth]</textarea>
                            <p class="help">If you want to define customized redirect URL then use shortcode: <code>[fluent_auth redirect_to="your_URL"]</code></p>
                        </div>
                        <div class="fls_shortcode_section">
                            <h3>{{$t('regi_short')}}</h3>
                            <textarea readonly>[fluent_auth_signup]</textarea>
                            <p class="help">If you want to define customized redirect URL then use shortcode: <code>[fluent_auth_signup redirect_to="your_URL"]</code></p>
                        </div>
                        <div class="fls_shortcode_section">
                            <h3>{{$t('login_short')}}</h3>
                            <textarea readonly>[fluent_auth_login]</textarea>
                            <p class="help">If you want to define customized redirect URL then use shortcode: <code>[fluent_auth_login redirect_to="your_URL"]</code></p>
                        </div>
                        <div class="fls_shortcode_section">
                            <h3>{{$t('pass_reset_short')}}</h3>
                            <textarea readonly>[fluent_auth_reset_password]</textarea>
                            <p class="help">If you want to define customized redirect URL then use shortcode: <code>[fluent_auth_reset_password redirect_to="your_URL"]</code></p>
                        </div>
                    </div>

                    <el-form-item>
                        <el-button v-loading="saving" :disabled="saving" @click="saveSettings()" type="success">
                            {{$t('Save Settings')}}
                        </el-button>
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
            errors: false,
            roles: {}
        }
    },
    methods: {
        saveSettings() {
            this.errors = false;
            this.saving = false;
            this.$post('auth-forms-settings', {
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
            this.$get('auth-forms-settings')
                .then(response => {
                    this.settings = response.settings
                    this.roles = response.roles
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
