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
    const panelHeight = panelEl.value?.offsetHeight || 280;
    const openUpward = spaceBelow < panelHeight + 12 && rect.top > panelHeight;

    // Pastikan lebar dropdown minimal 240px atau selebar trigger button
    let width = Math.max(rect.width, 240);
    let left = rect.left;

    // Batasi agar tidak meluap keluar layar kanan atau kiri
    if (left + width > window.innerWidth - 12) {
        left = Math.max(12, window.innerWidth - width - 12);
    }
    if (left < 12) {
        left = 12;
    }
    width = Math.min(width, window.innerWidth - 24);

    panelStyle.left = `${Math.round(left)}px`;
    panelStyle.width = `${Math.round(width)}px`;
    panelStyle.top = openUpward
        ? `${Math.round(rect.top - panelHeight - 6)}px`
        : `${Math.round(rect.bottom + 6)}px`;
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
                        <i class="bi bi-search dd-search-icon"></i>
                        <input ref="searchInputEl" v-model="search" type="text" :placeholder="searchPlaceholder"
                            @keydown.stop="onKeydown" />
                        <button v-if="search" type="button" class="dd-clear-btn" @click="search = ''" title="Hapus pencarian">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>

                    <div class="dd-list">
                        <button v-for="(opt, idx) in filteredOptions" :key="String(opt.value)" type="button" class="dd-option"
                            :class="{ 
                                'is-selected': opt.value === modelValue, 
                                'is-highlighted': idx === highlightedIndex,
                                'is-custom-option': opt.isCustom
                            }"
                            @mouseenter="highlightedIndex = idx" @click="selectOption(opt)">
                            <div class="dd-option-left">
                                <i v-if="opt.isCustom" class="bi bi-pencil-square text-primary flex-shrink-0"></i>
                                <span class="dd-option-label">{{ opt.label }}</span>
                            </div>
                            <i v-if="opt.value === modelValue && !opt.isCustom" class="bi bi-check-lg dd-check-icon"></i>
                        </button>

                        <div v-if="filteredOptions.length === 0" class="dd-empty">
                            <i class="bi bi-inbox me-1"></i> {{ emptyText }}
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
    justify-content: space-between;
    gap: .6rem;
    background: #fff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 0.5rem 0.85rem;
    font-size: .875rem;
    color: #0f172a;
    text-align: left;
    cursor: pointer;
    transition: all 0.2s ease;
    min-height: 38px;
    box-sizing: border-box;
    line-height: 1.5;
}

.dd-trigger.dd-size-sm {
    padding: 0.42rem 0.75rem;
    font-size: 0.84rem;
    border-radius: 8px;
    gap: 0.45rem;
    min-height: 38px;
}

.dd-trigger.dd-size-lg {
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    border-radius: 10px;
    min-height: 46px;
}

.dd-trigger:hover:not(.is-disabled) {
    border-color: #94a3b8;
}

.dd-trigger.is-open {
    border-color: #2743AF;
    box-shadow: 0 0 0 3px rgba(39, 67, 175, 0.15);
}

.dd-trigger.is-disabled {
    background: #f8fafc;
    cursor: not-allowed;
    opacity: .75;
}

.dd-trigger-label {
    flex: 1 1 auto;
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.4;
    display: block;
}

.dd-trigger-label.is-placeholder {
    color: #94a3b8;
}

.dd-chevron {
    flex: none;
    font-size: .8rem;
    color: #94a3b8;
    transition: transform .2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dd-trigger.is-open .dd-chevron {
    transform: rotate(180deg);
    color: #2743AF;
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
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    box-shadow: 0 16px 36px -4px rgba(15, 23, 42, 0.16), 0 4px 12px rgba(15, 23, 42, 0.08);
    overflow: hidden;
    font-family: inherit;
    box-sizing: border-box;
}

.dd-search {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 0.85rem;
    border-bottom: 1px solid #f1f5f9;
    background: #f8fafc;
    color: #64748b;
    box-sizing: border-box;
}

.dd-search-icon {
    font-size: 0.85rem;
    color: #94a3b8;
    flex-shrink: 0;
}

.dd-search input {
    flex: 1 1 auto;
    min-width: 0;
    border: none;
    background: transparent;
    outline: none;
    font-size: 0.85rem;
    color: #0f172a;
    padding: 0;
}

.dd-search input::placeholder {
    color: #94a3b8;
}

.dd-clear-btn {
    border: none;
    background: transparent;
    color: #94a3b8;
    cursor: pointer;
    padding: 0;
    font-size: 1rem;
    display: flex;
    align-items: center;
    line-height: 1;
}

.dd-clear-btn:hover {
    color: #0f172a;
}

.dd-list {
    max-height: 260px;
    overflow-y: auto;
    padding: 0.35rem;
    box-sizing: border-box;
}

.dd-list::-webkit-scrollbar {
    width: 5px;
}

.dd-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.dd-option {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    background: transparent;
    border: none;
    border-radius: 8px;
    padding: 0.55rem 0.75rem;
    font-size: 0.85rem;
    color: #1e293b;
    text-align: left;
    cursor: pointer;
    transition: background 0.12s ease, color 0.12s ease;
    box-sizing: border-box;
}

.dd-option-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1 1 auto;
    min-width: 0;
    overflow: hidden;
}

.dd-option-label {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: block;
    min-width: 0;
    line-height: 1.35;
}

.dd-check-icon {
    color: #0284c7;
    font-size: 0.95rem;
    font-weight: bold;
    flex-shrink: 0;
    margin-left: 0.25rem;
}

.dd-option.is-custom-option {
    background: #f0f9ff;
    border-bottom: 1px dashed #bae6fd;
    color: #0284c7;
    font-weight: 600;
    margin-bottom: 4px;
}

.dd-option.is-custom-option:hover,
.dd-option.is-custom-option.is-highlighted {
    background: #e0f2fe;
    color: #0369a1;
}

.dd-option.is-highlighted {
    background: #f1f5f9;
    color: #0f172a;
}

.dd-option.is-selected {
    background: #e0f2fe;
    color: #0284c7;
    font-weight: 600;
}

.dd-option.is-selected.is-highlighted {
    background: #bae6fd;
    color: #0369a1;
}

.dd-empty {
    padding: 1rem 0.75rem;
    text-align: center;
    font-size: 0.825rem;
    color: #94a3b8;
}

/* Animasi buka-tutup */
.dd-fade-enter-active,
.dd-fade-leave-active {
    transition: opacity .15s ease, transform .15s ease;
}

.dd-fade-enter-from,
.dd-fade-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>