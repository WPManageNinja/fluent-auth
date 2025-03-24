<template>
    <div class="box dashboard_box">
        <div class="box_header" style="padding: 10px 15px; font-weight: bold; font-size: 16px;">
            Extra Folders in Root
        </div>
        <div class="box_body">
            <div class="fls_file_lists">
                <div class="fls_file_item" v-for="file in formattedFiles"
                     :class="[
                          (file.isIgnored) ? 'fls_file_ignored' : ''
                     ]"
                     :key="file"
                >
                    <div class="fls_file_title">
                        <el-tag size="small" type="info" :class="'fls_file_icon_type_' + file.status"
                                class="fls_file_icon">
                            <el-icon>
                                <FolderOpened/>
                            </el-icon>
                        </el-tag>
                        <span>{{ file.file }}</span>
                        <el-tag v-if="file.isIgnored" size="small" type="info" class="fls_file_icon">
                            <MuteNotification/>
                            <span>Ignored</span>
                        </el-tag>
                    </div>
                    <div v-loading="workingFile == file.file" class="fls_file_status">
                        <el-dropdown @command="handleCommand" trigger="click">
                            <el-button text class="el-dropdown-link">
                                <el-icon>
                                    <MoreFilled/>
                                </el-icon>
                            </el-button>
                            <template #dropdown>
                                <el-dropdown-menu>
                                    <el-dropdown-item :command="file">
                                        <span v-if="file.isIgnored">
                                            {{ $t('Remove from Ignore List') }}
                                        </span>
                                        <span v-else>
                                             {{ $t('Add to Ignore List') }}
                                        </span>
                                    </el-dropdown-item>
                                </el-dropdown-menu>
                            </template>
                        </el-dropdown>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script type="text/babel">
import each from 'lodash/each';

export default {
    name: 'FolderLists',
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
    data() {
        return {
            workingFile: ''
        }
    },
    computed: {
        formattedFiles() {
            let formatted = [];

            let ignoredFiles = this.ignoredFiles;

            if (!ignoredFiles || !ignoredFiles.length) {
                ignoredFiles = [];
            }

            each(this.files, (file) => {
                let fullName = this.rootPath ? this.rootPath + file : file;
                formatted.push({
                    file: fullName,
                    relativeName: file,
                    isIgnored: ignoredFiles.includes(fullName),
                });
            });

            return formatted;
        }
    },
    methods: {
        handleCommand(file) {
            this.workingFile = file.file;
            let willRemove = file.isIgnored;
            this.$post('security-scan-settings/scan/toggle-ignore', {
                will_remove: willRemove ? 'yes' : 'no',
                file: file.file,
                is_folder: 'yes'
            })
                .then((response) => {
                    this.$notify.success(response.message);
                    if (willRemove) {
                        this.ignoredFiles.splice(this.ignoredFiles.indexOf(file.file), 1);
                    } else {
                        this.ignoredFiles.push(this.workingFile);
                    }
                })
                .catch((errors) => {
                    this.$handleError(errors)
                })
                .finally(() => {
                    this.workingFile = '';
                });
        }
    }
}
</script>
