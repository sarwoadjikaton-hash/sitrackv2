<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import { Unit, PaginatedData } from '@/types';

const props = defineProps<{
    units: PaginatedData<Unit>;
    filters: { search: string };
}>();

const search = ref(props.filters.search || '');
const showModal = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null as number | null,
    unit_name: '',
    pic_name: '',
    phone: '',
    email: '',
    address: '',
    is_active: true,
});

const handleSearch = () => {
    router.get('/master/units', { search: search.value }, { preserveState: true });
};

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (unit: Unit) => {
    isEditing.value = true;
    form.id = unit.id;
    form.unit_name = unit.unit_name;
    form.pic_name = unit.pic_name || '';
    form.phone = unit.phone || '';
    form.email = unit.email || '';
    form.address = unit.address || '';
    form.is_active = unit.is_active;
    showModal.value = true;
};

const submitUnit = () => {
    form.post('/master/units', {
        onSuccess: () => {
            showModal.value = false;
        },
    });
};

const deleteUnit = (id: number) => {
    if (confirm('Hapus master unit kerja ini?')) {
        router.delete(`/master/units/${id}`);
    }
};
</script>

<template>
    <AppLayout title="Master Unit Kerja / Direktorat">
        <Head title="Master Unit" />

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="text-uppercase fw-bold small text-primary">Master Data</span>
                <h2 class="fw-bold mb-1 text-dark">Unit Kerja & Direktorat</h2>
                <p class="text-muted mb-0 small">Kelola daftar unit pengolah arsip dan pejabat penanggung jawab persuratan.</p>
            </div>

            <button type="button" class="btn btn-sm btn-primary-blue" @click="openCreateModal">
                <i class="bi bi-plus-lg me-1"></i> Tambah Unit Kerja
            </button>
        </div>

        <div class="st-card p-3 mb-4">
            <div class="input-group" style="max-width: 400px;">
                <input
                    v-model="search"
                    type="text"
                    class="form-control"
                    placeholder="Cari nama unit, PIC, telepon..."
                    @keyup.enter="handleSearch"
                />
                <button class="btn btn-primary-blue" type="button" @click="handleSearch">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>

        <div class="st-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Nama Unit Kerja</th>
                            <th>Penanggung Jawab (PIC)</th>
                            <th>Kontak & Email</th>
                            <th>Alamat / Lokasi</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in units.data" :key="u.id">
                            <td>
                                <div class="fw-bold text-dark">{{ u.unit_name }}</div>
                            </td>
                            <td>
                                <div class="text-dark">{{ u.pic_name || '-' }}</div>
                            </td>
                            <td>
                                <div class="small">{{ u.phone || '-' }}</div>
                                <small class="text-muted">{{ u.email || '' }}</small>
                            </td>
                            <td>
                                <small class="text-muted">{{ u.address || '-' }}</small>
                            </td>
                            <td>
                                <span class="badge" :class="u.is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
                                    {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" @click="openEditModal(u)">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" @click="deleteUnit(u.id)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="units.data.length === 0">
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada unit terdaftar.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <small class="text-muted">Total: {{ units.total }} unit</small>
                <Pagination :links="units.links" />
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <template #title>
                {{ isEditing ? 'Ubah Unit Kerja' : 'Tambah Unit Kerja Baru' }}
            </template>

            <form @submit.prevent="submitUnit">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Unit Kerja</label>
                    <input v-model="form.unit_name" type="text" class="form-control" placeholder="Contoh: Biro Umum dan Keuangan" required />
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama PIC / Pejabat</label>
                    <input v-model="form.pic_name" type="text" class="form-control" placeholder="Nama pejabat/staf PIC" />
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Nomor Telepon / WA</label>
                        <input v-model="form.phone" type="text" class="form-control" placeholder="08xxxxxxxxxx" />
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Email Dinas</label>
                        <input v-model="form.email" type="email" class="form-control" placeholder="unit@kemnaker.go.id" />
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Alamat / Ruangan</label>
                    <textarea v-model="form.address" class="form-control" rows="2" placeholder="Gedung & lantai..."></textarea>
                </div>

                <div class="form-check mb-4">
                    <input id="unit-active" v-model="form.is_active" type="checkbox" class="form-check-input" />
                    <label for="unit-active" class="form-check-label small fw-semibold">Status Unit Aktif</label>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <button type="button" class="btn btn-secondary" @click="showModal = false">Batal</button>
                    <button type="submit" class="btn btn-primary-blue" :disabled="form.processing">
                        Simpan Unit
                    </button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
