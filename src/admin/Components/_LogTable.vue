<template>
    <el-table :data="logs">
        <el-table-column :min-width="120" label="Username" prop="username">
            <template #default="scope">
                {{scope.row.username}}
                <el-tag v-if="scope.row.status == 'blocked'" size="small" type="error">{{scope.row.status}}</el-tag>
            </template>
        </el-table-column>
        <el-table-column :width="90" label="IP" prop="ip" />
        <el-table-column :width="120" label="Date" prop="human_time_diff">
            <template #default="scope">
                <span :title="scope.row.created_at" style="font-size: 11px;">{{ formatDate(scope.row.created_at) }}</span>
            </template>
        </el-table-column>
        <el-table-column :width="130" label="Browser" prop="browser">
            <template #default="scope">
                <span :title="scope.row.agent" style="font-size: 11px;">{{scope.row.device_os}} / {{scope.row.browser}}</span>
            </template>
        </el-table-column>
    </el-table>
</template>

<script type="text/babel">
import { diffForHuman } from '../Bits/common';

export default {
    name: 'LogTable',
    props: ['logs'],
    methods: {
        formatDate(dateString){
            return diffForHuman(dateString)
        }
    },
}
</script>
