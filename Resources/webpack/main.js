
import Vue from "vue";
import 'document-register-element/build/document-register-element';
import vueCustomElement from 'vue-custom-element';

import UIkit from 'uikit';
import Icons from 'uikit/dist/js/uikit-icons';

import Table from "./vue/collections/Table.vue";
import DomainEditor from "./vue/DomainEditor.vue";
import Reference from "./vue/field/Reference.vue";

require("./sass/united.scss");

// loads the Icon plugin
UIkit.use(Icons);

// Use VueCustomElement
Vue.use(vueCustomElement);

window.UnitedCMSEventBus = new Vue();

// Register Collection: Table
Vue.customElement('united-cms-core-collection-table', Table);
Vue.customElement('united-cms-core-domaineditor', DomainEditor);
Vue.customElement('united-cms-core-reference-field', Reference);