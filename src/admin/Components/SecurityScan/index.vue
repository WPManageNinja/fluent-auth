<template>
    <div class="dashboard box_wrapper">
        <div class="box dashboard_box box_narrow">
            <div class="box_header_empty">
                <h3>{{ $t('WordPress Core Files Integrity Check') }}</h3>
                <p>
                    {{
                        $t('Ensure your WordPress core files remain secure by detecting any unauthorized changes or tampering')
                    }}
                </p>
            </div>

            <el-skeleton v-if="loading" :animated="true" :rows="5"/>

            <template v-else-if="settings">
                <register-promt @registered="getSettings()"
                                v-if="settings.status == 'unregistered' || settings.status == 'pending'"
                                :settings="settings"/>

                <scanner :ignores="ignores" v-if="settings.status == 'active'" :settings="settings"/>

                <pre v-else>{{ settings }}</pre>

            </template>

            <el-empty :description="$t('Sorry! Settings could not be loaded. Please reload the page.')" v-else/>

        </div>
    </div>
</template>

<script type="text/babel">
import RegisterPromt from "./RegisterPromt.vue";
import Scanner from "./Scanner.vue";

export default {
    name: 'SecurityScan',
    components: {
        RegisterPromt,
        Scanner
    },
    data() {
        return {
            loading: false,
            settings: null,
            saving: false,
            ignores: {
                files: [],
                folders: []
            }
        }
    },
    methods: {
        getSettings() {
            this.loading = true;
            this.$get('security-scan-settings')
                .then(response => {
                    this.settings = response.settings
                    this.ignores = response.ignores
                })
                .catch((errors) => {
                    this.$handleError(errors)
                })
                .finally(() => {
                    this.loading = false;
                });
        },
    },
    mounted() {
        this.getSettings();
    }
}
</script>
