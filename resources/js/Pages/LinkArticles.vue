<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import StatTable from "@/Components/StatTable.vue";
import LinkTable from "@/Components/LinkTable.vue";
</script>

<template>
    <Head title="Привязка артикулов к товарам" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Привязка артикулов к товарам</h2>
        </template>
        <LinkTable />
    </AuthenticatedLayout>

</template>

<script>
export default {
    name: 'LinkArticles',
    // Ваши данные, методы и логика компонента
}
</script>

<style scoped>
/* Ваши стили для компонента */
</style>
