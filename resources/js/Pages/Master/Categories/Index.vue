<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import { LetterCategory } from '@/types';

defineProps<{
    categories: LetterCategory[];
}>();

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref<number | null>(null);

// Inisialisasi form dengan nilai default
const form = useForm({
    category_name: '',
    description: '',
    is_active: true,
});

/**
 * Tutup modal dan RESET SEMUA STATE (Sangat Penting)
 */
const closeModal = () => {
    showModal.value = false;
    
    // Beri jeda sedikit agar animasi tutup modal selesai sebelum data hilang (opsional)
    setTimeout(() => {
        isEditing.value = false;
        editingId.value = null;
        form.reset(); // Ini mengembalikan ke nilai default saat useForm dideklarasikan
        form.clearErrors();
    }, 200);
};

/**
 * Buka modal tambah
 */
const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.clearErrors();
    form.reset(); // Pastikan form kosong
    showModal.value = true;
};

/**
 * Buka modal edit
 */
const openEditModal = (c: LetterCategory) => {
    isEditing.value = true;
    editingId.value = c.id;
    form.clearErrors();
    
    // Mengisi data ke form
    form.category_name = c.category_name;
    form.description = c.description || '';
    form.is_active = Boolean(c.is_active);
    
    showModal.value = true;
};

/**
 * Submit tambah / edit
 */
const submitCategory = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => closeModal(), // Panggil fungsi close yang sudah termasuk reset
    };

    if (isEditing.value && editingId.value !== null) {
        form.put(`/master/categories/${editingId.value}`, options);
    } else {
        form.post('/master/categories', options);
    }
};

/**
 * Hapus kategori
 */
const deleteCategory = (id: number) => {
    if (confirm('Hapus kategori surat ini?')) {
        router.delete(`/master/categories/${id}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout title="Master Kategori Surat">

        <Head title="Kategori Surat" />

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="text-uppercase fw-bold small text-primary">Master Data</span>
                <h2 class="fw-bold mb-1 text-dark">Kategori Surat</h2>
                <p class="text-muted mb-0 small">Kategori klasifikasi dokumen persuratan kedinasan.</p>
            </div>

            <button type="button" class="btn btn-sm btn-primary-blue" @click="openCreateModal">
                <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
            </button>
        </div>

        <div class="st-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Nama Kategori</th>
                            <th>Deskripsi / Peruntukan</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in categories" :key="c.id">
                            <td>
                                <div class="fw-bold text-dark">{{ c.category_name }}</div>
                            </td>
                            <td>
                                <div class="text-muted small">{{ c.description || '-' }}</div>
                            </td>
                            <td>
                                <span class="badge"
                                    :class="c.is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
                                    {{ c.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" @click="openEditModal(c)">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" @click="deleteCategory(c.id)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal">
            <template #title>
                {{ isEditing ? 'Ubah Kategori' : 'Tambah Kategori Baru' }}
            </template>

            <form @submit.prevent="submitCategory">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Kategori</label>
                    <input v-model="form.category_name" type="text" class="form-control"
                        placeholder="Contoh: Surat Edaran Khusus" required />
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Deskripsi</label>
                    <textarea v-model="form.description" class="form-control" rows="3"
                        placeholder="Penjelasan jenis surat..."></textarea>
                </div>

                <div class="form-check mb-4">
                    <input id="cat-active" v-model="form.is_active" type="checkbox" class="form-check-input" />
                    <label for="cat-active" class="form-check-label small fw-semibold">Status Kategori Aktif</label>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <button type="button" class="btn btn-secondary" @click="closeModal">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary-blue" :disabled="form.processing">
                        Simpan Kategori
                    </button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
