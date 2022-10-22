<template>
    <div v-if="stats" v-loading="loading" class="fct_stat_widget fct_widget_dark fct_widget_bar">
        <div @click="stat_range_popup = !stat_range_popup" class="fct_stat_item fct_stat_filter">
            <el-popover v-model:visible="stat_range_popup" placement="bottom-start" width="300px">
                <template #reference>
                    <h3>
                        {{ stat_ranges[stat_range]?.label || $t('Select Range') }}
                        <el-icon class="el-icon--right">
                            <ArrowDown />
                        </el-icon>
                    </h3>
                </template>

                <div class="fct_quick_statbar_popup">
                    <el-radio v-for="(item, index) in stat_ranges" :key="index" @change="rangeChanged()" v-model="stat_range" :label="index" size="large">
                        <h1>{{ item.label }}</h1>
                        <p>{{ item.desc }}</p>
                    </el-radio>
                </div>

            </el-popover>
        </div>
        <div v-for="(stat, statKey) in stats" :key="statKey" :class="'fct_w_item_' + statKey" class="fct_stat_item fct_stat_num">
            <div class="fct_w_vals">
                <h3>{{stat.title}}</h3>
                <div class="fct_w_num">
                    <span>
                        {{ stat.count }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import {Search, ArrowDown} from '@element-plus/icons-vue';

export default {
    name: 'OrdersQuickStat',
    components: {
        Search,
        ArrowDown
    },
    data() {
        return {
            stats: false,
            loading: false,
            stat_range_popup: false,
            stat_range: '-7 days',
            stat_ranges: {
                '-0 days': {
                    label: this.$t('Today')
                },
                '-7 days': {
                    label: this.$t('Last 7 days')
                },
                '-30 days': {
                    label: this.$t('Last 30 days')
                },
                'this_month': {
                    label: this.$t('This Month')
                },
                'all_time': {
                    label: this.$t('All Time')
                }
            }
        }
    },
    methods: {
        fetchStat() {
            this.stat_range_popup = false;
            this.loading = true;
            this.$get('quick-stats', {
                day_range: this.stat_range
            })
                .then(response => {
                    this.stats = response.stats
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        rangeChanged() {
            this.fetchStat();
        }
    },
    mounted() {
        this.fetchStat();
    }
}
</script>
