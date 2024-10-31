<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import StatTable from "@/Components/StatTable.vue";
</script>

<template>
    <Head title="Себестоимость" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Себестоимость</h2>
        </template>
        <StatTable />
    </AuthenticatedLayout>
</template>
