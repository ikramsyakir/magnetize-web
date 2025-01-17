import {createApp} from 'vue';
import {route} from 'ziggy-js';
import tooltip from '../../directives/tooltip.js';

createApp({
    name: "CreateRoleForm",
    directives: {
        tooltip: tooltip,
    },
    data() {
        return {
            form: {
                name: null,
                display_name: null,
                description: null,
                permissions: [],
            },
            permissions: window.permissions,
            errors: [],
            loading: false,
        }
    },
    methods: {
        async submitForm() {
            this.loading = true;

            let data = {
                name: this.form.name,
                display_name: this.form.display_name,
                description: this.form.description,
                permissions: this.form.permissions,
            };

            await axios.post(route('roles.store'), data).then(response => {
                this.errors = []; // Clear errors
                this.loading = false; // Stop loading

                if (response.data.redirect) {
                    this.loading = true;
                    window.location.href = response.data.redirect;
                }
            }).catch((error) => {
                console.log(error.response);
                if (error.response.status === 422) {
                    this.errors = error.response.data.errors;
                }
                this.loading = false; // Stop loading
            });
        },
    }
}).mount('#app');
