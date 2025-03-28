<template>
    <div>
        <el-popover
            :ref="btn_ref"
            placement="bottom-start"
            offset="50"
            :width="400"
            popper-class="fcrm-smartcodes-popover el-dropdown-list-wrapper"
            :visible="visible"
        >
            <div class="el_pop_data_group">
                <div class="el_pop_data_headings">
                    <ul>
                        <li
                            v-for="(item,item_index) in data"
                            :data-item_index="item_index"
                            :key="item_index"
                            :class="(activeIndex == item_index) ? 'active_item_selected' : ''"
                            @click="activeIndex = item_index">
                            {{ item.title }}
                        </li>
                    </ul>

                    <div v-if="doc_url" class="pop_doc">
                        <a :href="doc_url" target="_blank" rel="noopener">{{$t('Learn More')}}</a>
                    </div>
                </div>
                <div class="el_pop_data_body">
                    <div v-for="(item,current_index) in data" :key="current_index">
                        <ul v-show="activeIndex == current_index"
                            :class="'el_pop_body_item_'+current_index">
                            <li @click="insertShortcode(code)" v-for="(label,code) in item.shortcodes" :key="code">
                                {{ label }}<span>{{ code }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <template #reference>
                <el-button class="editor-add-shortcode"
                           @click="visible = !visible"
                           :type="btnType"
                           v-html="buttonText"
                />
            </template>
        </el-popover>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'inputPopoverDropdownExtended',
    props: {
        data: Array,
        close_on_insert: {
            type: Boolean,
            default() {
                return true;
            }
        },
        buttonText: {
            type: String,
            default() {
                return 'Add SmartCodes';
            }
        },
        btnType: {
            type: String,
            default() {
                return 'default';
            }
        },
        btn_ref: {
            type: String,
            default() {
                return 'input-popover1';
            }
        },
        doc_url: {
            type: String,
            default() {
                return '';
            }
        }
    },
    data() {
        return {
            activeIndex: 0,
            visible: false
        }
    },
    methods: {
        selectEmoji(imoji) {
            this.insertShortcode(imoji.data);
        },
        insertShortcode(code) {
            this.$emit('command', code);
            if (this.close_on_insert) {
                this.visible = false;
            }
        }
    },
    mounted() {
    }
}
</script>

<style lang="scss">
.fcrm-smartcodes-popover {
    padding: 0;
    border-radius: 8px;
    .el_pop_data_group {
        overflow: hidden;
        display: flex;
        * {
            box-sizing: border-box;
        }
        .pop_doc {
            left: 0;
            bottom: 0;
            width: 100%;
            padding: 0;
            a {
                background: #e6e6e6;
                color: #1e1f21;
                text-align: center;
                display: block;
                padding: 4px 5px;
                border-radius: 4px;
                transition: .2s;
                &:hover {
                    background: #1e1f21;
                    color: #ffffff;
                }
            }
        }

        .el_pop_data_headings {
            width: 190px;
            background: #f2f2f2;
            border-radius: 8px;
            padding: 10px;
            position: relative;

            ul {
                padding: 0;
                margin: 10px 0 0 0;

                li {
                    cursor: pointer;
                    color: #1e1f21;
                    font-size: 13px;
                    padding: 6px 8px;
                    border-radius: 4px;
                    margin-bottom: 4px;
                    position: relative;
                    transition: .2s;

                    &.active_item_selected {
                        background: #1e1f21;
                        color: #ffffff;
                    }
                }
            }
        }

        .el_pop_data_body {
            background: #ffffff;
            padding: 14px 20px 0 20px;
            width: 370px;
            height: 400px;
            overflow: auto;
            border-radius: 0 10px 10px 0;

            ul {
                padding: 0;
                margin: 0;

                li {
                    color: black;
                    padding: 12px 10px 12px 10px;
                    display: block;
                    margin-bottom: 0;
                    cursor: pointer;
                    text-align: left;
                    border-bottom: 1px solid #ececec;
                    &:first-child {
                        padding-top: 0;
                    }
                    &:last-child {
                        border-bottom: none;
                    }

                    &:hover {
                        background: white;
                    }

                    span {
                        font-size: 11px;
                        color: #8e8f90;
                        margin: 2px 0 0 0;
                        display: block;
                    }
                }
            }
        }
    }
}
</style>
