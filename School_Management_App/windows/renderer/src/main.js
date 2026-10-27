import { createApp } from 'vue';
import 'material-icons/iconfont/filled.css';
import 'material-icons/iconfont/outlined.css';
import '@fontsource-variable/inter';
import './styles/base.css';
import App from './App.vue';
import { router } from './router';
import { initSession } from './api/client';

initSession().then(() => {
  createApp(App).use(router).mount('#app');
});
