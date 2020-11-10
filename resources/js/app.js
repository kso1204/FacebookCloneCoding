import Vue from 'vue';
import router from './router';
import App from './components/App';
import store from './store'

require('./bootstrap');

Window.Vue = require('vue');


Vue.component('App', require('./components/App.vue').default)

const app = new Vue({
    el: "#app",
    router,
    store,

    
});