<template>
    <div class="box_wrapper">
        <div class="box dashboard_box">
            <div class="box_header" style="padding: 15px;font-size: 16px;">
                <div style="padding-top: 5px;" class="box_head">
                    Logs
                    <el-button @click="fetchLogs()" size="small">refresh</el-button>
                </div>
                <div style="display: flex;" class="box_actions">
                    <el-radio-group @change="fetchLogs()" v-model="status">
                        <el-radio-button size="medium" label="all">All</el-radio-button>
                        <el-radio-button size="medium" v-for="(status, statusKey) in statuses" :key="statusKey" :label="statusKey">
                            {{ status }}
                        </el-radio-button>
                    </el-radio-group>
                    <el-input clearable @keyup.native.enter="fetchLogs()" style="width: 200px; margin-left: 10px;" size="small" type="text" v-model="search" placeholder="Search">
                        <template #append>
                            <el-button @click="fetchLogs()" :icon="SearchIcon" />
                        </template>
                    </el-input>
                </div>
            </div>
            <el-table @sort-change="handleSortChange"
                      :default-sort="{ prop: sortBy, order: sortType }"
                      v-loading="loading"
                      :data="logs"
                      :row-class-name="tableRowClassName"
                      style="width: 100%"
            >
                <el-table-column type="expand">
                    <template #default="props">
                        <div style="padding: 10px 25px;">
                            <b>Description</b>
                            <div class="sql_pre" v-html="props.row.description"></div>
                            <b>User Agent</b>
                            <div class="sql_pre">{{props.row.agent}}</div>
                        </div>
                    </template>
                </el-table-column>

                <el-table-column sortable prop="status" label="Status" width="130"/>
                <el-table-column sortable prop="username" min-width="200px" label="Login Username">
                    <template #default="scope">
                        <pre class="sql_pre">{{ scope.row.username }}</pre>
                    </template>
                </el-table-column>
                <el-table-column sortable prop="user_id" label="User ID" width="130">
                    <template #default="scope">
                        <span style="font-size:12px; line-height: 12px;">{{ scope.row.user_id }}</span>
                    </template>
                </el-table-column>
                <el-table-column sortable prop="ip" label="IP" width="120">
                    <template #default="scope">
                        <a target="_blank" rel="noopener nofollow" :href="'https://ipinfo.io/' + scope.row.ip">{{scope.row.ip}}</a>
                    </template>
                </el-table-column>
                <el-table-column sortable prop="browser" label="Browser" width="220">
                    <template #default="scope">
                        {{scope.row.device_os}} / {{scope.row.browser}}
                    </template>
                </el-table-column>
                <el-table-column sortable prop="created_at" label="Date" width="190">
                    <template #default="scope">
                        {{scope.row.human_time_diff}}
                    </template>
                </el-table-column>

                <el-table-column fixed="right" label="Action" width="90">
                    <template #default="scope">
                        <el-button @click="deleteLog(scope.row.id)" type="danger" plain size="small"><span style="width: 15px; height: 15px; font-size: 15px;" class="dashicons dashicons-trash"></span></el-button>
                    </template>
                </el-table-column>
            </el-table>
            <el-row style="margin-top: 20px;" :gutter="30">
                <el-col :md="12" :xs="24">
                    <el-popconfirm :width="200" @confirm="deleteAllLogs()" title="Are you sure to delete all the logs?">
                        <template #reference>
                            <el-button v-loading="deleting" size="small" type="danger">Delete All Logs</el-button>
                        </template>
                    </el-popconfirm>
                </el-col>
                <el-col :md="12" :xs="24">
                    <div class="fql_pagi text-align-right" style="float: right;">
                        <el-pagination @current-change="changePage"
                                       :current-page="paginate.page"
                                       :page-size="paginate.per_page"
                                       background layout="prev, pager, next"
                                       :total="paginate.total"
                        />
                    </div>
                </el-col>
            </el-row>
        </div>
    </div>
</template>

<script type="text/babel">
import {Search} from '@element-plus/icons-vue';

export default {
    name: 'Logs',
    components: {
    },
    data() {
        return {
            logs: [],
            SearchIcon: Search,
            paginate: {
                page: 1,
                per_page: 20,
                total: 0
            },
            search: '',
            status: 'all',
            show_ignores: 'no',
            statuses: this.appVars.auth_statuses,
            loading: false,
            sortBy: 'updated_at',
            sortType: 'descending',
            deleting: false
        }
    },
    methods: {
        changePage(page) {
            this.paginate.page = page;
            this.fetchLogs();
        },
        deleteAllLogs() {
            this.deleting = true;
            this.$post('truncate-auth-logs')
                .then(response => {
                    this.$notify.success(response.message);
                    this.fetchLogs();
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .finally(() => {
                    this.deleting = false;
                });
        },
        handleSortChange(prop) {
            if (!prop.prop) {
                return;
            }
            this.sortBy = prop.prop;
            this.sortType = prop.order;
            this.fetchLogs();
        },
        fetchLogs() {
            this.loading = true;
            this.$get('auth-logs', {
                per_page: this.paginate.per_page,
                page: this.paginate.page,
                statuses: [this.status],
                sortBy: this.sortBy,
                search: this.search,
                sortType: (this.sortType == 'descending') ? 'DESC' : 'ASC'
            })
                .then(response => {
                    this.logs = response.logs.data;
                    this.paginate.total = response.logs.total;
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        deleteLog(id) {
            this.deleting = true;
            this.$post('delete-log/' + id)
                .then(response => {
                    this.$notify.success(response.message);
                    this.fetchLogs();
                })
                .catch((errors) => {
                    this.$handleError(errors);
                })
                .finally(() => {
                    this.deleting = false;
                });
        },
        tableRowClassName({row, rowIndex}) {
            return 'fls_status_' + row.status;
        }
    },
    mounted() {
        this.fetchLogs();
    }
}
</script>
