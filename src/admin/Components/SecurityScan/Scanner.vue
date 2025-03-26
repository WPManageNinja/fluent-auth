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

                    <p v-if="error_message" style="color: red;">{{error_message}}</p>

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
                <template v-if="willAlert">
                    <el-alert title="ALERT: Please review the file changes!" type="error" :closable="false" show-icon/>
                    <p style="font-size: 16px;">Look like there has some file changes has been detected. Please review
                        individual files and take necesarry actions.</p>
                </template>
                <template v-else>
                    <el-alert title="FluentAuth found some file changes but you marked them as ignored them previously"
                              type="warnning" :closable="false" show-icon/>
                    <p>
                        All the file changes are marked as ignored previously. You can review the files and check if you
                        want to keep them on ignored lists or not.
                    </p>
                </template>

                <div v-if="scan_results.extra_root_folders">
                    <folder-lists :ignored-files="ignores.folders" root-path="/" :files="scan_results.extra_root_folders"/>
                </div>

                <div v-if="scan_results.root">
                    <file-lists folderType="" :ignored-files="ignores.files" :files="scan_results.root" root-path="/"/>
                </div>

                <div v-if="scan_results.wp_admin">
                    <file-lists folderType="wp-admin" :ignored-files="ignores.files" :files="scan_results.wp_admin" root-path="/wp-admin/"/>
                </div>

                <div v-if="scan_results.wp_includes">
                    <file-lists folderType="wp-includes" :ignored-files="ignores.files" :files="scan_results.wp_includes"
                                root-path="/wp-includes/"/>
                </div>

                <el-button :disabled="scanStatus == 'scanning'" :loading="scanStatus == 'scanning'" @click="startScanning" size="large" type="primary">
                    {{ $t('Scan Again') }}
                </el-button>
            </div>

        </el-col>
        <el-col :md="8" :sm="24" :xs="24">
            <scanner-widgets :settings="settings" :ignores="ignores"/>
        </el-col>
    </el-row>
</template>

<script type="text/babel">
import FileLists from "./_FileLists.vue";
import FolderLists from "./_FolderLists.vue";
import each from "lodash/each";
import ScannerWidgets from "./_ScannerWidgets.vue";

export default {
    name: 'Scanner',
    props: ['settings', 'ignores'],
    components: {
        ScannerWidgets,
        FileLists,
        FolderLists
    },
    data() {
        return {
            scanStatus: '',
            scan_results: [],
            error_message: '',
            hasIssues: true,
            willAlert: false
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
                    this.processReport(response.scan_results);
                })
                .catch((errors) => {
                    console.log(errors);
                    this.$handleError(errors.message);
                    this.error_message = errors?.message;
                    this.scanStatus = '';
                });
        },
        processReport(scanResults) {
            if (!this.ignores.files.length && !this.ignores.folders.length) {
                return;
            }

            let allFiles = [];

            if (scanResults.root) {
                each(scanResults.root, (fileStatus, file) => {
                    allFiles.push('/' + file);
                });
            }

            if (scanResults.wp_admin) {
                each(scanResults.wp_admin, (fileStatus, file) => {
                    allFiles.push('/wp-admin/' + file);
                });
            }

            if (scanResults.wp_includes) {
                each(scanResults.wp_includes, (fileStatus, file) => {
                    allFiles.push('/wp-includes/' + file);
                });
            }

            if(scanResults.extra_root_folders) {
                each(scanResults.extra_root_folders, (folder) => {
                    allFiles.push('/' + folder);
                });
            }

            // find the difference between allFiles and this.ignores.files
            allFiles = allFiles.filter(file => {
                return !this.ignores.files.includes(file);
            });

            allFiles = allFiles.filter(file => {
                return !this.ignores.folders.includes(file);
            });

            if (allFiles.length) {
                this.willAlert = true;
            } else {
                this.willAlert = false;
            }
        }
    }
}
</script>
