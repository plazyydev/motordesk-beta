<!-- src/features/lxcars/views/car/car.list.view.vue -->

<template>
    <NavbarView />

    <v-container fluid class="car-list-view pa-2 pa-sm-4">
        <div class="car-list-view__header">
            <div class="car-list-view__title">
                <v-icon color="primary" size="large">mdi-car-multiple</v-icon>
                <div>
                    <h1 class="text-h5 text-sm-h4 mb-0">Fahrzeuge</h1>
                    <div class="text-body-2 text-medium-emphasis">
                        {{ total }} Eintraege
                    </div>
                </div>
            </div>

            <div class="car-list-view__actions">
                <v-btn
                    color="primary"
                    prepend-icon="mdi-plus"
                    :to="{ name: 'fahrzeug-neu' }"
                >
                    Neu
                </v-btn>
                <v-btn
                    variant="outlined"
                    color="primary"
                    prepend-icon="mdi-camera"
                    :to="{ name: 'car-new-from-scan' }"
                >
                    Scan
                </v-btn>
            </div>
        </div>

        <v-sheet border rounded class="car-list-view__filters pa-3 mb-3">
            <v-row align="center" dense>
                <v-col cols="12" md="7" lg="6">
                    <v-text-field
                        v-model="search"
                        label="Suche"
                        prepend-inner-icon="mdi-magnify"
                        variant="outlined"
                        density="compact"
                        hide-details
                        clearable
                    />
                </v-col>
                <v-col cols="12" md="5" lg="6" class="d-flex justify-md-end">
                    <v-btn
                        icon
                        variant="text"
                        :loading="loading"
                        title="Aktualisieren"
                        @click="loadCars"
                    >
                        <v-icon>mdi-refresh</v-icon>
                    </v-btn>
                </v-col>
            </v-row>
        </v-sheet>

        <v-alert
            v-if="error"
            type="error"
            variant="tonal"
            class="mb-3"
            density="compact"
        >
            {{ error }}
        </v-alert>

        <v-sheet border rounded class="car-list-view__table">
            <v-data-table-server
                v-model:page="page"
                v-model:items-per-page="itemsPerPage"
                v-model:sort-by="sortBy"
                :headers="headers"
                :items="cars"
                :items-length="total"
                :items-per-page-options="[10, 25, 50, 100]"
                :loading="loading"
                density="compact"
                hover
                class="zebra-table"
                no-data-text="Keine Fahrzeuge gefunden"
                @update:options="onOptionsUpdate"
                @click:row="openCar"
            >
                <template #item.c_ln="{ item }">
                    <span class="font-weight-medium text-no-wrap">{{ item.c_ln || '-' }}</span>
                </template>

                <template #item.owner_name="{ item }">
                    <div class="car-list-view__owner">
                        <span>{{ item.owner_name || '-' }}</span>
                        <span v-if="item.owner_phone" class="text-caption text-medium-emphasis">
                            {{ item.owner_phone }}
                        </span>
                    </div>
                </template>

                <template #item.vehicle="{ item }">
                    <div class="car-list-view__vehicle">
                        <span>{{ vehicleLabel(item) }}</span>
                        <span v-if="item.c_color" class="text-caption text-medium-emphasis">
                            {{ item.c_color }}
                        </span>
                    </div>
                </template>

                <template #item.c_fin="{ item }">
                    <span class="text-caption text-no-wrap">{{ item.c_fin || '-' }}</span>
                </template>

                <template #item.c_hu="{ item }">
                    <v-chip
                        v-if="item.c_hu"
                        size="small"
                        variant="tonal"
                        :color="huColor(item.c_hu)"
                    >
                        {{ formatDate(item.c_hu) }}
                    </v-chip>
                    <span v-else>-</span>
                </template>

                <template #item.c_km="{ item }">
                    <span class="text-no-wrap">{{ formatKm(item.c_km) }}</span>
                </template>

                <template #item.order_count="{ item }">
                    <span class="text-no-wrap">{{ Number(item.order_count || 0) }}</span>
                </template>

                <template #item.actions="{ item }">
                    <div class="d-inline-flex align-center">
                        <v-btn
                            icon
                            size="x-small"
                            variant="text"
                            color="primary"
                            title="Oeffnen"
                            :to="{ name: 'car', params: { id: item.c_id } }"
                            @click.stop
                        >
                            <v-icon size="small">mdi-open-in-new</v-icon>
                        </v-btn>
                    </div>
                </template>
            </v-data-table-server>
        </v-sheet>
    </v-container>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import NavbarView from '@/core/components/navbar/navbar.view.vue';
