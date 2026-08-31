<script setup lang="ts">
import { ref, reactive, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';

interface Option {
    value: number | string;
    label: string;
}

const props = withDefaults(defineProps<{
    modelValue: number | string | null;
    options: Option[];
    placeholder?: string;
    loading?: boolean;
    loadingText?: string;
    disabled?: boolean;
    searchable?: boolean;
    emptyText?: string;
}>(), {
    placeholder: '-- Pilih --',
    loading: false,
    loadingText: 'Memuat...',
    disabled: false,
    searchable: true,
    emptyText: 'Tidak ada hasil ditemukan',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: number | string | null): void;
}>();

const wrapperEl = ref<HTMLElement | null>(null);
const panelEl = ref<HTMLElement | null>(null);
const searchInputEl = ref<HTMLInputElement | null>(null);
const isOpen = ref(false);
const search = ref('');
const highlightedIndex = ref(-1);

// Posisi panel dihitung manual karena panel di-teleport ke <body>
const panelStyle = reactive({ top: '0px', left: '0px', width: '0px' });

const showSearch = computed(() => props.searchable && props.options.length > 5);

const filteredOptions = computed(() => {
    if (!search.value.trim()) return props.options;
    const q = search.value.toLowerCase();
    return props.options.filter((o) => o.label.toLowerCase().includes(q));
});

const selectedOption = computed(() =>
    props.options.find((o) => o.value === props.modelValue) || null
);

const updatePosition = () => {
    if (!wrapperEl.value) return;
    const rect = wrapperEl.value.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    const panelHeight = panelEl.value?.offsetHeight || 300;
    const openUpward = spaceBelow < panelHeight + 12 && rect.top > panelHeight;

    panelStyle.left = `${rect.left}px`;
    panelStyle.width = `${rect.width}px`;
    panelStyle.top = openUpward
        ? `${rect.top - panelHeight - 6}px`
        : `${rect.bottom + 6}px`;
};

const toggle = () => {
    if (props.disabled || props.loading) return;
    isOpen.value ? close() : open();
};

const open = () => {
    if (props.disabled || props.loading) return;
    isOpen.value = true;
    highlightedIndex.value = props.options.findIndex((o) => o.value === props.modelValue);
    nextTick(() => {
        updatePosition();
        searchInputEl.value?.focus();
        // Hitung ulang setelah panel benar-benar punya tinggi (untuk deteksi buka-ke-atas)
        requestAnimationFrame(updatePosition);
    });
};

const close = () => {
    isOpen.value = false;
    search.value = '';
};

const selectOption = (opt: Option) => {
    emit('update:modelValue', opt.value);
    close();
};

const onKeydown = (e: KeyboardEvent) => {
    if (!isOpen.value) {
        if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
            e.preventDefault();
            open();
        }
        return;
    }
    if (e.key === 'Escape') {
        e.preventDefault();
        close();
    } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        highlightedIndex.value = Math.min(highlightedIndex.value + 1, filteredOptions.value.length - 1);
        scrollHighlightedIntoView();
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
        scrollHighlightedIntoView();
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const opt = filteredOptions.value[highlightedIndex.value];
        if (opt) selectOption(opt);
    }
};

const scrollHighlightedIntoView = () => {
    nextTick(() => {
        const active = panelEl.value?.querySelector('.dd-option.is-highlighted') as HTMLElement | null;
        active?.scrollIntoView({ block: 'nearest' });
    });
};

watch(search, () => { highlightedIndex.value = 0; });

const onClickOutside = (e: MouseEvent) => {
    const target = e.target as Node;
    const insideTrigger = wrapperEl.value?.contains(target);
    const insidePanel = panelEl.value?.contains(target);
    if (!insideTrigger && !insidePanel) close();
};

const onWindowChange = () => {
    if (isOpen.value) updatePosition();
};

onMounted(() => {
    document.addEventListener('mousedown', onClickOutside);
    window.addEventListener('resize', onWindowChange);
    window.addEventListener('scroll', onWindowChange, true); // capture: ikut scroll di container manapun
});

onUnmounted(() => {
    document.removeEventListener('mousedown', onClickOutside);
    window.removeEventListener('resize', onWindowChange);
    window.removeEventListener('scroll', onWindowChange, true);
});
</script>

