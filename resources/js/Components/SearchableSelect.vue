<script setup lang="ts">
import { ref, reactive, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';

interface Option {
    value: number | string;
    label: string;
    isCustom?: boolean;
}

const props = withDefaults(defineProps<{
    modelValue: number | string | null;
    options: Option[];
    placeholder?: string;
    searchPlaceholder?: string;
    loading?: boolean;
    loadingText?: string;
    disabled?: boolean;
    searchable?: boolean;
    emptyText?: string;
    allowCustom?: boolean;
    customPlaceholder?: string;
    size?: 'sm' | 'md' | 'lg';
}>(), {
    placeholder: '-- Pilih --',
    searchPlaceholder: 'Cari...',
    loading: false,
    loadingText: 'Memuat...',
    disabled: false,
    searchable: true,
    emptyText: 'Tidak ada hasil ditemukan',
    allowCustom: false,
    customPlaceholder: '✨ Gunakan: "{text}" (Input Manual Bebas)',
    size: 'md',
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

const showSearch = computed(() => props.allowCustom || (props.searchable && (props.options.length > 5 || props.allowCustom)));

const filteredOptions = computed(() => {
    let list = props.options;
    const q = search.value.trim();
    if (q) {
        const lowerQ = q.toLowerCase();
        list = props.options.filter((o) => o.label.toLowerCase().includes(lowerQ));
    }
    if (props.allowCustom && q) {
        const exactMatch = list.some((o) => o.label.toLowerCase() === q.toLowerCase() || String(o.value).toLowerCase() === q.toLowerCase());
        if (!exactMatch) {
            const customLabel = props.customPlaceholder
                ? props.customPlaceholder.replace('{text}', q)
                : `✨ Gunakan: "${q}" (Input Bebas)`;
            return [
                { value: q, label: customLabel, isCustom: true },
                ...list,
            ];
        }
    }
    return list;
});

const selectedOption = computed(() => {
    if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
        return null;
    }
    const found = props.options.find((o) => o.value === props.modelValue);
    if (found) return found;
    if (props.allowCustom) {
        return { value: props.modelValue, label: String(props.modelValue) };
    }
    return null;
});

const updatePosition = () => {
    if (!wrapperEl.value) return;
    const rect = wrapperEl.value.getBoundingClientRect();
    const spaceBelow = window.innerHeight - rect.bottom;
    const panelHeight = panelEl.value?.offsetHeight || 300;
    const openUpward = spaceBelow < panelHeight + 12 && rect.top > panelHeight;

    let left = rect.left;
    let width = rect.width;

    // Mobile screen protection: ensure width fits viewport and doesn't bleed out
    if (window.innerWidth < 576) {
        width = Math.max(width, Math.min(window.innerWidth - 24, 340));
        if (left + width > window.innerWidth - 12) {
            left = Math.max(12, window.innerWidth - width - 12);
        }
    }

    panelStyle.left = `${Math.max(8, left)}px`;
    panelStyle.width = `${Math.min(width, window.innerWidth - 16)}px`;
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
        <button type="button" class="dd-trigger" :class="[
            `dd-size-${size}`,
            { 'is-open': isOpen, 'is-disabled': disabled || loading }
        ]"
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
                        <input ref="searchInputEl" v-model="search" type="text" :placeholder="searchPlaceholder"
                            @keydown.stop="onKeydown" />
                    </div>

                    <div class="dd-list">
                        <button v-for="(opt, idx) in filteredOptions" :key="String(opt.value)" type="button" class="dd-option"
                            :class="{ 
                                'is-selected': opt.value === modelValue, 
                                'is-highlighted': idx === highlightedIndex,
                                'is-custom-option': opt.isCustom
                            }"
                            @mouseenter="highlightedIndex = idx" @click="selectOption(opt)">
                            <div class="d-flex align-items-center gap-2 text-truncate">
                                <i v-if="opt.isCustom" class="bi bi-pencil-square text-primary flex-shrink-0"></i>
                                <span class="text-truncate">{{ opt.label }}</span>
                            </div>
                            <i v-if="opt.value === modelValue && !opt.isCustom" class="bi bi-check-lg"></i>
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

.dd-trigger.dd-size-sm {
    padding: 0.45rem 0.75rem;
    font-size: 0.84rem;
    border-radius: 8px;
    gap: 0.4rem;
}

.dd-trigger.dd-size-lg {
    padding: 0.85rem 1.15rem;
    font-size: 1rem;
    border-radius: 14px;
}

.dd-trigger:hover:not(.is-disabled) {
    border-color: #cbd5e1;
}

.dd-trigger.is-open {
    border-color: #3DA5F9;
    box-shadow: 0 0 0 4px rgba(61, 165, 249, 0.15);
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
    color: #167992;
}

.dd-spinner {
    flex: none;
    width: 14px;
    height: 14px;
    border: 2px solid #167992;
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
    border: 1px solid #B5CCE3;
    border-radius: 14px;
    box-shadow: 0 20px 40px -12px rgba(3, 32, 90, 0.18);
    overflow: hidden;
}

.dd-search {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .65rem .9rem;
    border-bottom: 1px solid #E4F5F9;
    color: #536b88;
}

.dd-search input {
    flex: 1;
    border: none;
    outline: none;
    font-size: .9rem;
    color: #03205A;
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
    background: #B5CCE3;
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
    color: #03205A;
    text-align: left;
    cursor: pointer;
    transition: background .12s ease, color .12s ease;
}

.dd-option.is-custom-option {
    background: #E4F5F9;
    border-bottom: 1px dashed #B5CCE3;
    color: #167992;
    font-weight: 600;
    margin-bottom: 4px;
}

.dd-option.is-custom-option:hover,
.dd-option.is-custom-option.is-highlighted {
    background: #EEF7FC;
    color: #03205A;
}

.dd-option.is-highlighted {
    background: #E4F5F9;
    color: #03205A;
}

.dd-option.is-selected {
    color: #167992;
    font-weight: 700;
}

.dd-option i {
    color: #167992;
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