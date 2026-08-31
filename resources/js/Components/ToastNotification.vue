<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, watch, ref } from 'vue';

const page = usePage<any>();
const flash = computed(() => page.props.flash || {});

const show = ref(false);
const message = ref('');
const type = ref('success');
let timer: any = null;

watch(
    () => page.props.flash,
    (newFlash) => {
        if (newFlash?.success) {
            type.value = 'success';
            message.value = newFlash.success;
            displayToast();
        } else if (newFlash?.error) {
            type.value = 'danger';
            message.value = newFlash.error;
            displayToast();
        } else if (newFlash?.warning) {
            type.value = 'warning';
            message.value = newFlash.warning;
            displayToast();
        } else if (newFlash?.info) {
            type.value = 'info';
            message.value = newFlash.info;
            displayToast();
        }
    },
    { deep: true }
);

function displayToast() {
    show.value = true;
    if (timer) clearTimeout(timer);
    timer = setTimeout(() => {
        show.value = false;
    }, 4500);
}

function closeToast() {
    show.value = false;
    if (timer) clearTimeout(timer);
}
</script>

<template>
    <div
        v-if="show"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    >
        <div
            class="toast show align-items-center text-white border-0 shadow-lg animate__animated animate__fadeInRight"
            :class="{
                'bg-success': type === 'success',
                'bg-danger': type === 'danger',
                'bg-warning text-dark': type === 'warning',
                'bg-primary': type === 'info',
            }"
            role="alert"
            aria-live="assertive"
            aria-atomic="true"
        >
            <div class="d-flex p-2">
                <div class="toast-body d-flex align-items-center gap-2 fw-semibold">
                    <i
                        class="bi fs-5"
                        :class="{
                            'bi-check-circle-fill': type === 'success',
                            'bi-exclamation-triangle-fill': type === 'danger',
                            'bi-exclamation-circle-fill': type === 'warning',
                            'bi-info-circle-fill': type === 'info',
                        }"
                    ></i>
                    <span>{{ message }}</span>
                </div>
                <button
                    type="button"
                    class="btn-close btn-close-white me-2 m-auto"
                    :class="{ 'btn-close-dark': type === 'warning' }"
                    @click="closeToast"
                ></button>
            </div>
        </div>
    </div>
</template>
