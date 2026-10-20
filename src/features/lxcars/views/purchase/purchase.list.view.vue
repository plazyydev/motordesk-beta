<!-- src/features/lxcars/views/purchase/purchase.list.view.vue -->

<template>
    <NavbarView />

    <v-container fluid class="pa-2 pa-sm-4">
        <div class="d-flex flex-wrap align-center justify-space-between ga-2 mb-3">
            <div class="d-flex align-center ga-2">
                <v-icon color="primary" size="large">mdi-calculator-variant</v-icon>
                <div>
                    <h1 class="text-h5 text-sm-h4 mb-0">{{ $t('PurchaseView.title') }}</h1>
                    <div class="text-body-2 text-medium-emphasis">
                        {{ car_purchases.length }} {{ $t('PurchaseView.entries') }}
                    </div>
                </div>
            </div>
            <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog()">
                {{ $t('PurchaseView.new') }}
            </v-btn>
        </div>

        <v-row dense class="mb-2">
            <v-col v-for="key in ['total_costs', 'total_asking_price', 'total_profit']" :key="key" cols="12" sm="4">
                <v-sheet border rounded class="pa-3">
                    <div class="text-caption text-medium-emphasis">{{ $t('PurchaseView.summary.' + key) }}</div>
                    <div class="text-h6">{{ formatMoney(summary[key]) }}</div>
                </v-sheet>
            </v-col>
        </v-row>

        <v-sheet border rounded class="pa-3 mb-3">
            <v-row align="center" dense>
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="search"
                        :label="$t('PurchaseView.search')"
                        prepend-inner-icon="mdi-magnify"
                        variant="outlined"
                        density="compact"
                        hide-details
                        clearable
                    />
                </v-col>
                <v-col cols="12" md="6" class="d-flex align-center ga-2">
                    <v-chip-group v-model="status" selected-class="text-primary" mandatory>
                        <v-chip v-for="s in status_keys" :key="s" :value="s" filter size="small">
                            {{ $t('PurchaseView.status.' + (s || 'all')) }}
                        </v-chip>
                    </v-chip-group>
                    <v-btn icon variant="text" :loading="loading" :title="$t('PurchaseView.refresh')" @click="load">
                        <v-icon>mdi-refresh</v-icon>
                    </v-btn>
                </v-col>
            </v-row>
        </v-sheet>

        <v-alert v-if="error" type="error" variant="tonal" class="mb-3" density="compact">{{ error }}</v-alert>

        <v-sheet border rounded>
            <v-data-table
                :headers="headers"
                :items="car_purchases"
                :loading="loading"
                :no-data-text="$t('PurchaseView.noData')"
                density="compact"
                hover
                class="zebra-table"
                @click:row="(e, row) => openDialog(row.item)"
            >
                <template #item.vehicle_number="{ item }">
                    <span class="font-weight-medium text-no-wrap">{{ item.vehicle_number }}</span>
                </template>
                <template #item.c_mt="{ item }">
                    <span class="text-no-wrap">{{ item.c_mt || '-' }}</span>
                </template>
                <template #item.purchase_date="{ item }">{{ formatDate(item.purchase_date) }}</template>
                <template #item.total_costs="{ item }">{{ formatMoney(item.total_costs) }}</template>
                <template #item.target_price="{ item }">{{ formatMoney(item.target_price) }}</template>
                <template #item.asking_price="{ item }">{{ formatMoney(item.asking_price) }}</template>
                <template #item.profit="{ item }">
                    <span v-if="item.profit !== null" :class="item.profit < 0 ? 'text-error' : 'text-success'">
                        {{ formatMoney(item.profit) }}
                    </span>
                    <span v-else>-</span>
                </template>
                <template #item.days_in_stock="{ item }">{{ $t('PurchaseView.days', { n: item.days_in_stock }) }}</template>
                <template #item.actions="{ item }">
                    <v-btn icon variant="text" size="small" :title="$t('PriceTag.title')" @click.stop="openDocument(item, 'price_tag')">
                        <v-icon>mdi-tag-text-outline</v-icon>
                    </v-btn>
                    <v-btn icon variant="text" size="small" :title="$t('Expose.create')" @click.stop="openDocument(item, 'expose')">
                        <v-icon>mdi-file-document-outline</v-icon>
                    </v-btn>
                </template>
                <template #item.status="{ item }">
                    <v-chip size="small" variant="tonal" :color="statusColor(item.status)">
                        {{ $t('PurchaseView.status.' + item.status) }}
                    </v-chip>
                </template>
            </v-data-table>
        </v-sheet>

        <v-dialog v-model="dialog" max-width="720" scrollable>
            <v-card>
                <v-card-title>{{ $t('PurchaseView.dialog.title') }}</v-card-title>
                <v-card-text>
                    <v-row dense>
                        <v-col cols="12">
                            <v-autocomplete
                                v-model="form.c_id"
                                v-model:search="car_search"
                                :items="car_options"
                                item-title="label"
                                item-value="c_id"
                                :label="$t('PurchaseView.dialog.car')"
                                :disabled="!!form.id"
                                :loading="car_loading"
                                no-filter
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field v-model="form.seller" :label="$t('PurchaseView.dialog.seller')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field v-model="form.purchase_date" type="date" :label="$t('PurchaseView.dialog.purchase_date')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col v-for="f in money_fields" :key="f" cols="6" sm="4">
                            <v-text-field
                                v-model.number="form[f]"
                                type="number"
                                inputmode="decimal"
                                suffix="€"
                                :label="$t('PurchaseView.dialog.' + f)"
                                variant="outlined"
                                density="compact"
                            />
                        </v-col>
                        <v-col cols="6" sm="4">
                            <v-text-field v-model.number="form.desired_margin_percent" type="number" inputmode="decimal" suffix="%" :label="$t('PurchaseView.dialog.desired_margin_percent')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12">
                            <v-sheet border rounded class="pa-3 d-flex flex-wrap justify-space-between ga-2">
                                <div>
                                    <div class="text-caption text-medium-emphasis">{{ $t('PurchaseView.dialog.total_costs') }}</div>
                                    <div class="text-h6">{{ formatMoney(preview_total_costs) }}</div>
                                </div>
                                <div>
                                    <div class="text-caption text-medium-emphasis">{{ $t('PurchaseView.dialog.target_price') }}</div>
                                    <div class="text-h6 text-primary">{{ formatMoney(preview_target_price) }}</div>
                                </div>
                            </v-sheet>
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field v-model.number="form.asking_price" type="number" inputmode="decimal" suffix="€" :label="$t('PurchaseView.dialog.asking_price')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-select v-model="form.status" :items="status_items" :label="$t('PurchaseView.dialog.status')" variant="outlined" density="compact" />
                        </v-col>
                        <template v-if="form.status === 'sold'">
                            <v-col cols="12" sm="6">
                                <v-text-field v-model.number="form.sold_price" type="number" inputmode="decimal" suffix="€" :label="$t('PurchaseView.dialog.sold_price')" variant="outlined" density="compact" />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field v-model="form.sold_date" type="date" :label="$t('PurchaseView.dialog.sold_date')" variant="outlined" density="compact" />
                            </v-col>
                        </template>
                        <v-col cols="12" class="text-subtitle-2">{{ $t('PurchaseView.dialog.price_tag_section') }}</v-col>
                        <v-col cols="6" sm="4">
                            <v-text-field v-model="form.fuel_type" :label="$t('PriceTag.fuel')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="6" sm="4">
                            <v-text-field v-model.number="form.power_kw" type="number" suffix="kW" :label="$t('PriceTag.power')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="6" sm="4">
                            <v-select v-model="form.efficiency_class" :items="efficiency_classes" clearable :label="$t('PriceTag.efficiency_class')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="6" sm="6">
                            <v-text-field v-model.number="form.co2_g_km" type="number" suffix="g/km" :label="$t('PriceTag.co2')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="6" sm="6">
                            <v-text-field v-model.number="form.consumption_combined" type="number" suffix="l/100 km" :label="$t('PriceTag.consumption')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="6" sm="4">
                            <v-text-field v-model.number="form.consumption_city" type="number" :suffix="form.consumption_unit" :label="$t('PriceTag.consumption_city')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="6" sm="4">
                            <v-text-field v-model.number="form.consumption_highway" type="number" :suffix="form.consumption_unit" :label="$t('PriceTag.consumption_highway')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12" sm="4">
                            <v-select v-model="form.consumption_unit" :items="consumption_units" :label="$t('PurchaseView.dialog.consumption_unit')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12">
                            <v-textarea v-model="form.tag_equipment" :label="$t('PurchaseView.dialog.tag_equipment')" rows="3" auto-grow variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12" class="text-subtitle-2">{{ $t('PurchaseView.dialog.details_section') }}</v-col>
                        <v-col cols="6" sm="4">
                            <v-text-field v-model="form.engine_code" :label="$t('Expose.fields.engine_code')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="6" sm="4">
                            <v-text-field v-model="form.paint_code" :label="$t('Expose.fields.paint_code')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12" sm="4">
                            <v-text-field v-model="form.transmission" :label="$t('Expose.fields.transmission')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="4" sm="3">
                            <v-text-field v-model.number="form.doors" type="number" :label="$t('Expose.fields.doors')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="4" sm="3">
                            <v-text-field v-model.number="form.seats" type="number" :label="$t('Expose.fields.seats')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="4" sm="3">
                            <v-text-field v-model.number="form.previous_owners" type="number" :label="$t('Expose.fields.previous_owners')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12" sm="3">
                            <v-checkbox v-model="form.accident_free" :label="$t('Expose.fields.accident_free')" density="compact" hide-details />
                        </v-col>
                        <v-col cols="12">
                            <v-textarea v-model="form.description" :label="$t('Expose.description')" rows="3" auto-grow variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12">
                            <v-textarea v-model="form.equipment_highlights" :label="$t('PurchaseView.dialog.equipment_highlights')" rows="2" auto-grow variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12" class="text-subtitle-2">{{ $t('PurchaseView.dialog.expose_section') }}</v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field v-model="form.contact_name" :label="$t('PurchaseView.dialog.contact_name')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="6" sm="3">
                            <v-text-field v-model="form.contact_phone" :label="$t('PurchaseView.dialog.contact_phone')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="6" sm="3">
                            <v-text-field v-model="form.contact_email" :label="$t('PurchaseView.dialog.contact_email')" variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12">
                            <v-textarea v-model="form.vehicle_location" :label="$t('Expose.location')" rows="2" auto-grow variant="outlined" density="compact" />
                        </v-col>
                        <v-col cols="12">
                            <v-textarea v-model="form.expose_message" :label="$t('PurchaseView.dialog.expose_message')" rows="3" auto-grow variant="outlined" density="compact" />
                        </v-col>
                        <v-col v-if="form.c_id" cols="12">
                            <div class="text-subtitle-2 mb-1">{{ $t('Expose.photos') }}</div>
                            <div class="d-flex flex-wrap ga-2 mb-2">
                                <div v-for="name in photos" :key="name" class="photo-thumb" :style="{ backgroundImage: `url(${photoUrl(form.c_id, name)})` }">
                                    <v-btn icon="mdi-close" size="x-small" color="error" class="photo-thumb__delete" @click="removePhoto(name)" />
                                </div>
                            </div>
                            <v-btn prepend-icon="mdi-camera-plus" variant="tonal" :loading="photo_uploading" @click="photo_input.click()">
                                {{ $t('PurchaseView.dialog.add_photos') }}
                            </v-btn>
                            <input ref="photo_input" type="file" accept="image/*" multiple hidden @change="addPhotos">
                        </v-col>
                        <v-col cols="12">
                            <v-textarea v-model="form.note" :label="$t('PurchaseView.dialog.note')" rows="2" auto-grow variant="outlined" density="compact" />
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="dialog = false">{{ $t('PurchaseView.dialog.cancel') }}</v-btn>
                    <v-btn color="primary" variant="flat" :disabled="!form.c_id" :loading="saving" @click="save">
                        {{ $t('PurchaseView.dialog.save') }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="document_dialog" max-width="900" scrollable>
            <v-card>
                <v-card-title class="d-flex align-center">
                    {{ document_kind === 'expose' ? $t('Expose.title') : $t('PriceTag.title') }}
                    <v-spacer />
                    <v-switch v-model="show_logo" :label="$t('PurchaseView.show_logo')" density="compact" hide-details color="primary" class="mr-4" />
                    <v-btn prepend-icon="mdi-printer" color="primary" variant="flat" @click="printDocument">{{ $t('PriceTag.print') }}</v-btn>
                </v-card-title>
                <v-card-text class="document-preview">
                    <div ref="document_area" class="document-print-area">
                        <template v-if="car_document">
                            <Expose v-if="document_kind === 'expose'" :car_document="car_document" :company_logo="show_logo ? branding.companyLogo : ''" :accent_color="branding.companyAccentColor" :images="document_images" />
                            <PriceTag v-else :car_document="car_document" :company_logo="show_logo ? branding.companyLogo : ''" :accent_color="branding.companyAccentColor" />
                        </template>
                    </div>
                </v-card-text>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import NavbarView from '@/core/components/navbar/navbar.view.vue';
import PriceTag from '@/features/lxcars/components/price-tag.vue';
import Expose from '@/features/lxcars/components/expose.vue';
import { oserpStore } from '@/core/stores/oserp.store.js';
import { lxcarsStore } from '@/features/lxcars/stores/lxcars.store.js';

const { t } = useI18n();
const store = lxcarsStore();
const branding = oserpStore().getBrandingConfig();

// Effizienzklassen sind gesetzlich festgelegt (PKW-EnVKV)
const efficiency_classes = ['A+', 'A', 'B', 'C', 'D', 'E', 'F', 'G'];
const document_dialog = ref(false);
const document_kind = ref('price_tag');
const document_area = ref(null);
const car_document = ref(null);
const show_logo = ref(true);
const document_images = ref([]);
const photos = ref([]);
const photo_input = ref(null);
const photo_uploading = ref(false);
const consumption_units = ['l/100 km', 'kWh/100 km', 'kg/100 km'];

const status_keys = ['', 'purchased', 'listed', 'reserved', 'sold'];
const money_fields = ['purchase_price', 'costs_repair', 'costs_preparation', 'costs_transport', 'costs_other'];

const car_purchases = ref([]);
const summary = ref({ total_costs: 0, total_asking_price: 0, total_profit: 0 });
const loading = ref(false);
const error = ref('');
const search = ref('');
const status = ref('');

const dialog = ref(false);
const saving = ref(false);
const form = ref({});
const car_search = ref('');
const car_options = ref([]);
const car_loading = ref(false);

const headers = computed(() =>
    ['vehicle_number', 'c_ln', 'c_mt', 'purchase_date', 'total_costs', 'target_price', 'asking_price', 'profit', 'days_in_stock', 'status', 'actions']
        .map(key => ({ title: key === 'actions' ? '' : t('PurchaseView.headers.' + key), key, sortable: key !== 'actions' }))
);
const status_items = computed(() =>
    status_keys.filter(Boolean).map(value => ({ value, title: t('PurchaseView.status.' + value) }))
);

// Live-Vorschau im Formular; die verbindliche Berechnung liefert car_purchases_calc() in der DB
const preview_total_costs = computed(() =>
    money_fields.reduce((sum, f) => sum + (Number(form.value[f]) || 0), 0)
);
const preview_target_price = computed(() =>
    preview_total_costs.value * (1 + (Number(form.value.desired_margin_percent) || 0) / 100)
);

let timer = null;

async function load() {
    loading.value = true;
    error.value = '';
    try {
        const payload = await store.loadCarPurchases({ search: search.value || '', status: status.value || '' });
        car_purchases.value = payload.results;
        summary.value = payload;
    } catch (err) {
        car_purchases.value = [];
        error.value = err?.message || '';
    } finally {
        loading.value = false;
    }
}

function openDialog(item = null) {
    form.value = item
        ? { ...item }
        : { status: 'purchased', consumption_unit: 'l/100 km', desired_margin_percent: 10, purchase_date: new Date().toISOString().slice(0, 10) };
    photos.value = [];
    if (item) loadPhotos(item.c_id);
    car_options.value = item ? [{ c_id: item.c_id, label: [item.c_ln, item.c_mt].filter(Boolean).join(' ') }] : [];
    dialog.value = true;
}

async function save() {
    saving.value = true;
    try {
        await store.saveCarPurchase(form.value);
        dialog.value = false;
        await load();
    } catch (err) {
        error.value = err?.message || '';
    } finally {
        saving.value = false;
    }
}

async function searchCars(query) {
    car_loading.value = true;
    try {
        const payload = await store.loadCars({ search: query || '', per_page: 10 });
        car_options.value = payload.results.map(c => ({
            c_id: c.c_id,
            label: [c.c_ln, c.c_m, c.c_mt].filter(Boolean).join(' '),
        }));
    } finally {
        car_loading.value = false;
    }
}

/** Oeffnet Preisschild oder Exposé; ein Exposé bekommt bei jedem Erzeugen eine neue Angebotsnummer */
async function openDocument(item, kind) {
    try {
        car_document.value = kind === 'expose'
            ? await store.createCarExpose(item.c_id)
            : await store.loadCarDocumentData(item.c_id);
        document_images.value = [];
        if (kind === 'expose') {
            const names = await store.loadCarPhotos(item.c_id);
            document_images.value = names.map(name => photoUrl(item.c_id, name));
        }
        show_logo.value = true;
        document_kind.value = kind;
        document_dialog.value = true;
    } catch (err) {
        error.value = err?.message || '';
    }
}

/** Bild-URL ueber den vorhandenen Fahrzeug-Dateimanager (vfPreview) */
function photoUrl(c_id, name) {
    return `/api/customer_vendor/?${new URLSearchParams({ action: 'vfPreview', c_id, path: 'fotos/' + name })}`;
}

async function loadPhotos(c_id) {
    try {
        photos.value = await store.loadCarPhotos(c_id);
    } catch (err) {
        error.value = err?.message || '';
    }
}

async function addPhotos(event) {
    const files = [...event.target.files];
    event.target.value = '';
    if (!files.length) return;
    photo_uploading.value = true;
    try {
        await store.uploadCarPhotos(form.value.c_id, files);
        await loadPhotos(form.value.c_id);
    } catch (err) {
        error.value = err?.message || '';
    } finally {
        photo_uploading.value = false;
    }
}

async function removePhoto(name) {
    try {
        await store.deleteCarPhoto(form.value.c_id, name);
        await loadPhotos(form.value.c_id);
    } catch (err) {
        error.value = err?.message || '';
    }
}

/** Druckt nur das Dokument: Seiten werden samt Styles in einen unsichtbaren Frame kopiert (mehrseitig moeglich) */
function printDocument() {
    const frame = document.createElement('iframe');
    frame.style.cssText = 'position:fixed;width:0;height:0;border:0;';
    document.body.appendChild(frame);
    const styles = [...document.querySelectorAll('style, link[rel="stylesheet"]')].map(node => node.outerHTML).join('');
    frame.contentDocument.write(`<!doctype html><html><head><meta charset="utf-8">${styles}</head><body style="margin:0">${document_area.value.innerHTML}</body></html>`);
    frame.contentDocument.close();
    let printed = false;
    const print = () => {
        if (printed) return;
        printed = true;
        frame.contentWindow.print();
        setTimeout(() => frame.remove(), 1000);
    };
    frame.onload = print;
    // Falls das Dokument schon geladen ist, onload aber nicht mehr feuert
    if (frame.contentDocument.readyState === 'complete') print();
}

function statusColor(value) {
    return { purchased: 'grey', listed: 'primary', reserved: 'warning', sold: 'success' }[value];
}

function formatMoney(value) {
    if (value === null || value === undefined || value === '') return '-';
    return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(Number(value));
}

function formatDate(value) {
    if (!value) return '-';
    const [year, month, day] = value.split('-');
    return `${day}.${month}.${year}`;
}

watch([search, status], () => {
    clearTimeout(timer);
    timer = setTimeout(load, 300);
});
watch(() => form.value.c_id, c_id => {
    if (dialog.value && c_id && !form.value.id) loadPhotos(c_id);
});
watch(car_search, query => {
    if (!dialog.value || form.value.id) return;
    clearTimeout(timer);
    timer = setTimeout(() => searchCars(query), 300);
});

onMounted(load);
</script>

<style>
.document-preview { overflow: auto; }
.photo-thumb { width: 88px; height: 66px; border-radius: 4px; background: #eee center / cover no-repeat; position: relative; }
.photo-thumb__delete { position: absolute; top: 2px; right: 2px; }
.document-print-area { zoom: 0.5; width: max-content; margin: 0 auto; }
.document-print-area .doc-page { margin-bottom: 8mm; box-shadow: 0 0 6px rgba(0, 0, 0, .3); }
</style>
