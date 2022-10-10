<template>
    <div class="fframe_app">
        <div class="fframe_main-menu-items">
            <div class="menu_logo_holder">
                <h3 style="margin: 10px 0;"><span class="dashicons dashicons-shield-alt"></span> Fluent Security</h3>
            </div>
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
    name: 'FluentSecurityApp',
    data() {
        return {
            menuItems: [
                {
                    route: 'dashboard',
                    title: 'Dashboard'
                },
                {
                    route: 'logs',
                    title: 'Logs'
                },
                {
                    route: 'settings',
                    title: 'Settings'
                },
                {
                    route: 'social_auth_settings',
                    title: 'Social Login Settings'
                }
            ]
        }
    },
    created() {
        jQuery('.update-nag,.notice, #wpbody-content > .updated, #wpbody-content > .error').remove();
    }
}
</script>
