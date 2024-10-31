<script setup>
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { Head } from '@inertiajs/vue3';
    import FinTable from "@/Components/FinTable.vue";
</script>


<template>
    <Head title="Расчёты" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Финансовый отчёт</h2>
        </template>

        <FinTable />
    </AuthenticatedLayout>
</template>

<script>
    export default {
        name: "Finance"
    }
</script>

<style scoped>

</style>