import { lxcarsStore } from '@/features/lxcars/stores/lxcars.store.js';

const router = useRouter();
const store = lxcarsStore();

const cars = ref([]);
const total = ref(0);
const loading = ref(false);
const error = ref('');
const search = ref('');
const page = ref(1);
const itemsPerPage = ref(25);
const sortBy = ref([{ key: 'c_ln', order: 'asc' }]);

let searchTimer = null;
let requestSerial = 0;

const headers = [
    { title: 'Kennzeichen', key: 'c_ln', sortable: true },
    { title: 'Besitzer', key: 'owner_name', sortable: true },
    { title: 'Fahrzeug', key: 'vehicle', sortable: false },
    { title: 'FIN', key: 'c_fin', sortable: false },
    { title: 'HU', key: 'c_hu', sortable: true },
    { title: 'KM', key: 'c_km', sortable: true, align: 'end' },
    { title: 'Auftraege', key: 'order_count', sortable: false, align: 'end' },
    { title: '', key: 'actions', sortable: false, align: 'end' },
];

const sortKeyMap = {
    c_ln: 'plate',
    owner_name: 'owner',
    c_hu: 'hu',
    c_d: 'first_registration',
    c_km: 'km',
    last_order_date: 'last_order',
};

async function loadCars() {
    const serial = ++requestSerial;
    loading.value = true;
    error.value = '';

    const currentSort = sortBy.value?.[0] || { key: 'c_ln', order: 'asc' };
    try {
        const payload = await store.loadCars({
            page: page.value,
            per_page: itemsPerPage.value,
            search: search.value,
            sort: sortKeyMap[currentSort.key] || 'plate',
            direction: currentSort.order || 'asc',
        });

        if (serial !== requestSerial) return;

        cars.value = payload.results || [];
        total.value = Number(payload.total || 0);
    } catch (err) {
        if (serial !== requestSerial) return;
        cars.value = [];
        total.value = 0;
        error.value = err?.message || 'Fahrzeuge konnten nicht geladen werden';
    } finally {
        if (serial === requestSerial) {
            loading.value = false;
        }
    }
}

function onOptionsUpdate(options) {
    page.value = options.page || 1;
    itemsPerPage.value = options.itemsPerPage || 25;
    sortBy.value = options.sortBy?.length ? options.sortBy : [{ key: 'c_ln', order: 'asc' }];
    loadCars();
}

function openCar(event, row) {
    const item = row?.item?.raw || row?.item;
    if (!item?.c_id) return;
    router.push({ name: 'car', params: { id: item.c_id } });
}

function vehicleLabel(item) {
    return [
        item.c_m || item.kba_hersteller,
        item.c_mt || item.kba_name,
    ].filter(Boolean).join(' ') || '-';
}

function formatKm(value) {
    if (value === null || value === undefined || value === '') return '-';
    return new Intl.NumberFormat('de-DE').format(Number(value));
}

function formatDate(value) {
    if (!value) return '-';
    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        const [year, month, day] = value.split('-');
        return `${day}.${month}.${year}`;
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat('de-DE').format(date);
}

function huColor(value) {
    if (!value) return 'default';
    const huDate = new Date(value);
    if (Number.isNaN(huDate.getTime())) return 'default';

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    huDate.setHours(0, 0, 0, 0);

    if (huDate < today) return 'error';

    const warningDate = new Date(today);
    warningDate.setMonth(warningDate.getMonth() + 2);
    return huDate <= warningDate ? 'warning' : 'success';
}

watch(search, () => {
    window.clearTimeout(searchTimer);
    page.value = 1;
    searchTimer = window.setTimeout(loadCars, 300);
});

onMounted(loadCars);
</script>

<style scoped>
.car-list-view {
    max-width: 1600px;
}

.car-list-view__header {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: space-between;
    margin-bottom: 16px;
}

.car-list-view__title,
.car-list-view__actions {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.car-list-view__table {
    overflow-x: auto;
}

.car-list-view__owner,
.car-list-view__vehicle {
    display: grid;
    gap: 2px;
    min-width: 0;
}

@media (max-width: 600px) {
    .car-list-view__actions {
        width: 100%;
    }

    .car-list-view__actions :deep(.v-btn) {
        flex: 1 1 140px;
    }
}
</style>
