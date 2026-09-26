<script setup lang="ts">
import { usePage, router } from '@inertiajs/vue3';
import { computed, watch, ref, nextTick, onMounted, onUnmounted } from 'vue';

const page = usePage<any>();

const show = ref(false);
const expanded = ref(false);
const message = ref('');
const type = ref('success');
const barVisible = ref(true);
let timer: any = null;

const DURATION = 5000;

const typeMeta = computed(() => {
    switch (type.value) {
        case 'success': return { icon: 'bi-check-circle-fill', color: 'var(--st-success, #10B981)' };
        case 'danger': return { icon: 'bi-exclamation-triangle-fill', color: 'var(--st-danger, #EF4444)' };
        case 'warning': return { icon: 'bi-exclamation-circle-fill', color: 'var(--st-warning, #F59E0B)' };
        default: return { icon: 'bi-info-circle-fill', color: 'var(--st-info, #3DA5F9)' };
    }
});

const isLong = computed(() => message.value.length > 70);

const handleFlash = (flash: any) => {
    if (!flash) return;
    if (flash.success) {
        type.value = 'success';
        message.value = flash.success;
        displayToast();
    } else if (flash.error) {
        type.value = 'danger';
        message.value = flash.error;
        displayToast();
    } else if (flash.warning) {
        type.value = 'warning';
        message.value = flash.warning;
        displayToast();
    } else if (flash.info) {
        type.value = 'info';
        message.value = flash.info;
        displayToast();
    }
};

watch(
    () => page.props.flash,
    (newFlash) => {
        handleFlash(newFlash);
    },
    { deep: true, immediate: true }
);

let removeInertiaListener: (() => void) | null = null;

const onWindowToast = (e: any) => {
    if (e.detail?.message) {
        type.value = e.detail.type || 'success';
        message.value = e.detail.message;
        displayToast();
    }
};

onMounted(() => {
    window.addEventListener('toast', onWindowToast);

    removeInertiaListener = router.on('success', (event: any) => {
        const flash = event.detail?.page?.props?.flash;
        if (flash) {
            handleFlash(flash);
        }
    });
});

onUnmounted(() => {
    window.removeEventListener('toast', onWindowToast);
    if (removeInertiaListener) {
        removeInertiaListener();
    }
});

async function displayToast() {
    expanded.value = false;
    show.value = true;
    barVisible.value = false;
    await nextTick();
    barVisible.value = true;

    if (timer) clearTimeout(timer);
    timer = setTimeout(() => { show.value = false; }, DURATION);
}

function closeToast() {
    show.value = false;
    if (timer) clearTimeout(timer);
}

function toggleExpand() {
    expanded.value = !expanded.value;
}

function pauseTimer() {
    if (timer) clearTimeout(timer);
}

function resumeTimer() {
    if (!expanded.value) {
        timer = setTimeout(() => { show.value = false; }, 1800);
    }
}
</script>

<template>
    <div v-if="show" class="dsh-toast-wrap">
        <div class="dsh-toast" :class="{ 'is-expanded': expanded }" @mouseenter="pauseTimer" @mouseleave="resumeTimer">
            <div class="dsh-toast-main">
                <span class="dsh-toast-icon" :style="{ background: `${typeMeta.color}1a`, color: typeMeta.color }">
                    <i class="bi" :class="typeMeta.icon"></i>
                </span>
                <div class="dsh-toast-body">
                    <p class="dsh-toast-msg" :class="{ 'is-clamped': isLong && !expanded }">{{ message }}</p>
                </div>
                <button v-if="isLong" type="button" class="dsh-toast-expand" @click="toggleExpand">
                    <i class="bi" :class="expanded ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                </button>
                <button type="button" class="dsh-toast-close" @click="closeToast">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="dsh-toast-timer-track">
                <div v-if="barVisible" class="dsh-toast-timer-bar"
                    :style="{ background: typeMeta.color, animationDuration: DURATION + 'ms' }"></div>
            </div>
        </div>
    </div>
</template>