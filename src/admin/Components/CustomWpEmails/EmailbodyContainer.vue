<template>
    <div>
        <iframe
            ref="ifr"
            frameborder="0"
            allowFullScreen
            mozallowfullscreen
            webkitallowfullscreen
            style="width:100%;height: 600px;"
        ></iframe>
    </div>
</template>

<script>
export default {
    name: 'EmailbodyContainer',
    props: ['content', 'style_config'],
    data() {
        return {
            // ...
        };
    },
    methods: {
        setBody(body) {
            if (!body) {
                body = ' ';
            }

            this.$nextTick(() => {
                const ifr = this.$refs.ifr;
                const doc = ifr.contentDocument || ifr.contentWindow.document;
                doc.body.innerHTML = body;
            });
        },
    },
    watch: {
        content: {
            immediate: true,
            handler: 'setBody'
        },
        style_config: {
            deep: true,
            handler() {
                if(!this.style_config) {
                    return;
                }
                const ifr = this.$refs.ifr;
                if(!ifr) {
                    return;
                }

                // let's generate the styles
                let css = '';
                css += `body, .body_wrap { background-color: ${this.style_config.body_bg} !important; }`;
                css += `body .footer_table { color: ${this.style_config.footer_content_color} !important; }`;
                css += `body .content_wrap { background-color: ${this.style_config.content_bg} !important; color: ${this.style_config.content_color} !important; }`;
                css += `blockquote { background-color: ${this.style_config.highlight_bg} !important; color: ${this.style_config.highlight_color} !important;}`;
                css += `blockquote p { color: ${this.style_config.highlight_color} !important;}`;

                // let's add the styles to the iframe
                const doc = ifr.contentDocument || ifr.contentWindow.document;
                const style = doc.createElement('style');
                style.type = 'text/css';
                style.appendChild(doc.createTextNode(css));
                doc.head.appendChild(style);

                // replace the text with class name: footer_text
                doc.querySelector('.footer_text').innerHTML = this.style_config.footer_text;

            }
        }
    }
};
</script>
