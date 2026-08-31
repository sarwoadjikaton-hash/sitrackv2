<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    file: null as File | null,
});

const uploadExcel = (e: any) => {
    form.file = e.target.files[0];
    if (form.file) {
        form.post('/import-surat', {
            preserveScroll: true,
            onSuccess: () => {
                alert('Import Berhasil!');
                form.reset();
            },
        });
    }
};
</script>

<template>
    <div class="st-card p-4">
        <h5 class="fw-bold mb-3">Import Data via Excel</h5>
        <div class="d-flex align-items-center gap-3">
            <div class="upload-btn-wrapper">
                <button class="btn btn-outline-primary shadow-sm">
                    <i class="bi bi-file-earmark-excel me-2"></i> Pilih File Excel
                </button>
                <input type="file" @change="uploadExcel" accept=".xlsx, .xls" />
            </div>
            <small class="text-muted">Format: .xlsx atau .xls (Maks 2MB)</small>
        </div>

        <!-- Progress Bar jika sedang upload -->
        <div v-if="form.processing" class="progress mt-3" style="height: 5px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated"
                :style="{ width: form.progress?.percentage + '%' }"></div>
        </div>
    </div>
</template>

<style scoped>
.upload-btn-wrapper {
    position: relative;
    overflow: hidden;
    display: inline-block;
}

.upload-btn-wrapper input[type=file] {
    font-size: 100px;
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    cursor: pointer;
}
</style>