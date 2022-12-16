<template>
    <el-table :data="logs">
        <el-table-column :min-width="120" :label="$t('Username')" prop="username">
            <template #default="scope">
                {{scope.row.username}}
                <el-tag v-if="scope.row.status == 'blocked'" size="small" type="error">{{scope.row.status}}</el-tag>
            </template>
        </el-table-column>
        <el-table-column :label="$t('IP')" prop="ip">
            <template #default="scope">
                <span style="font-size: 11px;">{{scope.row.ip}}</span>
            </template>
        </el-table-column>
        <el-table-column :width="100" :label="$t('Date')" prop="human_time_diff">
            <template #default="scope">
                <span :title="scope.row.created_at" style="font-size: 11px;">{{scope.row.human_time_diff}}</span>
            </template>
        </el-table-column>
        <el-table-column :width="130" :label="$t('Browser')" prop="browser">
            <template #default="scope">
                <span :title="scope.row.agent" style="font-size: 11px;">{{scope.row.device_os}} / {{scope.row.browser}}</span>
            </template>
        </el-table-column>
    </el-table>
</template>

<script type="text/babel">
export default {
    name: 'LogTable',
    props: ['logs']
}
</script>
