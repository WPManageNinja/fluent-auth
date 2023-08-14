<template>
    <div class="fframe_app">
        <div class="fframe_main-menu-items">
            <div class="menu_logo_holder">
                <h3 style="margin: 10px 0; display: flex;align-items: center;"><img :src="appVars.asset_url + '/images/logo.png'" style="width: 150px; margin-top: -10px; margin-right: 7px;" /></h3>
            </div>
            <div class="fframe_handheld"><span class="dashicons dashicons-menu-alt3"></span></div>
            <ul class="fframe_menu">
                <li v-for="item in menuItems" :key="item.route" class="fframe_menu_item">
                    <router-link :to="{ name: item.route }" class="fframe_menu_primary">
                        {{item.title}}
                    </router-link>
                </li>
            </ul>
        </div>

        <div class="ff_app_body">
            <router-view></router-view>
        </div>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'FluentAuthApp',
    data() {
        return {
            menuItems: [
                {
                    route: 'dashboard',
                    title: this.$t('Dashboard')
                },
                {
                    route: 'logs',
                    title: this.$t('Logs')
                },
                {
                    route: 'settings',
                    title: this.$t('Settings')
                },
                {
                    route: 'recaptcha',
                    title: this.$t('Recaptcha')
                },
                {
                    route: 'social_auth_settings',
                    title: this.$t('Social Login')
                },
                {
                    route: 'auth_shortcodes',
                    title: this.$t('Login/Signup Forms')
                },
                {
                    route: 'login_redirects',
                    title: this.$t('Login Redirects')
                }
            ]
        }
    },
    created() {
        jQuery('.update-nag,.notice, #wpbody-content > .updated, #wpbody-content > .error').remove();
    },
    mounted() {
        jQuery('.fframe_handheld span').on('click', function () {
            jQuery('ul.fframe_menu').toggle('show');
        });
    }
}
</script>
