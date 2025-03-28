<template>
    <div class="box_wrapper">
        <div class="box dashboard_box box_narrow">
            <div class="box_header" style="padding: 15px;font-size: 16px;">
                <div v-if="email" style="padding-top: 5px;" class="box_head">
                    <el-tag type="success">
                        {{$t('To:')}} {{ email.recipient }}
                    </el-tag>
                    {{ email.title }}
                    <p style="margin: 5px 0 0 0; font-weight: 500;">{{ email.description }}</p>
                </div>
                <div style="display: flex;" class="box_actions">
                    <el-button :disabled="saving" :loading="saving" @click="saveEmail" type="success">
                        {{$t('Save Settings')}}
                    </el-button>
                </div>
            </div>
            <div v-loading="loading" class="box_body">
                <div v-if="email && settings">
                    <el-form v-model="settings" label-position="top" class="fls_email_form">
                        <el-form-item :label="$t('Email Content Status')">
                            <el-radio-group v-model="settings.status">
                                <el-radio value="active" :label="$t('Customized Content')"></el-radio>
                                <el-radio value="system" :label="$t('System Default')"></el-radio>
                                <el-radio v-if="email.can_disable == 'yes'" value="disabled" :label="$t('Disabled')"></el-radio>
                            </el-radio-group>
                        </el-form-item>
                        <div v-if="settings.status == 'system'" style="padding: 10px 20px;" class="text-bg-warning">
                            <p style="margin: 0; font-size: 14px;">
                                <strong>{{$t('System Default')}}</strong>
                            </p>
                            <p style="margin: 0; font-size: 13px;">
                                {{$t('__email_default_system_desc__')}}
                            </p>
                        </div>
                        <div v-else-if="settings.status == 'disabled'" style="padding: 10px 20px;" class="text-bg-warning">
                            <p style="margin: 0; font-size: 14px;">
                                <strong>{{$t('Notification is disabled')}}</strong>
                            </p>
                            <p style="margin: 0; font-size: 13px;">
                                {{$t('This email notification is disabled. So no email notification will be sent for this event.')}}
                            </p>
                        </div>
                        <template v-else-if="settings.status == 'active'">
                            <el-form-item :label="$t('Email Subject')">
                                <input-popover input_size="large" :input_placeholder="$t('Your Email Subject')" v-model="settings.email.subject" :data="smartcodes" />
                            </el-form-item>
                            <el-form-item :label="$t('Email Body')">
                                <WpEditor v-if="!disableEditor" :editorShortcodes="smartcodes" v-model="settings.email.body"/>
                                <div v-if="default_content?.email?.body" style="margin-top: 10px;">
                                    <el-button @click="setDefaultContent()" size="small">
                                        {{$t('Set Default Subject & Body')}}
                                    </el-button>
                                    <el-button @click="previewEmail()" size="small">{{$t('Preview Email')}}</el-button>
                                </div>
                            </el-form-item>
                        </template>
                        <el-form-item style="text-align: right; margin-top: 40px;">
                            <el-button :disabled="saving" :loading="saving" @click="saveEmail" type="success">
                                {{$t('Save Settings')}}
                            </el-button>
                        </el-form-item>

                    </el-form>

                    <div v-if="settings.status == 'active' && required_smartcodes && required_smartcodes.length" class="fls_errors">
                        <p style="margin: 0; font-size: 14px;">
                            <strong>{{$t('Please Provide the Required Smartcodes')}}</strong>
                        </p>
                        <ul style="margin: 0; padding-left: 20px;">
                            <li v-for="(code, index) in required_smartcodes" :key="index">
                                <span v-html="'{{'+code+'}}'"></span> or <span v-html="'##'+code+'##'"></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <el-dialog
            v-model="showPreview"
            :title="$t('Previewing Email')"
            :width="800"
            :close-on-click-modal="true"
            :close-on-press-escape="true"
            :before-close="() => { showPreview = false; }"
        >
            <PreviewEmail
                v-if="showPreview"
                :email_id="email_id"
                :email_data="{subject: settings?.email?.subject, body: settings?.email?.body}"
            />
            <template #footer>
                <div class="dialog-footer">
                    <el-button type="primary" @click="showPreview = false">
                        {{$t('Close')}}
                    </el-button>
                </div>
            </template>
        </el-dialog>

    </div>
</template>

<script type="text/babel">
import WpEditor from './_wp_editor.vue';
import InputPopover from './MCE/InputPopover.vue';
import PreviewEmail from "./PreviewEmail.vue";

export default {
    name: 'EditWpEmail',
    components: {
        PreviewEmail,
        WpEditor,
        InputPopover
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
            saving: false,
            required_smartcodes: [],
            default_content: null,
            disableEditor: false,
            showPreview: false
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
                    this.default_content = response.default_content;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        saveEmail() {
            this.required_smartcodes = [];
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

                    if(errors?.data?.required_smartcodes) {
                        this.required_smartcodes = errors?.data?.required_smartcodes;
                    }
                })
                .finally(() => {
                    this.saving = false;
                });
        },
        setDefaultContent() {
            this.disableEditor = true;
            this.settings.email.subject = this.default_content.email.subject;
            this.settings.email.body = this.default_content.email.body;
            this.$nextTick(() => {
                this.disableEditor = false;
                this.$notify.success(this.$t('Default content has been set successfully.'));
            });
        },
        previewEmail() {
            this.showPreview = true;
        }
    },
    mounted() {
        this.fetchEmail();
    },
}
</script>
