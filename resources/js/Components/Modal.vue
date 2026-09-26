<script setup lang="ts">
import { watch, onMounted } from 'vue';

const props = withDefaults(
    defineProps<{
        show?: boolean;
        title?: string;
        maxWidth?: 'sm' | 'md' | 'lg' | 'xl';
    }>(),
    {
        show: false,
        maxWidth: 'md',
    }
);

const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};

watch(
    () => props.show,
    (val) => {
        if (val) {
            document.body.classList.add('modal-open');
        } else {
            document.body.classList.remove('modal-open');
        }
    }
);
</script>

<template>
    <div
        v-if="show"
        class="modal fade show d-block"
        tabindex="-1"
        style="background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);"
        @click.self="close"
    >
        <div
            class="modal-dialog modal-dialog-centered animate__animated animate__zoomIn animate__faster"
            :class="{
                'modal-sm': maxWidth === 'sm',
                'modal-md': maxWidth === 'md',
                'modal-lg': maxWidth === 'lg',
                'modal-xl': maxWidth === 'xl',
            }"
        >
            <div class="modal-content border-0 shadow-lg" style="border-radius: var(--st-radius-xl, 1rem); overflow: hidden;">
                <div v-if="title || $slots.title" class="modal-header border-bottom py-3 px-4" style="background: #f8fafc;">
                    <h5 class="modal-title fw-bold text-dark fs-6 d-flex align-items-center gap-2 m-0">
                        <slot name="title">{{ title }}</slot>
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        aria-label="Close"
                        @click="close"
                    ></button>
                </div>
                <div class="modal-body" :class="{ 'p-4': title || $slots.title, 'p-0': !(title || $slots.title) }">
                    <slot></slot>
                </div>
                <div v-if="$slots.footer" class="modal-footer border-top py-3 px-4 bg-light">
                    <slot name="footer"></slot>
                </div>
            </div>
        </div>
    </div>
</template>
