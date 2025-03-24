<template>
    <el-row style="margin-top: 20px;" :gutter="30">
        <el-col :md="16" :sm="24" :xs="24">
            <div v-if="!scanStatus || scanStatus == 'scanning'" class="box dashboard_box">
                <div class="box_header" style="padding: 10px 15px; font-weight: bold; font-size: 16px;">
                    {{ $t('Site Scan') }}
                </div>
                <div class="box_body" style="padding: 10px 30px 30px;">
                    <p>You can scan your site to find any unauthorized files changes for WordPress core files. After
                        scanning FluentAuth will show if there has any security issues.</p>
                    <el-button v-if="!scanStatus" :disabled="scanStatus == 'scanning'"
                               :loading="scanStatus == 'scanning'" @click="startScanning" size="large" type="primary">
                        {{ $t('Start Scan for WP Core Files') }}
                    </el-button>
                    <div v-else>
                        <h3 style="margin: 0 0 10px;">Scanning in progress. Please wait....</h3>
                        <el-skeleton :animated="true" :rows="5"/>
                    </div>
                </div>
            </div>

            <div v-else-if="!hasIssues" class="box dashboard_box">
                <div class="box_header" style="padding: 10px 15px; font-weight: bold; font-size: 16px;">
                    {{ $t('Scan Result') }}
                </div>
                <div class="box_body" style="padding: 10px 30px 30px;">
                    <h3></h3>
                    <el-alert title="Awesome! All looks good!" type="success" :closable="false" show-icon/>
                    <p>FluentAuth has scanned your site and found no unauthorized changes in WordPress core files.</p>
                    <el-button @click="startScanning" size="large" type="primary">
                        {{ $t('Scan Again') }}
                    </el-button>
                </div>
            </div>

            <div v-else-if="hasIssues" class="box dashboard_box">
                <el-alert title="ALERT: Please review the file changes!" type="error" :closable="false" show-icon/>
                <p style="font-size: 16px;">Look like there has some file changes has been detected. Please review individual files and take necesarry actions.</p>

                <div v-if="scan_results.root">
                    <file-lists :files="scan_results.root" root-path="/" />
                </div>

                <div v-if="scan_results.wp_admin">
                    <file-lists :files="scan_results.wp_admin" root-path="/wp-admin/" />
                </div>

                <div v-if="scan_results.wp_includes">
                    <file-lists :files="scan_results.wp_includes" root-path="/wp-includes/" />
                </div>

            </div>

        </el-col>
        <el-col :md="8" :sm="24" :xs="24">

        </el-col>
    </el-row>
</template>

<script type="text/babel">
import FileLists from "./_FileLists.vue";

export default {
    name: 'Scanner',
    props: ['settings'],
    components: {
        FileLists
    },
    data() {
        return {
            scanStatus: 'scanned',
            scan_results: {
                "wp_admin": {
                    "_docs/index.md": "new",
                    "_docs/login-redirects.md": "new",
                    "_docs/shortcodes.md": "new",
                    "_docs/social-connections/github-auth-connection.md": "new",
                    "_docs/social-connections/google-auth-connection.md": "new",
                    "about_extra.php": "new",
                    "admin.php": "modified",
                    "credits_new.php": "new",
                    "credits.php": "deleted"
                },
                "wp_includes": {
                    "SimplePie/autoloader.php": "modified"
                },
                "root": {
                    "wp-login_extra.php": "new"
                },
                "extra_root_folders": []
            },
            error_message: '',
            hasIssues: true
        }
    },
    methods: {
        startScanning() {
            this.scan_results = null;
            this.scanStatus = 'scanning';
            this.error_message = '';

            this.$get('security-scan-settings/scan')
                .then(response => {
                    this.hasIssues = response.has_issues;
                    this.scan_results = response.scan_results;
                    this.scanStatus = 'scanned';
                })
                .catch((errors) => {
                    this.$handleError(errors);
                    this.error_message = errors?.message;
                    this.scanStatus = 'error';
                });
        }
    }
}
</script>
