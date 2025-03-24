<template>
    <div class="box dashboard_box">
        <div class="box_header" style="padding: 10px 15px; font-weight: bold; font-size: 16px;">
            <el-icon><FolderOpened /></el-icon>
            {{rootPath}}
        </div>
        <div class="box_body">
            <div class="fls_file_lists">
                <div class="fls_file_item" v-for="(status, file) in files" :class="'fls_file_list_status_' + status" :key="file">
                    <div class="fls_file_title">
                        <el-tag size="small" type="info" :class="'fls_file_icon_type_' + status" class="fls_file_icon">
                            <el-icon>
                                <DocumentAdd v-if="status == 'new'" />
                                <Warning v-else-if="status == 'modified'" />
                                <DocumentDelete v-else-if="status == 'deleted'" />
                                <MuteNotification v-else-if="status == 'ignored'" />
                            </el-icon>
                            <span>{{status}}</span>
                        </el-tag>
                        <span>{{ file }}</span>
                    </div>
                    <div class="fls_file_status">
                        ...
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<script type="text/babel">
import each from 'lodash/each';

export default {
    name: 'FileLists',
    props: {
        files: {
            type: Object,
            default: () => ({})
        },
        ignoredFiles: {
            type: Array,
            default: () => []
        },
        rootPath: {
            type: String,
            default: ''
        }
    },
    computed: {
        stats() {
            let statuses = {};
            each(this.files, (fileStatus, file) => {
                file = this.rootPath ? this.rootPath + '/' + file : file;
                if (this.ignoredFiles.includes(file)) {
                    fileStatus = 'ignored';
                }
                if (!statuses[fileStatus]) {
                    statuses[fileStatus] = 0;
                }
                statuses[fileStatus] += 1;
            });

            return statuses;
        }
    }
}
</script>
