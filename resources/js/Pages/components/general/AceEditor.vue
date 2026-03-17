<template>
    <VAceEditor v-model:value="modelValue" @init="editorInit" :lang="lang" :theme="theme" :options="editorOptions"
        :style="editorStyle" />
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { VAceEditor } from 'vue3-ace-editor'
import ace from 'ace-builds'

const aceBasePath = '/vendor/ace'
ace.config.set('basePath', aceBasePath)

function configureAceModule(type, name, fileName = `${type}-${name}.js`) {
    ace.config.setModuleUrl(`ace/${type}/${name}`, `${aceBasePath}/${fileName}`)
}

configureAceModule('mode', 'xml')
configureAceModule('mode', 'yaml')
configureAceModule('mode', 'lua')
configureAceModule('mode', 'php')
configureAceModule('mode', 'php_laravel_blade')
configureAceModule('mode', 'javascript')
configureAceModule('theme', 'chrome')
configureAceModule('theme', 'one_dark')
ace.config.setModuleUrl('ace/mode/base', `${aceBasePath}/worker-base.js`)
ace.config.setModuleUrl('ace/mode/xml_worker', `${aceBasePath}/worker-xml.js`)
ace.config.setModuleUrl('ace/mode/yaml_worker', `${aceBasePath}/worker-yaml.js`)
ace.config.setModuleUrl('ace/mode/lua_worker', `${aceBasePath}/worker-lua.js`)
ace.config.setModuleUrl('ace/mode/php_worker', `${aceBasePath}/worker-php.js`)
ace.config.setModuleUrl('ace/mode/javascript_worker', `${aceBasePath}/worker-javascript.js`)
configureAceModule('snippets', 'html')
configureAceModule('snippets', 'xml')
configureAceModule('snippets', 'yaml')
configureAceModule('snippets', 'php')
configureAceModule('snippets', 'javascript')
configureAceModule('ext', 'searchbox', 'ext-searchbox.js')
configureAceModule('ext', 'language_tools', 'ext-language_tools.js')
ace.require('ace/ext/language_tools')

// Props
const props = defineProps({
    modelValue: { type: String, default: '' },
    lang: { type: String, default: 'javascript' },
    theme: { type: String, default: 'chrome' },
    options: { type: Object, default: () => ({}) },
    height: { type: [Number, String], default: 400 },
})

// Emit
const emit = defineEmits(['update:modelValue'])

// Local state proxy for v-model
const modelValue = ref(props.modelValue)

const editorStyle = computed(() => ({
    height: typeof props.height === 'number' ? `${props.height}px` : props.height,
    width: '100%',
}))


// Sync prop → local
watch(() => props.modelValue, val => {
    if (val !== modelValue.value) modelValue.value = val
})

// Sync local → prop
watch(modelValue, val => emit('update:modelValue', val))

// Merge default options with user options
const editorOptions = {
    useWorker: true,
    enableBasicAutocompletion: true,
    enableSnippets: true,
    enableLiveAutocompletion: true,
    fontSize: 14,
    tabSize: 2,
    showPrintMargin: false,
    highlightActiveLine: true,
    ...props.options,
}

function editorInit(editor) {
    // optional setup
}
</script>