<template>
    <div class="dd-wrapper" ref="wrapperEl" @keydown="onKeydown">
        <button type="button" class="dd-trigger" :class="{ 'is-open': isOpen, 'is-disabled': disabled || loading }"
            :disabled="disabled || loading" @click="toggle">
            <span v-if="loading" class="dd-spinner"></span>
            <span class="dd-trigger-label" :class="{ 'is-placeholder': !selectedOption && !loading }">
                {{ loading ? loadingText : (selectedOption ? selectedOption.label : placeholder) }}
            </span>
            <i class="bi bi-chevron-down dd-chevron"></i>
        </button>

        <Teleport to="body">
            <Transition name="dd-fade">
                <div v-if="isOpen" ref="panelEl" class="dd-panel"
                    :style="{ top: panelStyle.top, left: panelStyle.left, width: panelStyle.width }">
                    <div v-if="showSearch" class="dd-search">
                        <i class="bi bi-search"></i>
                        <input ref="searchInputEl" v-model="search" type="text" placeholder="Cari..."
                            @keydown.stop="onKeydown" />
                    </div>

                    <div class="dd-list">
                        <button v-for="(opt, idx) in filteredOptions" :key="opt.value" type="button" class="dd-option"
                            :class="{ 'is-selected': opt.value === modelValue, 'is-highlighted': idx === highlightedIndex }"
                            @mouseenter="highlightedIndex = idx" @click="selectOption(opt)">
                            <span>{{ opt.label }}</span>
                            <i v-if="opt.value === modelValue" class="bi bi-check-lg"></i>
                        </button>

                        <div v-if="filteredOptions.length === 0" class="dd-empty">
                            {{ emptyText }}
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.dd-wrapper {
    position: relative;
    width: 100%;
}

.dd-trigger {
    width: 100%;
    display: flex;
    align-items: center;
    gap: .6rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    font-size: .95rem;
    color: #0f172a;
    text-align: left;
    cursor: pointer;
    transition: all 0.2s ease;
}

.dd-trigger:hover:not(.is-disabled) {
    border-color: #cbd5e1;
}

.dd-trigger.is-open {
    border-color: #14b8a6;
    box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.1);
}

.dd-trigger.is-disabled {
    background: #f8fafc;
    cursor: not-allowed;
    opacity: .75;
}

.dd-trigger-label {
    flex: 1;
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dd-trigger-label.is-placeholder {
    color: #94a3b8;
}

.dd-chevron {
    flex: none;
    font-size: .8rem;
    color: #94a3b8;
    transition: transform .2s ease;
}

.dd-trigger.is-open .dd-chevron {
    transform: rotate(180deg);
    color: #14b8a6;
}

.dd-spinner {
    flex: none;
    width: 14px;
    height: 14px;
    border: 2px solid #14b8a6;
    border-top-color: transparent;
    border-radius: 50%;
    animation: dd-spin 0.8s linear infinite;
}

@keyframes dd-spin {
    to {
        transform: rotate(360deg);
    }
}
</style>

<!-- Style panel TIDAK di-scope, karena elemennya di-teleport keluar dari komponen ini (ke <body>) -->
<style>
.dd-panel {
    position: fixed;
    z-index: 9999;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 20px 40px -12px rgba(15, 23, 42, 0.18);
    overflow: hidden;
}

.dd-search {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .65rem .9rem;
    border-bottom: 1px solid #f1f5f9;
    color: #94a3b8;
}

.dd-search input {
    flex: 1;
    border: none;
    outline: none;
    font-size: .9rem;
    color: #0f172a;
}

.dd-list {
    max-height: 240px;
    overflow-y: auto;
    padding: .35rem;
}

.dd-list::-webkit-scrollbar {
    width: 5px;
}

.dd-list::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 4px;
}

.dd-option {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    background: transparent;
    border: none;
    border-radius: 9px;
    padding: .6rem .75rem;
    font-size: .9rem;
    color: #334155;
    text-align: left;
    cursor: pointer;
    transition: background .12s ease, color .12s ease;
}

.dd-option.is-highlighted {
    background: rgba(20, 184, 166, 0.09);
    color: #0f172a;
}

.dd-option.is-selected {
    color: #0d9488;
    font-weight: 700;
}

.dd-option i {
    color: #14b8a6;
    font-size: .85rem;
    flex: none;
}

.dd-empty {
    padding: 1.1rem .75rem;
    text-align: center;
    font-size: .85rem;
    color: #94a3b8;
}

/* Animasi buka-tutup */
.dd-fade-enter-active,
.dd-fade-leave-active {
    transition: opacity .16s ease, transform .16s ease;
}

.dd-fade-enter-from,
.dd-fade-leave-to {
    opacity: 0;
    transform: translateY(-6px) scale(.98);
}
</style>