<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import MasterTabs from '@/Components/MasterTabs.vue';
import { User, PaginatedData } from '@/types';

interface Role {
    id: number;
    name: string;
}

const props = defineProps<{
    users: PaginatedData<User>;
    roles: Role[];
    filters: { search: string };
}>();

const search = ref(props.filters.search || '');
const showModal = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null as number | null,
    username: '',
    name: '',
    email: '',
    role: 'admin',
    password: '',
    is_active: true,
});

const handleSearch = () => {
    router.get('/master/users', { search: search.value }, { preserveState: true });
};

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.role = 'admin';
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (u: User) => {
    isEditing.value = true;
    form.id = u.id;
    form.username = u.username;
    form.name = u.name || '';
    form.email = u.email || '';
    form.role = u.role;
    form.password = '';
    form.is_active = u.is_active;
    showModal.value = true;
};

const submituser = () => {
    form.post('/master/users', {
        onSuccess: () => {
            showModal.value = false;
        },
    });
};

const deleteUser = (id: number) => {
    if (confirm('Hapus akun staf ini?')) {
        router.delete(`/master/users/${id}`);
    }
};

const getRoleBadge = (role: string) => {
    switch (role) {
        case 'super_admin':
            return 'bg-danger text-white';
        case 'sekjen':
            return 'bg-primary text-white';
        case 'kasubbag':
            return 'bg-warning text-dark';
        default:
            return 'bg-secondary text-white';
    }
};
</script>

<template>
    <AppLayout title="Manajemen User Staf & Hak Akses">
        <Head title="Manajemen User" />

        <MasterTabs />

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="text-uppercase fw-bold small text-primary">Hak Akses & Peran (Spatie RBAC)</span>
                <h2 class="fw-bold mb-1 text-dark">Manajemen User Staf</h2>
                <p class="text-muted mb-0 small">Kelola akun staf operasional, administrator, dan pimpinan.</p>
            </div>

            <button type="button" class="btn btn-sm btn-primary-blue" @click="openCreateModal">
                <i class="bi bi-person-plus-fill me-1"></i> Tambah User Baru
            </button>
        </div>

        <div class="st-card p-3 mb-4">
            <div class="input-group" style="max-width: 400px;">
                <input
                    v-model="search"
                    type="text"
                    class="form-control"
                    placeholder="Cari username, nama, role..."
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
                            <th>Username & Nama</th>
                            <th>Email</th>
                            <th>Peran / Role</th>
                            <th>Status Akun</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in users.data" :key="u.id">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="user-avatar-circle" style="width: 32px; height: 32px; font-size: 0.75rem;">
                                        {{ (u.name || u.username).substring(0, 1).toUpperCase() }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ u.username }}</div>
                                        <small class="text-muted">{{ u.name || '-' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ u.email || '-' }}</td>
                            <td>
                                <span :class="['badge rounded-pill px-3 py-1 font-monospace', getRoleBadge(u.role)]">
                                    {{ u.role }}
                                </span>
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
                                    <button type="button" class="btn btn-outline-danger" @click="deleteUser(u.id)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada akun user terdaftar.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <small class="text-muted">Total: {{ users.total }} akun staf</small>
                <Pagination :links="users.links" />
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <template #title>
                {{ isEditing ? 'Ubah Akun Staf' : 'Tambah Akun Staf Baru' }}
            </template>

            <form @submit.prevent="submituser">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Username</label>
                    <input v-model="form.username" type="text" class="form-control" placeholder="username" required />
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Lengkap</label>
                    <input v-model="form.name" type="text" class="form-control" placeholder="Nama staf / pejabat" />
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Email</label>
                    <input v-model="form.email" type="email" class="form-control" placeholder="staf@sitrack.local" />
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Peran (Spatie RBAC Role)</label>
                    <select v-model="form.role" class="form-select" required>
                        <option value="super_admin">Super Admin (Akses Penuh)</option>
                        <option value="admin">Admin Operator (Lajur Persuratan)</option>
                        <option value="kasubbag">Kasubbag TU (Verifikasi & Pengendalian)</option>
                        <option value="sekjen">Sekretaris Jenderal (Pimpinan & Disposisi)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">
                        Kata Sandi {{ isEditing ? '(Kosongkan bila tidak ingin diubah)' : '' }}
                    </label>
                    <input
                        v-model="form.password"
                        type="password"
                        class="form-control"
                        placeholder="Minimal 6 karakter"
                        :required="!isEditing"
                    />
                </div>

                <div class="form-check mb-4">
                    <input id="user-active" v-model="form.is_active" type="checkbox" class="form-check-input" />
                    <label for="user-active" class="form-check-label small fw-semibold">Akun Aktif</label>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <button type="button" class="btn btn-secondary" @click="showModal = false">Batal</button>
                    <button type="submit" class="btn btn-primary-blue" :disabled="form.processing">
                        Simpan Akun
                    </button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
