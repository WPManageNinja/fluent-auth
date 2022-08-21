<template>
    <div v-if="!appVars.qm_installed" style="margin-bottom: 20px;" class="dashboard box_wrapper">
        <div class="box dashboard_box">
            <div class="box_header text-align-center"
                 style="padding: 20px 10px; background-color:#fff8b7; font-size: 14px;">
                <p>
                    Fluent Query Logger requires Query Monitor plugin to function. Please install Query Monitor Plugin
                    First
                </p>
                <el-button @click="installQueryMonitor()" v-loading="installing" :disabled="installing"
                           size="small"
                           type="primary">
                    Install Query Monitor Plugin
                </el-button>
            </div>
        </div>
    </div>
    <div v-else-if="!appVars.is_active && $route.name == 'logs'" style="margin-bottom: 20px;" class="dashboard box_wrapper">
        <div class="box dashboard_box">
            <div class="box_header text-align-center"
                 style="padding: 20px 10px; background-color:#fff8b7; font-size: 14px;">
                <p>
                    Query Log is disabled. Please activate Query Log from settings to view your Database Queries
                </p>
                <el-button @click="$router.push('settings')"
                           size="small"
                           type="primary">
                    View Settings
                </el-button>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'GlobalNotice',
    data() {
        return {
            installing: false
        }
    },
    methods: {
        installQueryMonitor() {
            this.installing = true;
            this.$post('logs/install-dependencies')
                .then(response => {
                    this.appVars.qm_installed = true;
                    this.$router.push('settings');
                })
                .catch(errors => {
                    this.$handleError(errors);
                })
                .finally(() => {
                    this.installing = false;
                });
        }
    }
}
</script>
