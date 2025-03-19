<template>
    <div class="box_wrapper">
        <div class="box dashboard_box box_narrow">
            <div class="box_header" style="padding: 15px;font-size: 16px;">
                <div v-if="email" style="padding-top: 5px;" class="box_head">
                    <el-tag type="success">
                        To: {{ email.recipient }}
                    </el-tag>
                    {{ email.title }}
                    <p style="margin: 5px 0 0 0; font-weight: 500;">{{ email.description }}</p>
                </div>
                <div style="display: flex;" class="box_actions">
                    <el-button :disabled="saving" :loading="saving" @click="saveEmail" type="success">Save Settings</el-button>
                </div>
            </div>
            <div v-loading="loading" class="box_body">
                <div v-if="email && settings">
                    <el-form v-model="settings" label-position="top" class="fls_email_form">
                        <el-form-item label="Email Content Status">
                            <el-radio-group v-model="settings.status">
                                <el-radio value="active">Customized Content</el-radio>
                                <el-radio value="system">System Default</el-radio>
                                <el-radio v-if="email.can_disable == 'yes'" value="disabled">Disable</el-radio>
                            </el-radio-group>
                        </el-form-item>
                        <div v-if="settings.status == 'system'" style="padding: 10px 20px;" class="text-bg-warning">
                            <p style="margin: 0; font-size: 14px;">
                                <strong>System Default</strong>
                            </p>
                            <p style="margin: 0; font-size: 13px;">
                                This email will use the system default content. If you want to customize the email
                                subject and body please switch to "Custimized Content".
                            </p>
                        </div>
                        <div v-else-if="settings.status == 'disabled'" style="padding: 10px 20px;" class="text-bg-warning">
                            <p style="margin: 0; font-size: 14px;">
                                <strong>Notification is disabled</strong>
                            </p>
                            <p style="margin: 0; font-size: 13px;">
                                This email notification is disabled. So no email notification will be sent for this event.
                            </p>
                        </div>
                        <template v-else-if="settings.status == 'active'">
                            <el-form-item label="Email Subject">
                                <el-input size="large" placeholder="Your Email Subject"
                                          v-model="settings.email.subject"/>
                            </el-form-item>
                            <el-form-item label="Email Body">
                                <WpEditor :editorShortcodes="smartcodes" v-model="settings.email.body"/>
                            </el-form-item>
                        </template>
                    </el-form>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import WpEditor from './_wp_editor.vue';

export default {
    name: 'EditWpEmail',
    components: {
        WpEditor
    },
    props: {
        email_id: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            email: null,
            settings: null,
            smartcodes: [],
            loading: false,
            saving: false
        }
    },
    methods: {
        fetchEmail() {
            this.loading = true;
            this.$get('wp-default-emails/find-email', {
                email_id: this.email_id
            })
                .then(response => {
                    this.smartcodes = response.smartcodes;
                    this.email = response.email;
                    this.settings = response.settings;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        saveEmail() {
            this.saving = true;
            this.$post('wp-default-emails/save-email-settings', {
                settings: this.settings,
                email_id: this.email_id
            })
                .then(response => {
                    this.$notify.success(response.message);
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .finally(() => {
                    this.saving = false;
                });
        }
    },
    mounted() {
        this.fetchEmail();
    },
}
</script>
