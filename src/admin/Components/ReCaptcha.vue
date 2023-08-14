<script setup>
import { reactive, onMounted } from 'vue';
import Rest from '../Bits/Rest';

import { ElNotification } from 'element-plus';

const state = reactive({
  loading: false,
  pageLoaded: false,
  isUpdating: false,
  settings: {
    site_key: '',
    secret_key: '',
    enable_recaptcha: 'yes',
    enable_on_shortcode_login: 'yes',
  },
});


const fetchSettings = async ()=> {

  state.loading = true;

  try {

    const response = await Rest.get('recaptcha-settings');
    state.settings = { ...response.data };

  }catch (e) {

    console.log(e);

  }finally {
    state.pageLoaded = true;
    state.loading = false;
  }
};

const saveSettings = async () => {

  const settingsToUpdate = {
    enable_recaptcha: state.settings.enable_recaptcha,
    site_key: state.settings.site_key,
    secret_key: state.settings.secret_key,
    enable_on_shortcode_login: state.settings.enable_on_shortcode_login,
  };

  state.isUpdating = true;

  try {
    const response = await Rest.post('recaptcha-settings', settingsToUpdate);
    state.settings = { ...response.data };

    ElNotification.success({
      message: response.message
    })

  }catch (e) {

    ElNotification.error({
      message: e.message || 'Something went wrong'
    })

  }finally {
    state.isUpdating = false;
  }
};

onMounted(fetchSettings);

</script>

<template>
<div class="dashboard box_wrapper">
  <div class="box dashboard_box box_narrow" v-loading="state.loading">

    <div class="box_header" style="padding: 15px;font-size: 16px;">
      {{ $t('Recaptcha Settings') }}
    </div>

    <div class="box_body">
      <el-form label-position="top" @submit.prevent="saveSettings">

        <el-form-item class="fls_switch">
          <el-switch v-model="state.settings.enable_recaptcha" active-value="yes" inactive-value="no"/>
            {{ $t('Enable Recaptcha') }}
        </el-form-item>

        <div class="setting-options" v-if="state.settings.enable_recaptcha === 'yes'">
          <el-form-item label="Site Key (v2)">
            <el-input
                v-model="state.settings.site_key"
                type="password"
                placeholder="Site key"
                show-password
            />
          </el-form-item>

          <el-form-item label="Secret key (v2)">
            <el-input
                v-model="state.settings.secret_key"
                type="password"
                placeholder="Secret key"
                show-password
            />
          </el-form-item>

<!--          <el-form-item class="fls_switch">-->
<!--            <el-switch v-model="state.settings.enable_on_shortcode_login" active-value="yes" inactive-value="no"/>-->
<!--            {{ $t('Enable on shortcode') }}-->
<!--          </el-form-item>-->
        </div>

        <div class="d-flex w-100 align-end">
          <el-button
              type="primary"
              class="ml-auto"
              :loading="state.isUpdating"
              @click="saveSettings"
              >
            Save
          </el-button>
        </div>
      </el-form>
    </div>
  </div>
</div>
</template>

<style scoped>
.d-flex {
  display: flex;
}

.w-100 {
  width: 100%;
}

.ml-auto {
  margin-left: auto;
}

.align-end {
  align-items: flex-end;
}
</style>
