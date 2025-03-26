<template>
    <div class="fls_scanner-widgets">
        <div class="fls-scanner-widgets">
            <div class="box dashboard_box">
                <div class="box_header" style="padding: 10px 15px; font-weight: bold; font-size: 16px;">
                    {{ $t('Scheduled Scanning') }}
                </div>
                <div class="box_body" style="padding: 15px 15px 20px;">
                    <div v-if="settings.auto_scan != 'yes'">
                        <p style="font-weight: bold;">{{ $t('Scheduled scanning is currently disabled') }}</p>
                        <p>
                            {{ $t('Enable auto-scanning of your Core WordPress files and get emails if there has any un-authorized file changes.') }}
                        </p>
                        <el-button v-loading="saving" :disabled="saving" v-if="scheduling.auto_scan != 'yes'" type="primary"
                                   @click="scheduling.auto_scan = 'yes'">{{ $t('Enable Auto Scanning') }}
                        </el-button>
                        <div v-else>
                            <el-form label-position="top" v-model="scheduling">
                                <el-form-item :label="$t('Scanning Interval')">
                                    <el-select v-model="scheduling.scan_interval" placeholder="Select Interval">
                                        <el-option label="Every Hour" value="hourly"></el-option>
                                        <el-option label="Daily" value="daily"></el-option>
                                    </el-select>
                                </el-form-item>
                                <el-form-item>
                                    <el-button type="success" @click="saveSchedulingSettings">{{ $t('Save') }}
                                    </el-button>
                                </el-form-item>
                            </el-form>
                        </div>
                    </div>
                    <div v-else>
                        <p style="font-weight: bold;">{{ $t('Scheduled scanning is currently enabled') }}</p>
                        <p>
                            {{ $t('You will get email alerts if there has any un-authorized file changes.') }}
                        </p>
                        <p>
                            <b>{{ $t('Scanning Interval') }}:</b> {{ scheduling.scan_interval }} <br/>
                            <b>{{ $t('Notification Email') }}:</b> {{ settings.account_email_id }}
                        </p>
                        <el-button v-loading="saving" :disabled="saving" style="margin-bottom: 15px;" @click="disableSchedule">
                            {{ $t('Disable/Change Auto Scanning') }}
                        </el-button>
                        <p>If you want to change the notification email address, <a v-loading="saving" @click.prevent="resetApi()" href="#">please click here</a>.</p>
                    </div>
                </div>
            </div>
            <div class="fls-scanner-widget">
                <h3>Ignores</h3>
                <pre>{{ settings }}</pre>
                <pre>{{ ignores }}</pre>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'ScannerWidgets',
    props: ['settings', 'ignores'],
    data() {
        return {
            scheduling: {
                auto_scan: this.settings.auto_scan,
                scan_interval: this.settings.scan_interval,
            },
            saving: false
        }
    },
    methods: {
        saveSchedulingSettings() {
            this.saving = true;
            this.$post('security-scan-settings/scan/update-schedule-scan', this.scheduling)
                .then(response => {
                    this.$notify.success(response.message);
                    this.settings.auto_scan = response.settings.auto_scan;
                    this.settings.scan_interval = response.settings.scan_interval;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .finally(() => {
                    this.saving = false;
                });
        },
        disableSchedule() {
            this.saving = true;
            this.$post('security-scan-settings/scan/update-schedule-scan', {
                auto_scan: 'no',
                scan_interval: this.scheduling.scan_interval
            })
                .then(response => {
                    this.$notify.success(response.message);
                    this.scheduling.auto_scan = 'no';
                    this.settings.auto_scan = response.settings.auto_scan;
                    this.settings.scan_interval = response.settings.scan_interval;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .finally(() => {
                    this.saving = false;
                });
        },
        resetApi() {
            this.saving = true;
            this.$post('security-scan-settings/scan/reset-api')
                .then(response => {
                    this.$notify.success(response.message);
                    // reload the page
                    window.location.reload();
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .finally(() => {
                    this.saving = false;
                });
        }
    }
}
</script>
